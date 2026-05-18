<?php

namespace App\Support;

use App\Models\CaseStatus;
use App\Models\Department;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\EmploymentStatus;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\OffenceCategory;
use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\PenaltyType;
use App\Models\Project;
use App\Models\PromotionType;
use App\Models\Province;
use App\Models\RelocationReason;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MasterDataRegistry
{
    /**
     * @return array<string, array{label: string, icon: string, model: class-string<Model>, parent?: string, relation?: string}>
     */
    public static function all(): array
    {
        return [
            'provinces' => ['label' => 'Provinces', 'icon' => 'bi-map', 'model' => Province::class],
            'districts' => ['label' => 'Districts', 'icon' => 'bi-geo-alt', 'model' => District::class, 'parent' => 'province_id', 'relation' => 'province'],
            'facilities' => ['label' => 'Facilities', 'icon' => 'bi-building', 'model' => Facility::class, 'parent' => 'district_id', 'relation' => 'district'],
            'job-titles' => ['label' => 'Job Titles', 'icon' => 'bi-briefcase', 'model' => JobTitle::class],
            'departments' => ['label' => 'Departments', 'icon' => 'bi-diagram-3', 'model' => Department::class],
            'employment-statuses' => ['label' => 'Employment Statuses', 'icon' => 'bi-patch-check', 'model' => EmploymentStatus::class],
            'projects' => ['label' => 'Projects', 'icon' => 'bi-folder2-open', 'model' => Project::class],
            'offence-categories' => ['label' => 'Offence Categories', 'icon' => 'bi-exclamation-triangle', 'model' => OffenceCategory::class],
            'penalty-types' => ['label' => 'Penalty Types', 'icon' => 'bi-clipboard2-x', 'model' => PenaltyType::class],
            'case-statuses' => ['label' => 'Case Statuses', 'icon' => 'bi-list-check', 'model' => CaseStatus::class],
            'promotion-types' => ['label' => 'Promotion Types', 'icon' => 'bi-graph-up-arrow', 'model' => PromotionType::class],
            'relocation-reasons' => ['label' => 'Relocation Reasons', 'icon' => 'bi-signpost-split', 'model' => RelocationReason::class],
            'appointment-types' => ['label' => 'Appointment Types', 'icon' => 'bi-person-lines-fill', 'model' => AppointmentType::class],
            'appointment-statuses' => ['label' => 'Appointment Statuses', 'icon' => 'bi-calendar-check', 'model' => AppointmentStatus::class],
            'document-types' => ['label' => 'Document Types', 'icon' => 'bi-files', 'model' => DocumentType::class],
        ];
    }

    /**
     * @return array{label: string, icon: string, model: class-string<Model>, parent?: string, relation?: string}
     */
    public static function get(string $type): array
    {
        return self::all()[$type] ?? throw new InvalidArgumentException('Unknown master data type.');
    }
}
