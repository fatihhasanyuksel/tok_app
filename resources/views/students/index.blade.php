@extends('layout')

{{-- Suppress layout’s default flash bar to avoid duplicates --}}
@section('suppress_global_flash', true)

@section('body')
  {{-- Local green welcome bar --}}
  <div class="flash">
    Welcome, {{ auth()->user()->name ?? 'Teacher' }}
  </div>

  <h2 style="margin-top:20px;">Students</h2>
  <p class="muted" style="margin-top:-6px">
    Open a student’s workspace for either ToK Exhibition or ToK Essay.
  </p>

  @if($students->count())

    {{-- Table styling --}}
    <style>
      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
      }
      th, td {
        padding: 10px 12px;
        border-bottom: 1px solid #eee;
        text-align: left;
        font-size: 14px;
      }
      th {
        font-weight: 600;
        background: #fafafa;
      }
      tr:hover td {
        background: #f9f9f9;
      }
      .t-name {
        font-weight: 600;
      }
      .actions {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: flex-end;
      }
      .muted {
        color: #777;
        font-size: 13px;
      }
    </style>

    <table>
      <thead>
        <tr>
          <th style="width:20%">Name</th>
          <th style="width:20%">Student Email</th>
          <th style="width:18%">Parent Name</th>
          <th style="width:22%">Parent Email</th>
          <th style="width:15%">Parent Phone</th>
          <th style="width:10%; text-align:right">Workspaces</th>
        </tr>
      </thead>
      <tbody>
        @foreach($students as $s)
          @php
            $uid = \DB::table('users')->where('email', $s->email)->value('id');
            $name = trim(($s->first_name ?? '').' '.($s->last_name ?? '')) ?: ($s->email ?? '—');
          @endphp
          <tr>
            <td class="t-name">{{ $name }}</td>
            <td>{{ $s->email ?? '—' }}</td>
            <td>{{ $s->parent_name ?? '—' }}</td>
            <td>{{ $s->parent_email ?? '—' }}</td>
            <td>{{ $s->parent_phone ?? '—' }}</td>
            <td style="text-align:right">
              <div class="actions">
                @if ($uid)
                  <a class="btn" href="{{ route('workspace.show', ['type' => 'exhibition']) }}?student={{ $uid }}">
                    Exhibition
                  </a>
                  <a class="btn" href="{{ route('workspace.show', ['type' => 'essay']) }}?student={{ $uid }}">
                    Essay
                  </a>
                @else
                  <span class="muted">No account</span>
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div style="margin-top:12px">
      {{ $students->links() }}
    </div>

  @else
    <p>No students yet. <a href="{{ route('students.create') }}">Add the first one</a>.</p>
  @endif
@endsection