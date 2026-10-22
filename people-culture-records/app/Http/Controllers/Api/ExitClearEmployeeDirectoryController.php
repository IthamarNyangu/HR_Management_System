<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Support\ExitClearEmployeeSnapshot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExitClearEmployeeDirectoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'min:2', 'max:100', 'required_without:source_id'],
            'source_id' => ['nullable', 'integer', 'min:1', 'required_without:search'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $employees = Employee::query()
            ->with([
                'department:id,name',
                'jobTitle:id,name',
                'facility:id,district_id,name',
                'district:id,province_id,name',
                'province:id,name',
                'supervisor:id,employee_no,first_name,last_name,email',
            ])
            ->when($validated['source_id'] ?? null, fn (Builder $query, int $id) => $query->whereKey($id))
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('employee_no', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$term]);
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit((int) ($validated['limit'] ?? 15))
            ->get();

        return response()->json([
            'data' => $employees->map(fn (Employee $employee) => ExitClearEmployeeSnapshot::make($employee))->values(),
        ]);
    }
}
