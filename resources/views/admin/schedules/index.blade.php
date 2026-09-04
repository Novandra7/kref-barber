@extends('admin.layouts.app')

@section('title', 'Schedule Management')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary">Management</p>
                <h1 class="font-league text-4xl uppercase text-gray-900">Weekly Schedules</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $weekStart->format('d M Y') }} - {{ $weekEnd->format('d M Y') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="date" name="week" value="{{ $weekStart->toDateString() }}"
                           class="rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                    <select name="role" class="rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <select name="barber" class="rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                        <option value="">All barbers</option>
                        @foreach ($allBarbers as $filterBarber)
                            <option value="{{ $filterBarber->id }}" @selected((string) $selectedBarber === (string) $filterBarber->id)>
                                {{ $filterBarber->name }}
                            </option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Filter</button>
                </form>
                <a href="{{ route('admin.schedules.index', ['week' => $weekStart->subWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                   class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Previous</a>
                <a href="{{ route('admin.schedules.index', ['week' => $weekStart->addWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                   class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Next</a>
                <button type="button" data-modal-target="bulk-schedule-modal" data-modal-toggle="bulk-schedule-modal"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Bulk set</button>
                <form method="POST" action="{{ route('admin.schedules.copy-previous-week') }}">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    <button class="rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5">Copy previous</button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="min-w-[800px]">
                <div class="grid grid-cols-[220px_repeat(7,minmax(110px,1fr))] border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500">
                    <div class="p-4">Barber</div>
                    @foreach ($days as $day)
                        <div class="border-l border-gray-200 p-4 text-center">
                            <div>{{ $day->format('D') }}</div>
                            <div class="mt-1 text-sm text-gray-900">{{ $day->format('d M') }}</div>
                        </div>
                    @endforeach
                </div>

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
                            @php($daySchedules = $barber->schedules->filter(fn ($schedule) => $schedule->date->isSameDay($day)))
                            <div class="group border-l border-gray-100 p-2">
                                <div class="space-y-1">
                                    @forelse ($daySchedules as $schedule)
                                        <div class="flex items-center justify-between gap-1 rounded-lg px-2 py-1.5 text-xs {{ $schedule->is_available ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500 line-through' }}">
                                            <button type="button" data-modal-target="schedule-modal" data-modal-toggle="schedule-modal"
                                                    data-barber-id="{{ $barber->id }}" data-date="{{ $day->toDateString() }}"
                                                    data-slot-time="{{ $schedule->slot_time->format('H:i') }}" class="hover:underline">
                                            <span>{{ $schedule->slot_time->format('H:i') }}</span>
                                            </button>
                                            <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule slot?')">
                                                @csrf @method('DELETE')
                                                <button class="font-bold hover:text-red-600" aria-label="Delete schedule">&times;</button>
                                            </form>
                                        </div>
                                    @empty
                                        <span class="block rounded-lg bg-red-50 px-2 py-1.5 text-center text-xs font-semibold text-red-600">OFF</span>
                                    @endforelse
                                </div>
                                <button type="button" data-modal-target="schedule-modal" data-modal-toggle="schedule-modal"
                                        data-barber-id="{{ $barber->id }}" data-date="{{ $day->toDateString() }}"
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

    <div id="schedule-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
        <div class="relative max-h-full w-full max-w-md p-4">
            <div class="relative rounded-lg bg-white shadow">
                <div class="flex items-center justify-between border-b p-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit schedule slot</h3>
                    <button type="button" data-modal-hide="schedule-modal" class="text-2xl text-gray-400">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.schedules.store') }}" class="space-y-4 p-4">
                    @csrf
                    <input type="hidden" name="barber_id" id="schedule-barber-id">
                    <input type="hidden" name="date" id="schedule-date">
                    <label class="block text-sm font-medium text-gray-700">Time
                        <input type="time" name="slot_time" id="schedule-slot-time" required class="mt-1 block w-full rounded-lg border-gray-300">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_available" value="0">
                        <input type="checkbox" name="is_available" value="1" checked class="rounded border-gray-300 text-primary focus:ring-primary">
                        Available
                    </label>
                    <button class="w-full rounded-lg bg-primary px-4 py-2 font-semibold text-white">Save slot</button>
                </form>
            </div>
        </div>
    </div>

    <div id="bulk-schedule-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
        <div class="relative max-h-full w-full max-w-md p-4">
            <div class="relative rounded-lg bg-white shadow">
                <div class="flex items-center justify-between border-b p-4">
                    <h3 class="text-lg font-semibold text-gray-900">Bulk set schedule</h3>
                    <button type="button" data-modal-hide="bulk-schedule-modal" class="text-2xl text-gray-400">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.schedules.bulk') }}" class="space-y-4 p-4">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    <select name="barber_id" required class="block w-full rounded-lg border-gray-300">
                        <option value="">Select barber</option>
                        @foreach ($barbers as $barber)
                            <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                        @endforeach
                    </select>
                    <input type="time" name="slot_time" required class="block w-full rounded-lg border-gray-300">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        @foreach ($days as $index => $day)
                            <label class="flex items-center gap-2"><input type="checkbox" name="days[]" value="{{ $index }}" class="rounded border-gray-300 text-primary"> {{ $day->format('D d M') }}</label>
                        @endforeach
                    </div>
                    <input type="hidden" name="is_available" value="1">
                    <button class="w-full rounded-lg bg-primary px-4 py-2 font-semibold text-white">Save bulk schedule</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-barber-id][data-date]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('schedule-barber-id').value = button.dataset.barberId;
                document.getElementById('schedule-date').value = button.dataset.date;
                document.getElementById('schedule-slot-time').value = button.dataset.slotTime || '';
            });
        });
    </script>
@endpush
