<?php

namespace App\Exports\Concerns;

use App\Models\User;
use App\Services\Reports\ReportQueryService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class BuildsReportSheets implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        protected ReportQueryService $reports,
        protected User $user,
        protected array $filters = [],
    ) {}

    abstract protected function type(): string;

    public function collection(): Collection
    {
        $columns = array_keys($this->reports->definition($this->type())['columns']);

        return $this->reports
            ->rows($this->type(), $this->user, $this->filters)
            ->map(fn (array $row) => collect($columns)->map(fn (string $column) => $row[$column] ?? '')->values()->all());
    }

    public function headings(): array
    {
        return array_values($this->reports->definition($this->type())['columns']);
    }
}
