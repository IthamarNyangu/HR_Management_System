<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\DisciplinaryCase;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\StaffPromotion;
use App\Models\StaffRelocation;
use App\Models\TemporaryAppointment;
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
                'province_name' => $subject->province?->name,
                'facility_id' => $subject->facility_id,
                'facility_name' => $subject->facility?->name,
            ];
        }

        if ($subject instanceof DisciplinaryCase) {
            return [
                'reference_no' => $subject->reference_no,
                'province_id' => $subject->province_id,
                'province_name' => $subject->province?->name,
                'facility_id' => $subject->facility_id,
                'facility_name' => $subject->facility?->name,
            ];
        }

        if ($subject instanceof Attachment) {
            $properties = [
                'filename' => $subject->original_filename,
            ];

            if ($subject->attachable instanceof DisciplinaryCase) {
                $properties['reference_no'] = $subject->attachable->reference_no;
                $properties['province_id'] = $subject->attachable->province_id;
                $properties['province_name'] = $subject->attachable->province?->name;
                $properties['facility_id'] = $subject->attachable->facility_id;
                $properties['facility_name'] = $subject->attachable->facility?->name;
            }

            if ($subject->attachable instanceof StaffPromotion) {
                $properties['reference_no'] = $subject->attachable->reference_no;
                $properties['province_id'] = $subject->attachable->province_id;
                $properties['province_name'] = $subject->attachable->province?->name;
                $properties['facility_id'] = $subject->attachable->facility_id;
                $properties['facility_name'] = $subject->attachable->facility?->name;
            }

            if ($subject->attachable instanceof StaffRelocation) {
                $properties['reference_no'] = $subject->attachable->reference_no;
                $properties['from_province_id'] = $subject->attachable->from_province_id;
                $properties['from_province_name'] = $subject->attachable->fromProvince?->name;
                $properties['from_facility_id'] = $subject->attachable->from_facility_id;
                $properties['from_facility_name'] = $subject->attachable->fromFacility?->name;
                $properties['to_province_id'] = $subject->attachable->to_province_id;
                $properties['to_province_name'] = $subject->attachable->toProvince?->name;
                $properties['to_facility_id'] = $subject->attachable->to_facility_id;
                $properties['to_facility_name'] = $subject->attachable->toFacility?->name;
            }

            if ($subject->attachable instanceof TemporaryAppointment) {
                $properties['reference_no'] = $subject->attachable->reference_no;
                $properties['province_id'] = $subject->attachable->province_id;
                $properties['province_name'] = $subject->attachable->province?->name;
                $properties['facility_id'] = $subject->attachable->facility_id;
                $properties['facility_name'] = $subject->attachable->facility?->name;
            }

            return $properties;
        }

        if ($subject instanceof StaffPromotion) {
            return [
                'reference_no' => $subject->reference_no,
                'province_id' => $subject->province_id,
                'province_name' => $subject->province?->name,
                'facility_id' => $subject->facility_id,
                'facility_name' => $subject->facility?->name,
            ];
        }

        if ($subject instanceof StaffRelocation) {
            return [
                'reference_no' => $subject->reference_no,
                'from_province_id' => $subject->from_province_id,
                'from_province_name' => $subject->fromProvince?->name,
                'from_facility_id' => $subject->from_facility_id,
                'from_facility_name' => $subject->fromFacility?->name,
                'to_province_id' => $subject->to_province_id,
                'to_province_name' => $subject->toProvince?->name,
                'to_facility_id' => $subject->to_facility_id,
                'to_facility_name' => $subject->toFacility?->name,
            ];
        }

        if ($subject instanceof TemporaryAppointment) {
            return [
                'reference_no' => $subject->reference_no,
                'province_id' => $subject->province_id,
                'province_name' => $subject->province?->name,
                'facility_id' => $subject->facility_id,
                'facility_name' => $subject->facility?->name,
            ];
        }

        if ($subject instanceof JobOpening) {
            return [
                'reference_no' => $subject->reference_no,
                'province_id' => $subject->province_id,
                'province_name' => $subject->province?->name,
                'facility_id' => $subject->facility_id,
                'facility_name' => $subject->facility?->name,
            ];
        }

        if ($subject instanceof User) {
            $provinceId = $subject->employee?->province_id ?? $subject->province_id;
            $provinceName = $subject->employee?->province?->name ?? $subject->province?->name;
            $facilityId = $subject->employee?->facility_id;
            $facilityName = $subject->employee?->facility?->name;

            return [
                'user_email' => $subject->email,
                'province_id' => $provinceId,
                'province_name' => $provinceName,
                'facility_id' => $facilityId,
                'facility_name' => $facilityName,
            ];
        }

        return [];
    }
}
