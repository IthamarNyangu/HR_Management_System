<?php

use App\Console\Commands\ApplyEffectiveStaffPromotions;
use App\Console\Commands\ApplyEffectiveStaffRelocations;
use App\Console\Commands\AutoCloseExpiredDisciplinaryCases;
use App\Console\Commands\AutoCompleteTemporaryAppointments;
use App\Console\Commands\NotifyTemporaryAppointmentsEndingSoon;
use App\Console\Commands\ReconcileWorkPulse;
use App\Console\Commands\SendMailTest;
use App\Console\Commands\SyncEmployeeImportMasterData;
use App\Http\Middleware\EnsurePasswordHasBeenChanged;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\VerifyWorkPulseIntegrationToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        AutoCloseExpiredDisciplinaryCases::class,
        ApplyEffectiveStaffPromotions::class,
        ApplyEffectiveStaffRelocations::class,
        AutoCompleteTemporaryAppointments::class,
        NotifyTemporaryAppointmentsEndingSoon::class,
        SyncEmployeeImportMasterData::class,
        SendMailTest::class,
        ReconcileWorkPulse::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.changed' => EnsurePasswordHasBeenChanged::class,
            'workpulse.integration' => VerifyWorkPulseIntegrationToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The uploaded file is too large. Please upload a file smaller than 10 MB.',
                ], 413);
            }

            return redirect()
                ->back()
                ->with('error', 'The uploaded file is too large. Please upload a file smaller than 10 MB.');
        });
    })->create();
