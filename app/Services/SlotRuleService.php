<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\BlockedSlot;
use App\Models\Service;
use App\Models\ServiceSlotRule;
use App\Models\ServiceSlotRuleItem;
use Illuminate\Support\Collection;

class SlotRuleService
{
    /**
     * Evaluasi aturan waktu aktif untuk kombinasi barber dan daftar layanan.
     * Jika beberapa aturan cocok bersamaan, slots_required dijumlahkan (SUM).
     */
    public function evaluate(?Barber $barber, Collection|array $services, string $customerName = ''): array
    {
        $serviceModels = $this->normalizeServices($services);

        if ($serviceModels->isEmpty()) {
            return $this->defaultResult();
        }

        $activeRules = $this->getActiveRulesForBarber($barber?->id);
        $matchedRules = $activeRules->filter(
            fn (ServiceSlotRule $rule) => $this->ruleMatchesServices($rule, $serviceModels)
        )->values();

        return $this->buildEvaluationResult($matchedRules, $customerName);
    }

    /**
     * Evaluasi berdasarkan daftar ID rule yang dipilih manual (misalnya di halaman Walk-In).
     */
    public function evaluateByRuleIds(?Barber $barber, array $ruleIds, string $customerName = ''): array
    {
        $ids = collect($ruleIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return $this->defaultResult();
        }

        $matchedRules = $this->getActiveRulesForBarber($barber?->id)
            ->whereIn('id', $ids->all())
            ->values();

        return $this->buildEvaluationResult($matchedRules, $customerName);
    }

    /**
     * Ambil aturan aktif yang berlaku untuk barber tertentu (atau semua barber).
     */
    public function getActiveRulesForBarber(?int $barberId): Collection
    {
        return ServiceSlotRule::query()
            ->with(['items.service:id,name,category'])
            ->active()
            ->where(function ($query) use ($barberId) {
                $query->whereNull('barber_id');
                if ($barberId !== null) {
                    $query->orWhere('barber_id', $barberId);
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * Format daftar aturan aktif untuk dikonsumsi oleh Alpine.js di frontend.
     */
    public function getActiveRulesForFrontend(): Collection
    {
        return ServiceSlotRule::query()
            ->with(['barber:id,name', 'items.service:id,name,category'])
            ->active()
            ->orderBy('id')
            ->get()
            ->map(fn (ServiceSlotRule $rule) => [
                'id'             => $rule->id,
                'name'           => $rule->name,
                'barber_id'      => $rule->barber_id,
                'barber_name'    => $rule->barber?->name,
                'slots_required' => (int) $rule->slots_required,
                'description'    => $rule->description,
                'items'          => $rule->items->map(fn (ServiceSlotRuleItem $item) => [
                    'service_id'   => $item->service_id,
                    'service_name' => $item->service?->name,
                    'category'     => $item->category,
                    'label'        => $item->display_label,
                ])->values(),
            ])
            ->values();
    }

    /**
     * Periksa apakah seluruh item kombinasi dalam satu rule terpenuhi oleh layanan yang dipilih.
     */
    private function ruleMatchesServices(ServiceSlotRule $rule, Collection $serviceModels): bool
    {
        $items = $rule->items;
        if ($items->isEmpty()) {
            return false;
        }

        // Dahulukan pencocokan service spesifik sebelum kategori agar konsumsi layanan akurat
        $sortedItems = $items->sortByDesc(fn (ServiceSlotRuleItem $item) => $item->service_id !== null ? 1 : 0);
        $remaining = $serviceModels->values()->all();

        foreach ($sortedItems as $item) {
            $matchedIndex = null;
            foreach ($remaining as $index => $service) {
                if ($this->serviceMatchesRuleItem($item, $service)) {
                    $matchedIndex = $index;
                    break;
                }
            }

            if ($matchedIndex === null) {
                return false;
            }

            unset($remaining[$matchedIndex]);
        }

        return true;
    }

    private function serviceMatchesRuleItem(ServiceSlotRuleItem $item, Service $service): bool
    {
        if ($item->service_id !== null) {
            return (int) $service->id === (int) $item->service_id;
        }

        if ($item->category !== null && $item->category !== '') {
            return strtolower((string) $service->category) === strtolower((string) $item->category);
        }

        return false;
    }

    private function normalizeServices(Collection|array $services): Collection
    {
        return collect($services)
            ->map(function ($item) {
                if ($item instanceof Service) {
                    return $item;
                }
                if (is_array($item) && isset($item['service']) && $item['service'] instanceof Service) {
                    return $item['service'];
                }
                return null;
            })
            ->filter()
            ->values();
    }

    private function buildEvaluationResult(Collection $matchedRules, string $customerName): array
    {
        if ($matchedRules->isEmpty()) {
            return $this->defaultResult();
        }

        $slotsRequired = max(1, (int) $matchedRules->sum('slots_required'));
        $extraSlots = max(0, $slotsRequired - 1);
        $displayLabel = $matchedRules->pluck('name')->implode(' + ');
        $reason = $this->resolveReason($matchedRules, $displayLabel);

        $cleanName = trim($customerName);
        $notes = $cleanName !== ''
            ? "Lanjutan {$displayLabel} untuk {$cleanName}"
            : "Lanjutan {$displayLabel}";

        return [
            'requires_multi_slot' => $extraSlots > 0,
            'slots_required'      => $slotsRequired,
            'extra_slots'         => $extraSlots,
            'matched_rules'       => $matchedRules,
            'reason'              => $reason,
            'display_label'       => $displayLabel,
            'notes'               => $notes,
        ];
    }

    private function resolveReason(Collection $matchedRules, string $displayLabel): string
    {
        if ($matchedRules->count() === 1) {
            $rule = $matchedRules->first();
            $hasChemicalCategory = $rule->items->contains(
                fn (ServiceSlotRuleItem $item) => strtolower((string) $item->category) === 'chemical'
            );

            if ($hasChemicalCategory) {
                return BlockedSlot::REASON_CHEMICAL;
            }
        }

        return $displayLabel !== '' ? $displayLabel : BlockedSlot::REASON_CUSTOM_RULE;
    }

    private function defaultResult(): array
    {
        return [
            'requires_multi_slot' => false,
            'slots_required'      => 1,
            'extra_slots'         => 0,
            'matched_rules'       => collect(),
            'reason'              => '',
            'display_label'       => '',
            'notes'               => '',
        ];
    }
}
