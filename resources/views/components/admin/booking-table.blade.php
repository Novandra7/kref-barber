@props([
    'rows' => collect(),
    'bookings' => null,
    'columns' => [],
    'title' => 'Recent Bookings',
    'emptyMessage' => 'No data found.',
])

@php
    $rows = $bookings ?? $rows;
    $rows = $rows instanceof \Illuminate\Support\Collection ? $rows : collect($rows);
    $tableId = 'admin-table-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
    $columns = $columns ?: [
        ['key' => 'walk_in_customer_name', 'label' => 'Customer', 'class' => 'font-semibold text-gray-900'],
        ['key' => 'barber.name', 'label' => 'Barber'],
        ['key' => 'scheduled_at', 'label' => 'Schedule', 'type' => 'datetime'],
        ['key' => 'total_amount', 'label' => 'Amount', 'type' => 'currency'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        [
            'key' => 'id',
            'label' => '',
            'type' => 'modal',
            'text' => 'View',
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    <div class="border-b border-gray-200 px-5 py-4">
        <h3 class="font-montserrat font-bold text-gray-900">{{ $title }}</h3>
    </div>

    <div class="relative overflow-x-auto">
        <table id="{{ $tableId }}" class="w-full text-left text-sm text-gray-500">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" class="whitespace-nowrap px-6 py-3 {{ $column['header_class'] ?? '' }}">
                            {{ $column['label'] ?? '' }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($rows as $rowIndex => $row)
                    <tr class="bg-white hover:bg-gray-50">
                        @foreach ($columns as $column)
                            @php
                                $value = data_get($row, $column['key'] ?? '');
                                $type = $column['type'] ?? 'text';
                            @endphp
                            <td class="whitespace-nowrap px-6 py-4 {{ $column['class'] ?? '' }}">
                                @if ($type === 'status')
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ str_replace('_', ' ', ucfirst($value ?: '-')) }}
                                    </span>
                                @elseif ($type === 'currency')
                                    {{ $value === null ? '-' : 'Rp ' . number_format($value, 0, ',', '.') }}
                                @elseif ($type === 'datetime')
                                    {{ $value?->format($column['format'] ?? 'd M Y H:i') ?? '-' }}
                                @elseif ($type === 'link')
                                    <a
                                        href="{{ route($column['route'], [$column['route_param'] ?? 'id' => $value]) }}"
                                        class="font-medium text-primary hover:underline"
                                    >
                                        {{ $column['text'] ?? 'View' }}
                                    </a>
                                @elseif ($type === 'modal')
                                    <button
                                        type="button"
                                        data-modal-target="{{ $tableId }}-modal-{{ $rowIndex }}"
                                        data-modal-toggle="{{ $tableId }}-modal-{{ $rowIndex }}"
                                        class="font-medium text-primary hover:underline"
                                    >
                                        {{ $column['text'] ?? 'View' }}
                                    </button>
                                @else
                                    {{ $value ?? ($column['empty'] ?? '-') }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-6 py-8 text-center">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach ($rows as $rowIndex => $row)
    <div
        id="{{ $tableId }}-modal-{{ $rowIndex }}"
        tabindex="-1"
        aria-hidden="true"
        class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0"
    >
        <div class="relative max-h-full w-full max-w-2xl">
            <div class="relative rounded-lg bg-white shadow">
                <div class="flex items-center justify-between rounded-t border-b border-gray-200 p-4 md:p-5">
                    <h3 class="font-montserrat text-xl font-semibold text-gray-900">
                        {{ $title }} Detail
                    </h3>
                    <button
                        type="button"
                        data-modal-hide="{{ $tableId }}-modal-{{ $rowIndex }}"
                        class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900"
                        aria-label="Close modal"
                    >
                        <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4 p-4 md:p-5">
                    @foreach ($columns as $column)
                        @if (($column['type'] ?? 'text') !== 'modal')
                            @php
                                $value = data_get($row, $column['key'] ?? '');
                                $type = $column['type'] ?? 'text';
                            @endphp
                            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 text-sm last:border-b-0">
                                <span class="font-medium text-gray-500">{{ $column['label'] ?? '' }}</span>
                                <span class="text-right font-semibold text-gray-900">
                                    @if ($type === 'status')
                                        {{ str_replace('_', ' ', ucfirst($value ?: '-')) }}
                                    @elseif ($type === 'currency')
                                        {{ $value === null ? '-' : 'Rp ' . number_format($value, 0, ',', '.') }}
                                    @elseif ($type === 'datetime')
                                        {{ $value?->format($column['format'] ?? 'd M Y H:i') ?? '-' }}
                                    @else
                                        {{ $value ?? ($column['empty'] ?? '-') }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endforeach
