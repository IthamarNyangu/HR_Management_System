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
     * @return array<string, array{label: string, model: class-string<Model>, parent?: string, relation?: string}>
     */
    public static function all(): array
    {
        return [
            'provinces' => ['label' => 'Provinces', 'model' => Province::class],
            'districts' => ['label' => 'Districts', 'model' => District::class, 'parent' => 'province_id', 'relation' => 'province'],
            'facilities' => ['label' => 'Facilities', 'model' => Facility::class, 'parent' => 'district_id', 'relation' => 'district'],
            'job-titles' => ['label' => 'Job Titles', 'model' => JobTitle::class],
            'departments' => ['label' => 'Departments', 'model' => Department::class],
            'employment-statuses' => ['label' => 'Employment Statuses', 'model' => EmploymentStatus::class],
            'projects' => ['label' => 'Projects', 'model' => Project::class],
            'offence-categories' => ['label' => 'Offence Categories', 'model' => OffenceCategory::class],
            'penalty-types' => ['label' => 'Penalty Types', 'model' => PenaltyType::class],
            'case-statuses' => ['label' => 'Case Statuses', 'model' => CaseStatus::class],
            'promotion-types' => ['label' => 'Promotion Types', 'model' => PromotionType::class],
            'relocation-reasons' => ['label' => 'Relocation Reasons', 'model' => RelocationReason::class],
            'document-types' => ['label' => 'Document Types', 'model' => DocumentType::class],
        ];
    }

    /**
     * @return array{label: string, model: class-string<Model>, parent?: string, relation?: string}
     */
    public static function get(string $type): array
    {
        return self::all()[$type] ?? throw new InvalidArgumentException('Unknown master data type.');
    }
}
