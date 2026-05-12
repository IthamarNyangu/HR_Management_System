<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\DisciplinaryCase;
use App\Models\Employee;
use App\Models\StaffPromotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * @param array<string, mixed> $properties
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();
        $user ??= $request?->user();
        $properties = array_merge($this->subjectProperties($subject), $properties);

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function subjectProperties(?Model $subject): array
    {
        if ($subject instanceof Employee) {
            return [
                'employee_no' => $subject->employee_no,
                'province_id' => $subject->province_id,
            ];
        }

        if ($subject instanceof DisciplinaryCase) {
            return [
                'reference_no' => $subject->reference_no,
                'province_id' => $subject->province_id,
            ];
        }

        if ($subject instanceof Attachment) {
            $properties = [
                'filename' => $subject->original_filename,
            ];

            if ($subject->attachable instanceof DisciplinaryCase) {
                $properties['reference_no'] = $subject->attachable->reference_no;
                $properties['province_id'] = $subject->attachable->province_id;
            }

            if ($subject->attachable instanceof StaffPromotion) {
                $properties['reference_no'] = $subject->attachable->reference_no;
                $properties['province_id'] = $subject->attachable->province_id;
            }

            return $properties;
        }

        if ($subject instanceof StaffPromotion) {
            return [
                'reference_no' => $subject->reference_no,
                'province_id' => $subject->province_id,
            ];
        }

        if ($subject instanceof User) {
            return [
                'user_email' => $subject->email,
                'province_id' => $subject->province_id,
            ];
        }

        return [];
    }
}
