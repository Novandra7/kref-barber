@extends('admin.layouts.app')

@section('title', 'Schedule Management')

@section('content')
    <div class="space-y-6">
        <!-- Banner Header Container Retro -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                <div>
                    <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                        Management
                    </span>
                    <h1 class="mt-1 font-league text-4xl font-black uppercase text-gray-900">Weekly Schedules</h1>
                    <p class="mt-1 text-xs font-bold text-gray-500">
                        {{ $weekStart->format('d M Y') }} - {{ $weekEnd->format('d M Y') }}
                    </p>
                </div>

                <!-- Action Controls Retro -->
                <div class="flex flex-wrap gap-2">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input type="date" name="week" value="{{ $weekStart->toDateString() }}"
                               onchange="this.form.submit()"
                               class="rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:border-brand focus:ring-0">
                        
                        <select name="role" onchange="this.form.submit()"
                                class="rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:border-brand focus:ring-0">
                            <option value="">All roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                            @endforeach
                        </select>

                        <select name="barber" onchange="this.form.submit()"
                                class="rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:border-brand focus:ring-0">
                            <option value="">All barbers</option>
                            @foreach ($allBarbers as $filterBarber)
                                <option value="{{ $filterBarber->id }}" @selected((string) $selectedBarber === (string) $filterBarber->id)>
                                    {{ $filterBarber->name }}
                                </option>
                            @endforeach
                        </select>

                        <a href="{{ route('admin.schedules.index', ['week' => $weekStart->toDateString()]) }}"
                           class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-700 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-gray-100 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                            Reset filter
                        </a>
                    </form>

                    <a href="{{ route('admin.schedules.index', ['week' => $weekStart->subWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                       class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-300 active:translate-x-0 active:translate-y-0 active:shadow-none">Previous</a>

                    <a href="{{ route('admin.schedules.index', ['week' => $weekStart->addWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                       class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-300 active:translate-x-0 active:translate-y-0 active:shadow-none">Next</a>

                    <button type="button" data-modal-target="bulk-schedule-modal" data-modal-toggle="bulk-schedule-modal"
                            class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-brand px-4 py-2 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">Bulk set</button>

                    <form method="POST" action="{{ route('admin.schedules.copy-previous-week') }}" class="inline">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <button class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-amber-300 px-4 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">Copy previous</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Flash Alerts Retro -->
        @if (session('success'))
            <div class="rounded-xl border-2 border-gray-900 bg-green-100 p-4 text-xs font-bold text-green-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border-2 border-gray-900 bg-red-100 p-4 text-xs font-bold text-red-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border-2 border-gray-900 bg-red-100 p-4 text-xs font-bold text-red-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <!-- Grid Matrix Container Retro -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="overflow-x-auto">
                <div class="min-w-[990px]">
                    <!-- Header Row -->
                    <div class="grid grid-cols-[220px_repeat(7,minmax(110px,1fr))] border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500">
                        <div class="p-4 flex items-center font-bold text-gray-900">Barber</div>
                        @foreach ($days as $day)
                            <div class="border-l border-gray-200 p-4 text-center">
                                <div>{{ $day->format('D') }}</div>
                                <div class="mt-1 text-sm text-gray-900">{{ $day->format('d M') }}</div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Rows Barber & Schedules -->
                    @forelse ($barbers as $barber)
                        <div class="grid grid-cols-[220px_repeat(7,minmax(110px,1fr))] border-b border-gray-100 last:border-0">
                            <div class="flex items-center gap-3 p-4">
                                @if ($barber->photo)
                                    <img src="{{ asset('storage/' . $barber->photo) }}" class="h-10 w-10 rounded-full object-cover" alt="{{ $barber->name }}">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">
                                        {{ strtoupper(substr($barber->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $barber->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $barber->role }}</div>
                                </div>
                            </div>
                            @foreach ($days as $day)
                                @php
                                    $daySchedules = $barber->schedules->filter(
                                        fn ($schedule) => $schedule->date->isSameDay($day)
                                    );
                                @endphp
                                <div class="group border-l border-gray-100 p-2">
                                    <div class="space-y-1">
                                        @forelse ($daySchedules as $schedule)
                                            @php
                                                $booking = $schedule->bookings->first();
                                            @endphp
                                            <div class="group/slot relative" x-data="{ tooltipOpen: false }" @click.outside="tooltipOpen = false">
                                                @if ($schedule->is_available)
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-green-50 px-2 py-1.5 text-xs text-green-700">
                                                        <button type="button" data-modal-target="schedule-modal" data-modal-toggle="schedule-modal"
                                                                data-barber-id="{{ $barber->id }}" data-date="{{ $day->toDateString() }}"
                                                                data-slot-time="{{ $schedule->slot_time->format('H:i') }}"
                                                                data-schedule-id="{{ $schedule->id }}"
                                                                data-update-url="{{ route('admin.schedules.update', $schedule) }}"
                                                                class="hover:underline">
                                                            {{ $schedule->slot_time->format('H:i') }}
                                                        </button>
                                                        <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule slot?')">
                                                            @csrf @method('DELETE')
                                                            <button class="font-bold hover:text-red-600" aria-label="Delete schedule">&times;</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <div class="flex items-center justify-between gap-1 rounded-lg bg-gray-100 px-2 py-1.5 text-xs text-gray-500">
                                                        <span>{{ $schedule->slot_time->format('H:i') }}</span>
                                                        @if ($booking?->payment_type === 'dp')
                                                            <button type="button" @click.stop="tooltipOpen = !tooltipOpen" class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700">
                                                                DP
                                                            </button>
                                                        @elseif ($booking?->payment_type === 'full')
                                                            <button type="button" @click.stop="tooltipOpen = !tooltipOpen" class="rounded-full bg-blue-100 px-2 py-0.5 font-semibold text-blue-700">
                                                                FULL
                                                            </button>
                                                        @else
                                                            <button type="button" @click.stop="tooltipOpen = !tooltipOpen" class="rounded-full bg-gray-200 px-2 py-0.5 font-semibold text-gray-600">
                                                                BOOKED
                                                            </button>
                                                        @endif
                                                    </div>
                                                    @if ($booking)
                                                        <div x-show="tooltipOpen" x-cloak
                                                             class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden w-full -translate-x-1/2 rounded-lg bg-gray-900 px-3 py-2 text-left text-xs text-white shadow-lg md:!block md:opacity-0 md:group-hover/slot:opacity-100">
                                                            <p class="font-semibold">{{ $booking->name ?? 'Customer' }}</p>
                                                            <p class="mt-1 text-gray-300">{{ $booking->phone ?? 'Phone unavailable' }}</p>
                                                            <p class="mt-2 font-semibold text-gray-300">
                                                                Services
                                                                <span class="ml-1 rounded-full px-1.5 py-0.5 {{ $booking->payment_type === 'dp' ? 'bg-amber-400/20 text-amber-200' : 'bg-blue-400/20 text-blue-200' }}">
                                                                    {{ strtoupper($booking->payment_type ?? 'booked') }}
                                                                </span>
                                                            </p>
                                                            <ul class="mt-1 space-y-0.5 text-gray-300">
                                                                @forelse ($booking->items->where('item_type', 'service') as $item)
                                                                    <li>
                                                                        {{ $item->service_name_snapshot ?? 'Service' }}
                                                                        @if ($item->qty > 1)
                                                                            x{{ $item->qty }}
                                                                        @endif
                                                                    </li>
                                                                @empty
                                                                    <li>Services unavailable</li>
                                                                @endforelse
                                                            </ul>
                                                            <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900"></span>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @empty
                                            <span class="block rounded-lg bg-red-50 px-2 py-1.5 text-center text-xs font-semibold text-red-600">OFF</span>
                                        @endforelse
                                    </div>
                                    <button type="button" data-modal-target="schedule-modal" data-modal-toggle="schedule-modal"
                                            data-barber-id="{{ $barber->id }}" data-date="{{ $day->toDateString() }}"
                                            data-create-schedule
                                            class="mt-2 w-full rounded-lg border border-dashed border-gray-300 py-1 text-xs text-gray-400 opacity-0 transition group-hover:opacity-100 hover:border-primary hover:text-primary">
                                        + slot
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="p-10 text-center text-sm text-gray-500">No active barbers match the selected filters.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Single Slot Retro -->
    <div id="schedule-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-xs">
        <div class="relative w-full max-w-md">
            <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-5 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                <div class="flex items-center justify-between border-b-2 border-gray-900 pb-3">
                    <h3 id="schedule-modal-title" class="font-league text-2xl font-black uppercase text-gray-900">Add schedule slot</h3>
                    <button type="button" data-modal-hide="schedule-modal" class="rounded-lg border-2 border-gray-900 bg-white px-2 py-0.5 font-black">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.schedules.store') }}" id="schedule-form" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="schedule-form-method" value="">
                    <input type="hidden" name="barber_id" id="schedule-barber-id">
                    <input type="hidden" name="date" id="schedule-date">
                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-gray-700">Time</label>
                        <input type="time" name="slot_time" id="schedule-slot-time" required class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    </div>
                    <button id="schedule-submit" class="w-full rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:shadow-none">Save slot</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Schedule Retro -->
    <div id="bulk-schedule-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-xs">
        <div class="relative w-full max-w-md">
            <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-5 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                <div class="flex items-center justify-between border-b-2 border-gray-900 pb-3">
                    <h3 class="font-league text-2xl font-black uppercase text-gray-900">Bulk set schedule</h3>
                    <button type="button" data-modal-hide="bulk-schedule-modal" class="rounded-lg border-2 border-gray-900 bg-white px-2 py-0.5 font-black">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.schedules.bulk') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-gray-700">Select Barber</label>
                        <select name="barber_id" required class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                            <option value="">Select barber</option>
                            @foreach ($barbers as $barber)
                                <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-gray-700">Slot Time</label>
                        <input type="time" name="slot_time" required class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-gray-700">Apply Days</label>
                        <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                            @foreach ($days as $index => $day)
                                <label class="flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-white p-2 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                    <input type="checkbox" name="days[]" value="{{ $index }}" class="rounded-md border-2 border-gray-900 text-brand focus:ring-0"> {{ $day->format('D d M') }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="is_available" value="1">
                    <button class="w-full rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:shadow-none">Save bulk schedule</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-barber-id][data-date]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = document.getElementById('schedule-form');
                const isEdit = Boolean(button.dataset.scheduleId);

                form.action = isEdit
                    ? button.dataset.updateUrl
                    : @json(route('admin.schedules.store'));
                document.getElementById('schedule-form-method').value = isEdit ? 'PATCH' : '';
                document.getElementById('schedule-modal-title').textContent = isEdit
                    ? 'Edit schedule slot'
                    : 'Add schedule slot';
                document.getElementById('schedule-submit').textContent = isEdit
                    ? 'Update slot'
                    : 'Save slot';
                document.getElementById('schedule-barber-id').value = button.dataset.barberId;
                document.getElementById('schedule-date').value = button.dataset.date;
                document.getElementById('schedule-slot-time').value = button.dataset.slotTime || '';
            });
        });
    </script>
@endpush