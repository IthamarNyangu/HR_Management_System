<?php

namespace App\Http\Requests;

use App\Models\OrganisationChart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOrganisationChartLayoutRequest extends FormRequest
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
            'nodes' => ['required', 'array', 'min:1'],
            'nodes.*.id' => ['required', 'integer'],
            'nodes.*.parent_id' => ['nullable', 'integer'],
            'nodes.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<int, callable>
     */
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

                    if (! $nodeId || ! in_array($nodeId, $chartNodeIds, true)) {
                        $validator->errors()->add("nodes.{$index}.id", 'This chart box does not belong to this organisation chart.');
                        continue;
                    }

                    if ($parentId && ! in_array($parentId, $chartNodeIds, true)) {
                        $validator->errors()->add("nodes.{$index}.parent_id", 'The parent box must already exist in this organisation chart.');
                    }

                    if ($parentId && $nodeId === $parentId) {
                        $validator->errors()->add("nodes.{$index}.parent_id", 'A chart box cannot report to itself.');
                    }

                    $parentMap[$nodeId] = $parentId;
                }

                foreach ($parentMap as $nodeId => $parentId) {
                    $seen = [$nodeId];

                    while ($parentId && isset($parentMap[$parentId])) {
                        if (in_array($parentId, $seen, true)) {
                            $validator->errors()->add('nodes', 'This layout creates a circular chart relationship.');
                            break 2;
                        }

                        $seen[] = $parentId;
                        $parentId = $parentMap[$parentId];
                    }
                }
            },
        ];
    }
}
