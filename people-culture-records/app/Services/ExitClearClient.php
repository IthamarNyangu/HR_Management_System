<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeOffboardingCase;
use App\Models\User;
use App\Support\ExitClearEmployeeSnapshot;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExitClearClient
{
    public function initiate(Employee $employee, User $actor, EmployeeOffboardingCase $offboardingCase): array
    {
        $this->assertConfigured();

        $response = $this->client()->post($this->endpoint(), [
            'request_id' => $offboardingCase->integration_request_uuid,
            'employee_source_id' => $employee->id,
            'last_working_date' => $employee->termination_date?->toDateString(),
            'employee' => ExitClearEmployeeSnapshot::make($employee),
            'actor' => [
                'hrms_user_id' => $actor->id,
                'name' => $actor->name,
                'email' => $actor->email,
            ],
        ]);

        return $this->validatedPayload($response);
    }

    public function status(EmployeeOffboardingCase $offboardingCase): array
    {
        $this->assertConfigured();

        if (! $offboardingCase->exitclear_case_id) {
            throw new RuntimeException('This offboarding record is not linked to an ExitClear case.');
        }

        return $this->validatedPayload(
            $this->client()->get($this->endpoint().'/'.$offboardingCase->exitclear_case_id)
        );
    }

    public function caseUrl(EmployeeOffboardingCase $offboardingCase): ?string
    {
        if (! $offboardingCase->exitclear_case_id || blank(config('services.exitclear.base_url'))) {
            return null;
        }

        return rtrim((string) config('services.exitclear.base_url'), '/').'/exit-cases/'.$offboardingCase->exitclear_case_id;
    }

    private function client()
    {
        return Http::acceptJson()
            ->withToken((string) config('services.exitclear.integration_token'))
            ->timeout((int) config('services.exitclear.timeout', 10))
            ->retry(2, 250, throw: false);
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.exitclear.base_url'), '/').'/'.ltrim((string) config('services.exitclear.initiation_path'), '/');
    }

    private function assertConfigured(): void
    {
        if (! config('services.exitclear.enabled')) {
            throw new RuntimeException('ExitClear integration is disabled.');
        }

        $baseUrl = (string) config('services.exitclear.base_url');
        $token = (string) config('services.exitclear.integration_token');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('ExitClear integration is not configured.');
        }

        if (app()->environment('production') && ! str_starts_with(mb_strtolower($baseUrl), 'https://')) {
            throw new RuntimeException('ExitClear integration requires HTTPS in production.');
        }
    }

    private function validatedPayload(Response $response): array
    {
        if (! $response->successful()) {
            $message = $response->json('message');
            $validationMessage = collect($response->json('errors', []))->flatten()->first();

            throw new RuntimeException($validationMessage ?: $message ?: 'ExitClear rejected the request.');
        }

        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['id'], $data['case_number'], $data['status'])) {
            throw new RuntimeException('ExitClear returned an incomplete case response.');
        }

        return [
            'id' => (int) $data['id'],
            'case_number' => (string) $data['case_number'],
            'status' => (string) $data['status'],
            'initiated_at' => $data['initiated_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'created' => (bool) $response->json('meta.created', false),
        ];
    }
}
