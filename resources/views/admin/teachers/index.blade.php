@extends('layout')

@section('head')
    {{-- Re-use the same admin CSS as the main dashboard / TLS --}}
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('content')
    <div class="tok-admin-shell">

        <h2 class="admin-page-title">Teachers</h2>
        
        <p class="admin-page-subtitle">
            Manage ToK App teachers. Use this screen to add, edit, or remove teacher accounts.
        </p>

        <div class="card">

            {{-- Card header with title left, Add Teacher button right --}}
            <div class="card-header card-header-muted" 
                 style="display:flex; justify-content:space-between; align-items:center;">
                
                <div class="card-header-title">
                    All Teachers ({{ $teachers->count() }})
                </div>

                <a href="{{ route('admin.teachers.create') }}"
                   class="btn"
                   style="font-size:13px; padding:4px 10px; border-radius:8px;">
                    + Add Teacher
                </a>
            </div>

            <div class="card-body">
                @if ($teachers->isEmpty())
                    <p class="empty-state-text">No teachers found yet.</p>
                @else
                    {{-- Same hover style as students table --}}
                    <style>
                        .tls-row-hover td {
                            transition: background 0.15s ease;
                        }
                        .tls-row-hover:hover td {
                            background: #f9fafb;
                        }
                    </style>

                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:14px;">
                            <thead>
                                <tr>
                                    <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Name</th>
                                    <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Email</th>
                                    <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Active</th>
                                    <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Admin</th>
                                    <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($teachers as $teacher)
                                    <tr class="tls-row-hover">
                                        <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6;">
                                            {{ $teacher->name }}
                                        </td>

                                        <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6;">
                                            {{ $teacher->email }}
                                        </td>

                                        <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6;">
                                            @if ($teacher->active)
                                                <span class="badge-pill" style="background:#dcfce7; color:#166534;">
                                                    Yes
                                                </span>
                                            @else
                                                <span class="badge-pill" style="background:#fee2e2; color:#b91c1c;">
                                                    No
                                                </span>
                                            @endif
                                        </td>

                                        <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6;">
                                            @if ($teacher->is_admin)
                                                <span class="badge-pill" style="background:#dbeafe; color:#1d4ed8;">
                                                    Yes
                                                </span>
                                            @else
                                                <span class="badge-pill" style="background:#f3f4f6; color:#374151;">
                                                    No
                                                </span>
                                            @endif
                                        </td>

                                        <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6; white-space:nowrap;">
                                            <div style="display:flex; gap:6px; flex-wrap:nowrap;">

                                                <a href="{{ route('admin.teachers.edit', $teacher->id) }}"
                                                   class="workspace-link-btn">
                                                    Edit
                                                </a>

                                                <form action="{{ route('admin.teachers.destroy', $teacher->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Delete this teacher? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                            class="workspace-link-btn workspace-link-btn-danger">
                                                        Delete
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                @endif
            </div>

        </div>

    </div>
@endsection