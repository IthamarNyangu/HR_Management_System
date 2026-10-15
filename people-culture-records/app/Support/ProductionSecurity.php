<?php

namespace App\Support;

class ProductionSecurity
{
    /** @return list<string> */
    public function failures(): array
    {
        $failures = [];
        $appUrl = mb_strtolower((string) config('app.url'));

        if (config('app.debug')) {
            $failures[] = 'APP_DEBUG must be false.';
        }
        if (blank(config('app.key'))) {
            $failures[] = 'APP_KEY must be configured.';
        }
        if (! str_starts_with($appUrl, 'https://')) {
            $failures[] = 'APP_URL must use HTTPS.';
        }
        if (filled(config('services.workpulse.sync_token')) && ! str_starts_with(mb_strtolower((string) config('services.workpulse.base_url')), 'https://')) {
            $failures[] = 'WORKPULSE_BASE_URL must use HTTPS when WorkPulse integration is enabled.';
        }
        if (! config('session.encrypt')) {
            $failures[] = 'SESSION_ENCRYPT must be true.';
        }
        if (! config('session.secure')) {
            $failures[] = 'SESSION_SECURE_COOKIE must be true.';
        }
        if (! config('session.http_only')) {
            $failures[] = 'SESSION_HTTP_ONLY must be true.';
        }
        if (! in_array(config('session.same_site'), ['lax', 'strict'], true)) {
            $failures[] = 'SESSION_SAME_SITE must be lax or strict.';
        }
        if (config('database.default') === 'sqlite') {
            $failures[] = 'Production must use a managed MySQL, MariaDB, PostgreSQL, or SQL Server database.';
        }
        if (! config('security.storage_encrypted')) {
            $failures[] = 'Encrypted database and document storage has not been confirmed.';
        }
        if (! config('security.backups_encrypted')) {
            $failures[] = 'Encrypted backups have not been confirmed.';
        }
        if (! config('security.data_residency_confirmed')) {
            $failures[] = 'Zambian data residency has not been confirmed.';
        }
        if (! config('security.privacy_governance_approved')) {
            $failures[] = 'Privacy governance approval has not been confirmed.';
        }

        return $failures;
    }
}
