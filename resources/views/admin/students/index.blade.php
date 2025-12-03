@extends('layout')

@php
    use Illuminate\Support\Str;
    $q = $q ?? '';
    $teachers = $teachers ?? collect();
@endphp

@section('suppress_global_flash', true)

@section('head')
    {{-- Load unified admin UI stylesheet --}}
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('body')
<div class="tok-admin-shell" style="max-width:1300px;">

    {{-- Page Title --}}
    <h2 class="admin-page-title">Manage Students</h2>
    <p class="admin-page-subtitle">Create, search, and maintain student accounts.</p>

    {{-- Local flashes --}}
    @php
        $ok  = session('ok_students') ?? session()->pull('ok');
        $gen = session('generated_password_students') ?? session()->pull('generated_password');
    @endphp

    @if($ok)
        <div class="flash" style="margin-top:6px">{{ $ok }}</div>
    @endif

    @if($gen)
        <div class="flash" style="margin-top:6px">
            Generated password:
            <code>{{ $gen }}</code>
            — copy it now; it will not be shown again.
        </div>
    @endif


    {{-- CARD WRAPPER --}}
    <div class="card" style="margin-top:16px;">

        {{-- HEADER: Title + Add Student --}}
        <div class="card-header card-header-muted"
             style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-header-title">
                All Students ({{ $students->total() ?? $students->count() }})
            </div>

            <a href="{{ route('admin.students.create') }}"
               class="btn"
               style="font-size:14px; border-radius:8px;">
                + Add Student
            </a>
        </div>


        <div class="card-body">

            {{-- SEARCH BAR --}}
            <form method="get"
                  action="{{ route('admin.students.index') }}"
                  style="margin:0 0 14px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">

                <input type="text"
                       name="q"
                       value="{{ $q }}"
                       placeholder="Search by email or ID…"
                       style="
                           min-width:260px;
                           padding:8px 10px;
                           border-radius:10px;
                           border:1px solid #d1d5db;
                           font-size:14px;
                       ">

                <button class="workspace-link-btn" type="submit">Search</button>

                @if($q !== '')
                    <a href="{{ route('admin.students.index') }}"
                       class="workspace-link-btn workspace-link-btn-secondary">
                        Clear
                    </a>
                @endif
            </form>


            {{-- EMPTY STATE --}}
            @if($students->isEmpty())
                <p class="empty-state-text" style="margin-top:10px;">No students found.</p>
            @else

                {{-- Hover effect --}}
                <style>
                    .tls-row-hover td {
                        transition: background 0.15s ease;
                    }
                    .tls-row-hover:hover td {
                        background: #f9fafb;
                    }
                </style>

                {{-- TABLE --}}
                <div style="overflow-x:auto; margin-top:4px;">
                    <table style="width:100%; border-collapse:collapse; font-size:14px;">
                        <thead>
                            <tr>
                                <th style="padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; width:32%;">Name</th>
                                <th style="padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; width:32%;">Email</th>
                                <th style="padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; width:20%;">Teacher</th>
                                <th style="padding:8px; border-bottom:1px solid #e5e7eb; text-align:left; width:16%;">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        @foreach($students as $s)
                            @php
                                $first = $s->first_name ?? '';
                                $last  = $s->last_name  ?? '';
                                $studentName = trim($first.' '.$last);

                                if ($studentName === '' && !empty($s->name)) {
                                    $studentName = $s->name;
                                }
                                if ($studentName === '') {
                                    $studentName = Str::of($s->email)->before('@')->headline();
                                }

                                $t = $teachers->firstWhere('id', $s->teacher_id);
                                $teacherName = $t->name ?? '—';

                                $sid = $s->id;
                            @endphp

                            <tr class="tls-row-hover">
                                <td style="padding:8px; border-bottom:1px solid #f3f4f6; font-weight:600;">
                                    {{ $studentName }}
                                </td>

                                <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                                    {{ $s->email }}
                                </td>

                                <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                                    {{ $teacherName }}
                                </td>

                                <td style="padding:8px; border-bottom:1px solid #f3f4f6; white-space:nowrap;">
                                    <div style="display:flex; gap:6px; flex-wrap:nowrap;">

                                        {{-- Edit --}}
                                        <a href="{{ route('admin.students.edit', $sid) }}"
                                           class="workspace-link-btn">
                                            Edit
                                        </a>

                                        {{-- Delete --}}
                                        <form method="POST"
                                              action="{{ route('admin.students.destroy', $sid) }}"
                                              onsubmit="return confirm('Delete this student? This does not remove their user account.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="workspace-link-btn workspace-link-btn-danger">
                                                Delete
                                            </button>
                                        </form>

                                        {{-- Reset PW --}}
                                        <form method="POST"
                                              action="{{ route('admin.students.reset', $sid) }}"
                                              onsubmit="return confirm('Generate a new password for this student?');">
                                            @csrf
                                            <input type="hidden" name="generate" value="1">
                                            <button type="submit" class="workspace-link-btn">
                                                Reset PW
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:12px;">
                    {{ $students->links() }}
                </div>

            @endif
        </div>
    </div>

</div>
@endsection