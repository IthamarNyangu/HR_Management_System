<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterDataRequest;
use App\Models\District;
use App\Models\Province;
use App\Support\MasterDataRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('admin.master-data.index', [
            'types' => MasterDataRegistry::all(),
        ]);
    }

    public function records(Request $request, string $type): View
    {
        $config = MasterDataRegistry::get($type);
        $model = $config['model'];

        $records = $model::query()
            ->when(isset($config['relation']), fn ($query) => $query->with($config['relation']))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.master-data.records', compact('type', 'config', 'records'));
    }

    public function create(string $type): View
    {
        $config = MasterDataRegistry::get($type);

        return view('admin.master-data.create', $this->formData($type, $config));
    }

    public function store(MasterDataRequest $request, string $type): RedirectResponse
    {
        $config = MasterDataRegistry::get($type);
        $model = $config['model'];
        $data = $this->validatedData($request);

        $model::create($data);

        return redirect()->route('admin.master-data.records', $type)->with('success', "{$config['label']} record created.");
    }

    public function edit(string $type, int $id): View
    {
        $config = MasterDataRegistry::get($type);
        $record = $this->findRecord($config['model'], $id);

        return view('admin.master-data.edit', $this->formData($type, $config) + compact('record'));
    }

    public function update(MasterDataRequest $request, string $type, int $id): RedirectResponse
    {
        $config = MasterDataRegistry::get($type);
        $record = $this->findRecord($config['model'], $id);
        $record->update($this->validatedData($request));

        return redirect()->route('admin.master-data.records', $type)->with('success', "{$config['label']} record updated.");
    }

    public function toggleStatus(string $type, int $id): RedirectResponse
    {
        $config = MasterDataRegistry::get($type);
        $record = $this->findRecord($config['model'], $id);
        $record->update(['is_active' => ! $record->is_active]);

        return back()->with('success', "{$config['label']} status updated.");
    }

    /**
     * @param class-string<Model> $model
     */
    private function findRecord(string $model, int $id): Model
    {
        return $model::query()->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function formData(string $type, array $config): array
    {
        return [
            'type' => $type,
            'config' => $config,
            'provinces' => Province::where('is_active', true)->orderBy('name')->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(MasterDataRequest $request): array
    {
        return $request->safe()->merge([
            'is_active' => $request->boolean('is_active', true),
        ])->all();
    }
}
