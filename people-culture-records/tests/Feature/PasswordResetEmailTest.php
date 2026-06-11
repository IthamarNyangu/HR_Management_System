<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetEmailTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create([
            'name' => 'Admin',
            'code' => 'ADMIN',
            'is_active' => true,
        ]);
    }

    public function test_forgot_password_page_loads(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Reset Your Password');
    }

    public function test_password_reset_link_notification_is_sent_to_requested_user_only(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'email' => 'ithamar.nyangu@righttocare-zambia.org',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_user_can_reset_password_from_email_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'email' => 'ithamar.nyangu@righttocare-zambia.org',
            'password' => Hash::make('OldPassword@2026'),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        /** @var ResetPassword $notification */
        $notification = Notification::sent($user, ResetPassword::class)->first();

        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'NewPassword@2026',
            'password_confirmation' => 'NewPassword@2026',
        ])->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('NewPassword@2026', $user->password));
        $this->assertFalse($user->must_change_password);
    }
}
