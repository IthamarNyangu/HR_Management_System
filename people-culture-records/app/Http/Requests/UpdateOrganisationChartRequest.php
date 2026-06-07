<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Facility;
use App\Models\OrganisationChart;
use App\Models\OrganisationChartNode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrganisationChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        $chart = $this->route('organisation_chart');

        return $chart instanceof OrganisationChart
            && ($this->user()?->can('update', $chart) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'status' => ['required', Rule::in(OrganisationChart::STATUSES)],
            'effective_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],

            'nodes' => ['nullable', 'array'],
            'nodes.*.id' => ['nullable', 'integer'],
            'nodes.*._delete' => ['nullable', 'boolean'],
            'nodes.*.parent_id' => ['nullable', 'integer'],
            'nodes.*.label' => ['required', 'string', 'max:255'],
            'nodes.*.subtitle' => ['nullable', 'string', 'max:255'],
            'nodes.*.node_type' => ['required', Rule::in(array_keys(OrganisationChartNode::TYPES))],
            'nodes.*.planned_positions' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'nodes.*.employee_id' => ['nullable', 'exists:employees,id'],
            'nodes.*.job_title_id' => ['nullable', 'exists:job_titles,id'],
            'nodes.*.project_id' => ['nullable', 'exists:projects,id'],
            'nodes.*.department_id' => ['nullable', 'exists:departments,id'],
            'nodes.*.province_id' => ['nullable', 'exists:provinces,id'],
            'nodes.*.district_id' => ['nullable', 'exists:districts,id'],
            'nodes.*.facility_id' => ['nullable', 'exists:facilities,id'],
            'nodes.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $chart = $this->route('organisation_chart');

                if (! $chart instanceof OrganisationChart) {
                    return;
                }

                $chartNodeIds = $chart->nodes()->pluck('id')->map(fn ($id) => (int) $id)->all();
                $parentMap = [];

                foreach ((array) $this->input('nodes', []) as $index => $node) {
                    $nodeId = filled($node['id'] ?? null) ? (int) $node['id'] : null;
                    $parentId = filled($node['parent_id'] ?? null) ? (int) $node['parent_id'] : null;
                    $isDelete = (bool) ($node['_delete'] ?? false);

                    if ($nodeId && ! in_array($nodeId, $chartNodeIds, true)) {
                        $validator->errors()->add("nodes.{$index}.id", 'This chart box does not belong to this organisation chart.');
                    }

                    if ($nodeId && ! $isDelete) {
                        $parentMap[$nodeId] = $parentId;
                    }

                    if ($parentId && ! in_array($parentId, $chartNodeIds, true)) {
                        $validator->errors()->add("nodes.{$index}.parent_id", 'The parent box must already exist in this organisation chart.');
                    }

                    if ($nodeId && $parentId && $nodeId === $parentId) {
                        $validator->errors()->add("nodes.{$index}.parent_id", 'A chart box cannot report to itself.');
                    }

                    if (filled($node['province_id'] ?? null) && filled($node['district_id'] ?? null)) {
                        $districtBelongsToProvince = District::whereKey($node['district_id'])
                            ->where('province_id', $node['province_id'])
                            ->exists();

                        if (! $districtBelongsToProvince) {
                            $validator->errors()->add("nodes.{$index}.district_id", 'The selected district does not belong to the selected province.');
                        }
                    }

                    if (filled($node['district_id'] ?? null) && filled($node['facility_id'] ?? null)) {
                        $facilityBelongsToDistrict = Facility::whereKey($node['facility_id'])
                            ->where('district_id', $node['district_id'])
                            ->exists();

                        if (! $facilityBelongsToDistrict) {
                            $validator->errors()->add("nodes.{$index}.facility_id", 'The selected facility does not belong to the selected district.');
                        }
                    }
                }

                foreach ((array) $this->input('nodes', []) as $index => $node) {
                    $nodeId = filled($node['id'] ?? null) ? (int) $node['id'] : null;

                    if (! $nodeId || ! isset($parentMap[$nodeId])) {
                        continue;
                    }

                    $seen = [$nodeId];
                    $parentId = $parentMap[$nodeId];

                    while ($parentId && isset($parentMap[$parentId])) {
                        if (in_array($parentId, $seen, true)) {
                            $validator->errors()->add("nodes.{$index}.parent_id", 'This parent selection creates a circular chart relationship.');
                            break;
                        }

                        $seen[] = $parentId;
                        $parentId = $parentMap[$parentId];
                    }
                }
            },
        ];
    }
}
