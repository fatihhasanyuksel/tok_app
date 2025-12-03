@extends('layout')

@section('head')
    {{-- Shared admin styling --}}
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('content')
    <div class="tok-admin-shell">

        <h2 class="admin-page-title">Edit Teacher</h2>
        <p class="admin-page-subtitle">
            Update the teacher’s details. Leave the password fields blank if you don’t want to change it.
        </p>

        <p style="margin-bottom: 12px;">
            <a href="{{ route('admin.teachers.index') }}" class="btn">
                ← Back to Teachers
            </a>
        </p>

        @if ($errors->any())
            <div class="card" style="margin-bottom:16px; border-color:#fecaca; background:#fef2f2;">
                <div class="card-body">
                    <strong style="color:#b91c1c;">There were some problems with your input:</strong>
                    <ul style="margin:8px 0 0 18px; font-size:13px; color:#991b1b;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header card-header-muted">
                <div class="card-header-title">
                    Teacher details — {{ $teacher->name }}
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('admin.teachers.update', $teacher->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="selector-field" style="max-width:420px; margin-bottom:12px;">
                        <label for="name">Name</label>
                        <input id="name"
                               type="text"
                               name="name"
                               value="{{ old('name', $teacher->name) }}"
                               required>
                    </div>

                    <div class="selector-field" style="max-width:420px; margin-bottom:12px;">
                        <label for="email">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               value="{{ old('email', $teacher->email) }}"
                               required>
                    </div>

                    <div class="selector-field" style="max-width:420px; margin-bottom:12px;">
                        <label for="password">New password (optional)</label>
                        <input id="password"
                               type="password"
                               name="password">
                    </div>

                    <div class="selector-field" style="max-width:420px; margin-bottom:16px;">
                        <label for="password_confirmation">Confirm new password</label>
                        <input id="password_confirmation"
                               type="password"
                               name="password_confirmation">
                    </div>

                    <div class="selector-field" style="max-width:420px; margin-bottom:12px;">
                        <label for="active">Active</label>
                        <select id="active" name="active">
                            <option value="1" {{ old('active', $teacher->active) == 1 ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('active', $teacher->active) == 0 ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="selector-field" style="max-width:420px; margin-bottom:20px;">
                        <label for="is_admin">Admin privileges</label>
                        <select id="is_admin" name="is_admin">
                            <option value="0" {{ old('is_admin', $teacher->is_admin ?? 0) == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('is_admin', $teacher->is_admin ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                        <button type="submit" class="btn">
                            Update teacher
                        </button>

                        <a href="{{ route('admin.teachers.index') }}"
                           class="workspace-link-btn">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection