@extends('admin.layouts.app')

@section('title', 'Edit Barber')
@section('header', 'Edit Barber')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.barbers.index') }}" class="text-sm font-semibold text-primary hover:underline">&larr; Back to barbers</a>
            <h2 class="mt-4 font-montserrat text-2xl font-bold text-gray-900">Edit Barber</h2>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm md:p-6">
            <form action="{{ route('admin.barbers.update', $barber) }}" method="POST" enctype="multipart/form-data">
                @method('PUT')
                @include('admin.barbers._form', ['submitLabel' => 'Update Barber'])
            </form>
        </div>
    </div>
@endsection
