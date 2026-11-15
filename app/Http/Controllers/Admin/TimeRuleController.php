<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Service;
use App\Models\ServiceSlotRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeRuleController extends Controller
{
    public function index(): View
    {
        $rules = ServiceSlotRule::query()
            ->with(['barber:id,name', 'items.service:id,name,category'])
            ->orderBy('id')
            ->get();

        $barbers = Barber::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $categories = Service::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.rules.time.index', compact('rules', 'barbers', 'services', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$ruleData, $itemRows] = $this->validatedPayload($request);

        DB::transaction(function () use ($ruleData, $itemRows) {
            $rule = ServiceSlotRule::create($ruleData);
            $rule->items()->createMany($itemRows);
        });

        return redirect()
            ->route('admin.rules.time.index')
            ->with('success', 'Time rule created successfully.');
    }

    public function update(Request $request, ServiceSlotRule $timeRule): RedirectResponse
    {
        [$ruleData, $itemRows] = $this->validatedPayload($request);

        DB::transaction(function () use ($timeRule, $ruleData, $itemRows) {
            $timeRule->update($ruleData);
            $timeRule->items()->delete();
            $timeRule->items()->createMany($itemRows);
        });

        return redirect()
            ->route('admin.rules.time.index')
            ->with('success', 'Time rule updated successfully.');
    }

    public function toggle(ServiceSlotRule $timeRule): RedirectResponse
    {
        $timeRule->update([
            'is_active' => ! $timeRule->is_active,
        ]);

        $statusText = $timeRule->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.rules.time.index')
            ->with('success', "Time rule \"{$timeRule->name}\" {$statusText}.");
    }

    public function destroy(ServiceSlotRule $timeRule): RedirectResponse
    {
        $timeRule->delete();

        return redirect()
            ->route('admin.rules.time.index')
            ->with('success', 'Time rule deleted successfully.');
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'name'           => ['nullable', 'string', 'max:255'],
            'barber_id'      => ['nullable', 'integer', 'exists:barbers,id'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*'        => ['required', 'string'],
            'slots_required' => ['required', 'integer', 'min:1', 'max:10'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $itemRows = [];
        $itemLabels = [];

        foreach ($validated['items'] as $index => $rawItem) {
            $rawItem = trim((string) $rawItem);

            if (str_starts_with($rawItem, 'service:')) {
                $serviceId = (int) substr($rawItem, strlen('service:'));
                $service = Service::find($serviceId);
                if (! $service) {
                    throw ValidationException::withMessages([
                        "items.{$index}" => 'Layanan yang dipilih tidak ditemukan.',
                    ]);
                }

                $itemRows[] = [
                    'service_id' => $service->id,
                    'category'   => null,
                ];
                $itemLabels[] = $service->name;
            } elseif (str_starts_with($rawItem, 'category:')) {
                $category = trim(substr($rawItem, strlen('category:')));
                if ($category === '') {
                    throw ValidationException::withMessages([
                        "items.{$index}" => 'Kategori layanan tidak valid.',
                    ]);
                }

                $itemRows[] = [
                    'service_id' => null,
                    'category'   => $category,
                ];
                $itemLabels[] = 'Kategori ' . ucfirst($category);
            } else {
                throw ValidationException::withMessages([
                    "items.{$index}" => 'Pilih layanan atau kategori yang valid.',
                ]);
            }
        }

        $barberId = ! empty($validated['barber_id']) ? (int) $validated['barber_id'] : null;
        $barberName = $barberId ? (Barber::find($barberId)?->name ?? 'Barber') : 'Semua Barber';

        $name = trim((string) ($validated['name'] ?? ''));
        if ($name === '') {
            $name = implode(' + ', $itemLabels) . " ({$barberName})";
        }

        $ruleData = [
            'name'           => $name,
            'barber_id'      => $barberId,
            'slots_required' => (int) $validated['slots_required'],
            'description'    => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_active'      => $request->boolean('is_active'),
        ];

        return [$ruleData, $itemRows];
    }
}
