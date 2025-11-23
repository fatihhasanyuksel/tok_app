@extends('layout')

@section('body')
  <h2>Manage Admin Accounts</h2>

  {{-- Breadcrumb --}}
  <p style="margin-top:4px;">
    <a href="{{ route('admin.dashboard') }}">← Back to Admin Dashboard</a>
  </p>

  @if (session('ok'))
    <div class="alert alert-success" style="margin-top:10px;">
      {{ session('ok') }}
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger" style="margin-top:10px;">
      {{ session('error') }}
    </div>
  @endif

  <div style="margin:18px 0;">
    <a href="{{ route('admin.admins.create') }}" class="btn">
      Add New Admin Account
    </a>
  </div>

  @if ($admins->isEmpty())
    <p>No admin accounts found.</p>
  @else
    <table class="table" style="width:100%;border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;border-bottom:1px solid #e5e7eb;padding:8px;">Name</th>
          <th style="text-align:left;border-bottom:1px solid #e5e7eb;padding:8px;">Email</th>
          <th style="text-align:left;border-bottom:1px solid #e5e7eb;padding:8px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($admins as $admin)
          <tr>
            <td style="padding:8px;border-bottom:1px solid #f3f4f6;">
              {{ $admin->name }}
            </td>
            <td style="padding:8px;border-bottom:1px solid #f3f4f6;">
              {{ $admin->email }}
            </td>
            <td style="padding:8px;border-bottom:1px solid #f3f4f6;">
              {{-- Edit --}}
              <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-small">
                Edit
              </a>

              {{-- Reset password --}}
              <form method="POST"
                    action="{{ route('admin.admins.reset', $admin) }}"
                    style="display:inline-block;margin-left:4px;">
                @csrf
                <button type="submit"
                        class="btn btn-small"
                        onclick="return confirm('Reset password for {{ $admin->name }}?')">
                  Reset password
                </button>
              </form>

              {{-- Delete --}}
              <form method="POST"
                    action="{{ route('admin.admins.destroy', $admin) }}"
                    style="display:inline-block;margin-left:4px;">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn btn-small btn-danger"
                        onclick="return confirm('Delete admin {{ $admin->name }}? This cannot be undone.')">
                  Delete
                </button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
@endsection