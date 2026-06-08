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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nodes.*.label.required' => 'Box label is required.',
            'nodes.*.label.max' => 'Box label must be 255 characters or fewer.',
            'nodes.*.node_type.required' => 'Box type is required.',
            'nodes.*.node_type.in' => 'Choose a valid box type.',
            'nodes.*.planned_positions.integer' => 'Planned positions must be a whole number.',
            'nodes.*.planned_positions.min' => 'Planned positions cannot be negative.',
            'nodes.*.planned_positions.max' => 'Planned positions must be 9,999 or fewer.',
            'nodes.*.sort_order.integer' => 'Sort order must be a whole number.',
            'nodes.*.sort_order.min' => 'Sort order cannot be negative.',
            'nodes.*.sort_order.max' => 'Sort order must be 9,999 or fewer.',
            'nodes.*.employee_id.exists' => 'Choose a valid linked employee from the search results.',
            'nodes.*.job_title_id.exists' => 'Choose a valid job title.',
            'nodes.*.project_id.exists' => 'Choose a valid project.',
            'nodes.*.department_id.exists' => 'Choose a valid department.',
            'nodes.*.province_id.exists' => 'Choose a valid province.',
            'nodes.*.district_id.exists' => 'Choose a valid district.',
            'nodes.*.facility_id.exists' => 'Choose a valid facility.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'chart title',
            'status' => 'chart status',
            'effective_date' => 'effective date',
            'nodes.*.label' => 'box label',
            'nodes.*.node_type' => 'box type',
            'nodes.*.planned_positions' => 'planned positions',
            'nodes.*.employee_id' => 'linked employee',
            'nodes.*.job_title_id' => 'job title link',
            'nodes.*.project_id' => 'project link',
            'nodes.*.department_id' => 'department link',
            'nodes.*.province_id' => 'province link',
            'nodes.*.district_id' => 'district link',
            'nodes.*.facility_id' => 'facility link',
            'nodes.*.sort_order' => 'sort order',
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
                $boxKeys = [];

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

                    if (! $isDelete && filled($node['label'] ?? null)) {
                        $boxKey = $this->boxDuplicateKey($node);

                        if (isset($boxKeys[$boxKey])) {
                            $validator->errors()->add(
                                "nodes.{$index}.label",
                                'This chart already has an identical box. Change the label, linked employee, or reporting position before saving.'
                            );
                        }

                        $boxKeys[$boxKey] = true;
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

    /**
     * @param array<string, mixed> $node
     */
    private function boxDuplicateKey(array $node): string
    {
        $value = fn (string $field) => filled($node[$field] ?? null) ? (string) $node[$field] : '';
        $text = fn (string $field) => str((string) ($node[$field] ?? ''))->trim()->lower()->toString();

        return implode('|', [
            $value('parent_id'),
            $text('label'),
            $text('subtitle'),
            $value('node_type'),
            $value('employee_id'),
            $value('job_title_id'),
            $value('project_id'),
            $value('department_id'),
            $value('province_id'),
            $value('district_id'),
            $value('facility_id'),
        ]);
    }
}
