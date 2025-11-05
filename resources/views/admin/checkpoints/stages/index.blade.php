@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6 space-y-6">

    {{-- Flash / errors --}}
    @if(session('ok'))
        <div class="p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="p-3 rounded bg-red-100 text-red-800">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="text-2xl font-semibold">Checkpoint Stages</h1>

    {{-- Create --}}
    <div class="border rounded-lg p-4 space-y-3">
        <h2 class="font-medium">Add Stage</h2>
        <form method="POST" action="{{ route('admin.stages.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @csrf
            <input name="key" placeholder="key (e.g., draft_1)" class="border rounded p-2" required>
            <input name="label" placeholder="Label (e.g., Draft 1)" class="border rounded p-2" required>
            <input name="display_order" type="number" min="0" class="border rounded p-2" placeholder="Order (auto)">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>Active</span>
            </label>
            <button class="border rounded p-2 bg-gray-900 text-white">Create</button>
        </form>
    </div>

    {{-- List + inline edit + toggle + deactivate --}}
    <div class="border rounded-lg p-4">
        <form method="POST" action="{{ route('admin.stages.reorder') }}" class="overflow-x-auto">
            @csrf
            <table class="w-full text-left border rounded">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="p-2 border-b">ID</th>
                        <th class="p-2 border-b">Key</th>
                        <th class="p-2 border-b">Label</th>
                        <th class="p-2 border-b">Order</th>
                        <th class="p-2 border-b">Active</th>
                        <th class="p-2 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($stages as $s)
                    <tr class="border-b">
                        <td class="p-2 align-top">{{ $s->id }}</td>

                        {{-- Inline edit key/label --}}
                        <td class="p-2 align-top">
                            <form method="POST" action="{{ route('admin.stages.update', $s) }}" class="flex flex-col gap-2 md:flex-row md:items-center">
                                @csrf @method('PATCH')
                                <input name="key" value="{{ $s->key }}" class="border rounded p-2 w-44" />
                                <input name="label" value="{{ $s->label }}" class="border rounded p-2 w-52" />
                                <button class="px-3 py-2 border rounded">Save</button>
                            </form>
                        </td>

                        <td class="p-2 align-top"></td>

                        {{-- Reorder field --}}
                        <td class="p-2 align-top">
                            <input name="order[{{ $s->id }}]" type="number" class="border rounded p-2 w-24" value="{{ $s->display_order }}">
                        </td>

                        {{-- Toggle active --}}
                        <td class="p-2 align-top">
                            <form method="POST" action="{{ route('admin.stages.toggle', $s) }}">
                                @csrf
                                <button class="px-3 py-2 border rounded {{ $s->is_active ? 'bg-green-100' : 'bg-red-100' }}">
                                    {{ $s->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>

                        {{-- Deactivate (soft) --}}
                        <td class="p-2 align-top">
                            <form method="POST" action="{{ route('admin.stages.destroy', $s) }}" onsubmit="return confirm('Deactivate this stage?');">
                                @csrf @method('DELETE')
                                <button class="px-3 py-2 border rounded">Deactivate</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-3 text-gray-500">No stages yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                <button class="px-4 py-2 border rounded bg-gray-900 text-white">Save Order</button>
            </div>
        </form>
    </div>
</div>
@endsection