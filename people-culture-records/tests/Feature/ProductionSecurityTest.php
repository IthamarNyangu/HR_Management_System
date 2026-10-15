<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    public function test_browser_responses_include_security_headers(): void
    {
        $this->get('/login')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_check_fails_when_production_controls_are_unsafe(): void
    {
        config([
            'app.debug' => true,
            'app.url' => 'http://hrms.internal',
            'session.encrypt' => false,
            'session.secure' => false,
            'database.default' => 'sqlite',
        ]);

        $this->artisan('security:production-check')
            ->expectsOutputToContain('Production security check failed')
            ->assertExitCode(1);
    }

    public function test_check_passes_only_after_required_controls_are_confirmed(): void
    {
        config([
            'app.debug' => false,
            'app.url' => 'https://hrms.righttocare-zambia.org',
            'services.workpulse.base_url' => 'https://workpulse.righttocare-zambia.org',
            'session.encrypt' => true,
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'database.default' => 'mysql',
            'security.storage_encrypted' => true,
            'security.backups_encrypted' => true,
            'security.data_residency_confirmed' => true,
            'security.privacy_governance_approved' => true,
        ]);

        $this->artisan('security:production-check')
            ->expectsOutput('Production security check passed.')
            ->assertExitCode(0);
    }
}
