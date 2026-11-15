@extends('admin.layouts.app')

@section('title', 'Time Rules')
@section('header', 'Rules / Time Rules')

@section('content')
    @php
        $oldItems = old('items', []);
        $oldEditingId = old('_editing_rule_id') !== null ? (int) old('_editing_rule_id') : null;
        $hasOldCreate = old('slots_required') !== null && $oldEditingId === null;

        $rulesMap = $rules->mapWithKeys(function ($rule) {
            $ruleItems = $rule->items->map(fn ($item) => $item->target_key)->filter()->values()->all();
            if (empty($ruleItems)) {
                $ruleItems = [''];
            }

            return [
                (string) $rule->id => [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'description' => (string) ($rule->description ?? ''),
                    'barber_id' => $rule->barber_id ? (string) $rule->barber_id : '',
                    'items' => $ruleItems,
                    'slots_required' => (int) $rule->slots_required,
                    'is_active' => (bool) $rule->is_active,
                ],
            ];
        })->all();

        $initialTimeRuleState = [
            'isAdding' => $hasOldCreate,
            'editingId' => $oldEditingId,
            'rulesMap' => $rulesMap,
            'newForm' => [
                'name' => $oldEditingId === null ? (string) old('name', '') : '',
                'description' => $oldEditingId === null ? (string) old('description', '') : '',
                'barber_id' => $oldEditingId === null ? (string) old('barber_id', '') : '',
                'items' => (! $oldEditingId && is_array($oldItems) && count($oldItems) > 0) ? array_values($oldItems) : [''],
                'slots_required' => ! $oldEditingId ? (int) old('slots_required', 2) : 2,
                'is_active' => ! $oldEditingId ? ($hasOldCreate ? (bool) old('is_active') : true) : true,
            ],
            'editForm' => [
                'name' => $oldEditingId !== null ? (string) old('name', '') : '',
                'description' => $oldEditingId !== null ? (string) old('description', '') : '',
                'barber_id' => $oldEditingId !== null ? (string) old('barber_id', '') : '',
                'items' => ($oldEditingId && is_array($oldItems) && count($oldItems) > 0) ? array_values($oldItems) : [''],
                'slots_required' => $oldEditingId ? (int) old('slots_required', 2) : 2,
                'is_active' => $oldEditingId ? (bool) old('is_active') : true,
            ],
        ];
    @endphp

    <div x-data="timeRuleTable(@js($initialTimeRuleState))" class="space-y-6">
        <!-- Header Banner Retro -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div>
                <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                    Rules &rsaquo; Time Rules
                </span>
                <h2 class="mt-1 font-league text-4xl font-black uppercase text-gray-900">Time Rules</h2>
                <p class="mt-1 text-xs font-semibold text-gray-500">
                    Kombinasikan barber tertentu dengan satu atau beberapa layanan/kategori untuk menyesuaikan jumlah slot waktu yang dikunci.
                </p>
            </div>

            <!-- Info Akumulasi SUM -->
            <div class="mt-4 rounded-xl border-2 border-gray-900 bg-amber-50 p-3 text-xs font-semibold text-gray-800">
                <span class="font-black uppercase text-amber-900">Info Cara Kerja &amp; Akumulasi:</span>
                Aturan aktif ketika seluruh layanan/kategori yang Anda kombinasikan di rule tersebut terpilih dalam 1 booking.
                Jika beberapa aturan cocok sekaligus (misal <em>Long Trim (Sogi) [2 slot]</em> + <em>Chemical + Haircut [2 slot]</em>),
                total slot dijumlahkan (<strong>2 + 2 = 4 slot</strong> berurutan).
            </div>
        </div>

        <!-- Flash Alerts -->
        @if (session('success'))
            <div class="rounded-xl border-2 border-gray-900 bg-green-100 p-4 text-xs font-bold text-green-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border-2 border-gray-900 bg-red-100 p-4 text-xs font-bold text-red-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <!-- Hidden Form Hosts (menjaga validitas DOM <table> tanpa modal) -->
        <form id="create-rule-form" action="{{ route('admin.rules.time.store') }}" method="POST" class="hidden">
            @csrf
        </form>
        @foreach ($rules as $rule)
            <form id="edit-rule-form-{{ $rule->id }}" action="{{ route('admin.rules.time.update', $rule) }}" method="POST" class="hidden">
                @csrf
                @method('PUT')
                <input type="hidden" name="_editing_rule_id" value="{{ $rule->id }}">
            </form>
        @endforeach

        <!-- Table Container Retro -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="border-b-2 border-gray-900 bg-gray-50 text-xs font-black uppercase text-gray-700">
                        <tr>
                            <th scope="col" class="px-5 py-3">Nama Rule</th>
                            <th scope="col" class="px-5 py-3">Barber</th>
                            <th scope="col" class="px-5 py-3">Kombinasi Layanan</th>
                            <th scope="col" class="px-5 py-3">Jumlah Slot</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($rules as $rule)
                            <!-- Baris Normal -->
                            <tr x-show="!isEditing({{ $rule->id }})" class="bg-white hover:bg-amber-50/40">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-gray-900">{{ $rule->name }}</div>
                                    @if ($rule->description)
                                        <div class="mt-0.5 max-w-xs truncate text-xs text-gray-500" title="{{ $rule->description }}">{{ $rule->description }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($rule->barber)
                                        <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-blue-100 px-2.5 py-0.5 text-2xs font-black uppercase text-gray-900">
                                            {{ $rule->barber->name }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-gray-100 px-2.5 py-0.5 text-2xs font-black uppercase text-gray-700">
                                            Semua Barber
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach ($rule->items as $idx => $item)
                                            @if ($idx > 0)
                                                <span class="text-xs font-black text-gray-400">+</span>
                                            @endif
                                            <span class="inline-flex items-center rounded-lg border-2 border-gray-900 px-2.5 py-0.5 text-2xs font-black {{ $item->service_id ? 'bg-amber-100 text-amber-900' : 'bg-purple-100 text-purple-900' }}">
                                                {{ $item->display_label }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-brand/15 px-3 py-1 text-xs font-black text-brand">
                                        {{ $rule->slots_required }} Slot
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <form action="{{ route('admin.rules.time.toggle', $rule) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                title="Klik untuk ubah status"
                                                class="inline-flex cursor-pointer items-center rounded-lg border-2 border-gray-900 px-2.5 py-0.5 text-2xs font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] transition-transform active:translate-y-0.5 {{ $rule->is_active ? 'bg-green-300 text-gray-900 hover:bg-green-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="startEdit({{ $rule->id }})"
                                            class="rounded-lg p-2 text-gray-500 hover:text-brand focus:outline-none"
                                            title="Edit Inline"
                                        >
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-3M14.707 3.293a1 1 0 0 1 1.414 0l1.586 1.586a1 1 0 0 1 0 1.414l-9 9a1 1 0 0 1-.39.242l-3 1a1 1 0 0 1-1.266-1.265l1-3a1 1 0 0 1 .242-.391l9-9z"/>
                                            </svg>
                                        </button>
                                        <form action="{{ route('admin.rules.time.destroy', $rule) }}" method="POST" class="inline" onsubmit="return confirm('Hapus time rule ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-gray-500 hover:text-red-600 focus:outline-none" title="Delete Rule">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- Baris Edit Inline -->
                            <tr x-show="isEditing({{ $rule->id }})" x-cloak class="bg-amber-50">
                                <td class="px-5 py-3 align-top">
                                    <div class="space-y-2">
                                        <input type="text"
                                               form="edit-rule-form-{{ $rule->id }}"
                                               name="name"
                                               x-model="editForm.name"
                                               placeholder="Otomatis jika dikosongkan"
                                               class="w-full min-w-[180px] rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-bold text-gray-900 focus:border-brand focus:outline-none">
                                        <textarea form="edit-rule-form-{{ $rule->id }}"
                                                  name="description"
                                                  x-model="editForm.description"
                                                  rows="2"
                                                  maxlength="1000"
                                                  placeholder="Deskripsi rule (opsional)"
                                                  class="w-full min-w-[180px] rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-semibold text-gray-700 placeholder:text-gray-400 focus:border-brand focus:outline-none"></textarea>
                                    </div>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <select form="edit-rule-form-{{ $rule->id }}"
                                            name="barber_id"
                                            x-model="editForm.barber_id"
                                            class="w-full min-w-[140px] rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-bold text-gray-900 focus:border-brand focus:outline-none">
                                        <option value="">Semua Barber</option>
                                        @foreach ($barbers as $barber)
                                            <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <div class="space-y-2">
                                        <template x-for="(itemVal, idx) in editForm.items" :key="idx">
                                            <div class="flex items-center gap-1.5">
                                                <select form="edit-rule-form-{{ $rule->id }}"
                                                        name="items[]"
                                                        x-model="editForm.items[idx]"
                                                        required
                                                        class="w-full min-w-[200px] rounded-xl border-2 border-gray-900 bg-white px-2.5 py-2 text-xs font-bold text-gray-900 focus:border-brand focus:outline-none">
                                                    <option value="">-- Pilih Layanan / Kategori --</option>
                                                    <optgroup label="Kategori Layanan">
                                                        @foreach ($categories as $category)
                                                            <option value="category:{{ $category }}">Kategori: {{ ucfirst($category) }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Layanan Spesifik">
                                                        @foreach ($services as $service)
                                                            <option value="service:{{ $service->id }}">{{ $service->name }} ({{ $service->category }})</option>
                                                        @endforeach
                                                    </optgroup>
                                                </select>
                                                <button type="button"
                                                        x-show="editForm.items.length > 1"
                                                        @click="editForm.items.splice(idx, 1)"
                                                        class="rounded-lg border-2 border-gray-900 bg-red-100 px-2 py-1.5 text-xs font-black text-red-700 hover:bg-red-200"
                                                        title="Hapus item">
                                                    ✕
                                                </button>
                                            </div>
                                        </template>
                                        <button type="button"
                                                @click="editForm.items.push('')"
                                                class="inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-900 bg-white px-2.5 py-1 text-[11px] font-black uppercase text-gray-700 hover:bg-amber-100">
                                            + Tambah Kombinasi Layanan
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <input type="number"
                                           form="edit-rule-form-{{ $rule->id }}"
                                           name="slots_required"
                                           x-model="editForm.slots_required"
                                           min="1"
                                           max="10"
                                           required
                                           class="w-20 rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black text-gray-900 focus:border-brand focus:outline-none">
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <label class="inline-flex cursor-pointer items-center gap-2 pt-1.5">
                                        <input type="checkbox"
                                               form="edit-rule-form-{{ $rule->id }}"
                                               name="is_active"
                                               value="1"
                                               x-model="editForm.is_active"
                                               class="h-4 w-4 rounded border-2 border-gray-900 text-brand focus:ring-0">
                                        <span class="text-xs font-black uppercase text-gray-800">Aktif</span>
                                    </label>
                                </td>
                                <td class="px-5 py-3 align-top text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <button type="submit"
                                                form="edit-rule-form-{{ $rule->id }}"
                                                class="rounded-xl border-2 border-gray-900 bg-emerald-400 px-3 py-1.5 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-emerald-500">
                                            Simpan
                                        </button>
                                        <button type="button"
                                                @click="cancelEdit()"
                                                class="rounded-xl border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-black uppercase text-gray-700 hover:bg-gray-100">
                                            Batal
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr x-show="!isAdding">
                                <td colspan="6" class="px-6 py-10 text-center text-xs font-bold uppercase text-gray-400">
                                    Belum ada Time Rule. Klik tombol "Tambah Time Rule" di bawah tabel untuk membuat aturan baru.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Template Row Tambah Rule Langsung di Tabel (Tanpa Modal) -->
                        <tr x-show="isAdding" x-cloak class="border-t-2 border-gray-900 bg-emerald-50/70">
                            <td class="px-5 py-3 align-top">
                                <div class="space-y-2">
                                    <input type="text"
                                           form="create-rule-form"
                                           name="name"
                                           x-model="newForm.name"
                                           placeholder="Nama rule (opsional)"
                                           class="w-full min-w-[180px] rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-bold text-gray-900 placeholder:text-gray-400 focus:border-brand focus:outline-none">
                                    <textarea form="create-rule-form"
                                              name="description"
                                              x-model="newForm.description"
                                              rows="2"
                                              maxlength="1000"
                                              placeholder="Deskripsi rule (opsional)"
                                              class="w-full min-w-[180px] rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-semibold text-gray-700 placeholder:text-gray-400 focus:border-brand focus:outline-none"></textarea>
                                </div>
                            </td>
                            <td class="px-5 py-3 align-top">
                                <select form="create-rule-form"
                                        name="barber_id"
                                        x-model="newForm.barber_id"
                                        class="w-full min-w-[140px] rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-bold text-gray-900 focus:border-brand focus:outline-none">
                                    <option value="">Semua Barber</option>
                                    @foreach ($barbers as $barber)
                                        <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-3 align-top">
                                <div class="space-y-2">
                                    <template x-for="(itemVal, idx) in newForm.items" :key="idx">
                                        <div class="flex items-center gap-1.5">
                                            <select form="create-rule-form"
                                                    name="items[]"
                                                    x-model="newForm.items[idx]"
                                                    :required="isAdding"
                                                    class="w-full min-w-[200px] rounded-xl border-2 border-gray-900 bg-white px-2.5 py-2 text-xs font-bold text-gray-900 focus:border-brand focus:outline-none">
                                                <option value="">-- Pilih Layanan / Kategori --</option>
                                                <optgroup label="Kategori Layanan">
                                                    @foreach ($categories as $category)
                                                        <option value="category:{{ $category }}">Kategori: {{ ucfirst($category) }}</option>
                                                    @endforeach
                                                </optgroup>
                                                <optgroup label="Layanan Spesifik">
                                                    @foreach ($services as $service)
                                                        <option value="service:{{ $service->id }}">{{ $service->name }} ({{ $service->category }})</option>
                                                    @endforeach
                                                </optgroup>
                                            </select>
                                            <button type="button"
                                                    x-show="newForm.items.length > 1"
                                                    @click="newForm.items.splice(idx, 1)"
                                                    class="rounded-lg border-2 border-gray-900 bg-red-100 px-2 py-1.5 text-xs font-black text-red-700 hover:bg-red-200"
                                                    title="Hapus item">
                                                ✕
                                            </button>
                                        </div>
                                    </template>
                                    <button type="button"
                                            @click="newForm.items.push('')"
                                            class="inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-900 bg-white px-2.5 py-1 text-[11px] font-black uppercase text-gray-700 hover:bg-amber-100">
                                        + Tambah Kombinasi Layanan
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-3 align-top">
                                <input type="number"
                                       form="create-rule-form"
                                       name="slots_required"
                                       x-model="newForm.slots_required"
                                       min="1"
                                       max="10"
                                       :required="isAdding"
                                       class="w-20 rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black text-gray-900 focus:border-brand focus:outline-none">
                            </td>
                            <td class="px-5 py-3 align-top">
                                <label class="inline-flex cursor-pointer items-center gap-2 pt-1.5">
                                    <input type="checkbox"
                                           form="create-rule-form"
                                           name="is_active"
                                           value="1"
                                           x-model="newForm.is_active"
                                           class="h-4 w-4 rounded border-2 border-gray-900 text-brand focus:ring-0">
                                    <span class="text-xs font-black uppercase text-gray-800">Aktif</span>
                                </label>
                            </td>
                            <td class="px-5 py-3 align-top text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button type="submit"
                                            form="create-rule-form"
                                            class="rounded-xl border-2 border-gray-900 bg-brand px-3 py-1.5 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:opacity-95">
                                        Simpan
                                    </button>
                                    <button type="button"
                                            @click="cancelAdd()"
                                            class="rounded-xl border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-black uppercase text-gray-700 hover:bg-gray-100">
                                        Batal
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Button Tambah di Bawah Table -->
        <div class="flex items-center justify-start">
            <button
                type="button"
                x-show="!isAdding"
                @click="startAdd()"
                class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none"
            >
                <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14m-7-7v14"/>
                </svg>
                Tambah Time Rule
            </button>
        </div>
    </div>
@endsection
