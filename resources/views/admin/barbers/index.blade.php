@extends('admin.layouts.app')

@section('title', 'Barbers')
@section('header', 'Barbers')

@section('content')
    <div class="space-y-6">
        <!-- Header Banner Container Retro -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                        Team
                    </span>
                    <h2 class="mt-1 font-league text-4xl font-black uppercase text-gray-900">Barbers</h2>
                    <p class="mt-1 text-xs font-semibold text-gray-500">Manage barber profiles and availability.</p>
                </div>

                <!-- Button Add Barber Neobrutalism -->
                <button 
                    type="button" 
                    data-modal-target="barber-modal" 
                    data-modal-toggle="barber-modal" 
                    onclick="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none"
                >
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14m-7-7v14"/>
                    </svg>
                    Add Barber
                </button>
            </div>
        </div>

        <!-- Flash Alerts Retro -->
        @if (session('success'))
            <div class="rounded-xl border-2 border-gray-900 bg-green-100 p-4 text-xs font-bold text-green-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ✅ {{ session('success') }}
            </div>
        @endif

        <!-- Table Outer Container Retro -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3">Barber</th>
                            <th scope="col" class="px-6 py-3">Role</th>
                            <th scope="col" class="px-6 py-3">Phone</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($barbers as $barber)
                            <tr class="bg-white hover:bg-gray-50">
                                <td class="flex items-center gap-3 px-6 py-4">
                                    @if ($barber->photo)
                                        <img src="{{ asset('storage/' . $barber->photo) }}" alt="{{ $barber->name }}" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-bold uppercase text-primary">
                                            {{ substr($barber->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <span class="font-semibold text-gray-900">{{ $barber->name }}</span>
                                </td>
                                <td class="px-6 py-4 capitalize">{{ $barber->role }}</td>
                                <td class="px-6 py-4 font-mono text-xs">{{ $barber->phone }}</td>
                                
                                <!-- Badge Status Retro Neobrutalism -->
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-lg border-2 border-gray-900 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] {{ $barber->is_active ? 'bg-green-300 text-gray-900' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $barber->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        {{-- Edit Button Icon --}}
                                        <button
                                            type="button"
                                            data-modal-target="barber-modal"
                                            data-modal-toggle="barber-modal"
                                            data-update-url="{{ route('admin.barbers.update', $barber) }}"
                                            onclick="openEditModal(this, @js($barber))"
                                            class="rounded-lg p-2 text-gray-500 hover:text-primary focus:outline-none"
                                            title="Edit Barber"
                                            aria-label="Edit {{ $barber->name }}"
                                        >
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-3M14.707 3.293a1 1 0 0 1 1.414 0l1.586 1.586a1 1 0 0 1 0 1.414l-9 9a1 1 0 0 1-.39.242l-3 1a1 1 0 0 1-1.266-1.265l1-3a1 1 0 0 1 .242-.391l9-9z"/>
                                            </svg>
                                        </button>

                                        {{-- Delete Button Icon --}}
                                        <form action="{{ route('admin.barbers.destroy', $barber) }}" method="POST" class="inline" onsubmit="return confirm('Delete this barber?')">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="rounded-lg p-2 text-gray-500 hover:text-primary focus:outline-none"
                                                title="Delete Barber"
                                                aria-label="Delete {{ $barber->name }}"
                                            >
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">No barbers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Container (Justify Between) -->
        <div class="w-full [&>nav]:flex [&>nav]:w-full [&>nav]:items-center [&>nav]:justify-between text-xs font-bold text-gray-600">
            {{ $barbers->links() }}
        </div>
    </div>

    @include('admin.barbers._modal')
@endsection