@extends('layout')

@section('head')
    {{-- Re-use the same admin CSS as dashboard / teachers / students --}}
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('body')
    <div class="tok-admin-shell">

        {{-- Page title --}}
        <h2 class="admin-page-title">Manage Admin Accounts</h2>
        <p class="admin-page-subtitle">
            View, create, or remove system admin accounts.
        </p>

        <div class="card" style="margin-top: 12px;">

            {{-- Card header with title left, Add Admin button right --}}
            <div class="card-header card-header-muted"
                 style="display:flex; justify-content:space-between; align-items:center;">
                <div class="card-header-title">
                    All Admins ({{ $admins->count() }})
                </div>

                <a href="{{ route('admin.admins.create') }}"
                   class="btn"
                   style="font-size:13px; padding:4px 10px;">
                    + Add Admin
                </a>
            </div>

            <div class="card-body">
                @if ($admins->isEmpty())
                    <p class="empty-state-text">No admin accounts found.</p>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:14px;">
                            <thead>
                            <tr>
                                <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Name</th>
                                <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Email</th>
                                <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Actions</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach ($admins as $admin)
                                <tr class="tok-row-hover">
                                    <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6;">
                                        {{ $admin->name }}
                                    </td>

                                    <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6;">
                                        {{ $admin->email }}
                                    </td>

                                    <td style="padding:8px 8px; border-bottom:1px solid #f3f4f6;">
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">

                                            {{-- Edit --}}
                                            <a href="{{ route('admin.admins.edit', $admin->id) }}"
                                               class="workspace-link-btn">
                                                Edit
                                            </a>

                                            {{-- Reset password --}}
                                            <form action="{{ route('admin.admins.reset', $admin->id) }}"
                                                  method="POST"
                                                  style="display:inline">
                                                @csrf
                                                <button type="submit"
                                                        class="workspace-link-btn workspace-link-btn-secondary">
                                                    Reset password
                                                </button>
                                            </form>

                                            {{-- Delete --}}
                                            <form action="{{ route('admin.admins.destroy', $admin->id) }}"
                                                  method="POST"
                                                  style="display:inline"
                                                  onsubmit="return confirm('Delete this admin account? This cannot be undone.');">
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