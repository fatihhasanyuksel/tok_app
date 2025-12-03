@extends('layout')

@section('body')
<h2>Transfer Students</h2>

@php
  // Scoped flash key to prevent duplicate messages from layout
  $ok = session('ok_transfer') ?? session()->pull('ok');
@endphp
@if($ok)
  <div class="flash" style="margin:10px 0; color:green;">
    {{ $ok }}
  </div>
@endif

<form method="get"
      action="{{ route('admin.transfer') }}"
      style="margin-bottom:16px; display:flex; gap:10px; align-items:center;">

  <label>
    Filter by teacher:
    <select name="from_id"
            class="transfer-select"
            onchange="this.form.submit()">
      <option value="">— All teachers —</option>
      @foreach($teachers as $t)
        <option value="{{ $t->id }}" {{ request('from_id') == $t->id ? 'selected' : '' }}>
          {{ $t->name }}
        </option>
      @endforeach
    </select>
  </label>

  <noscript>
    <button type="submit" class="transfer-pill-btn">Filter</button>
  </noscript>
</form>

@if($students->isEmpty())
  <p>No students found for the selected teacher.</p>
@else

  <style>
    /* Table alignment */
    .transfer-table {
      border-collapse: collapse;
      width: 100%;
    }
    .transfer-table th,
    .transfer-table td {
      padding: 10px 12px !important;
      border: 1px solid #e5e7eb;
      font-size: 14px;
    }
    .transfer-table th {
      background: #f9fafb;
      font-weight: 600;
      color: #374151;
      text-align: left;
    }

    /* Styled selects */
    .transfer-select {
      padding: 6px 10px;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      font-size: 14px;
      background: #ffffff;
    }
    .transfer-select:focus {
      outline: 2px solid #0b6bd6;
      outline-offset: 1px;
    }

    /* Local blue pill button (matches other admin pills) */
    .transfer-pill-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 6px 12px;
      border-radius: 999px;
      border: 1px solid #0b6bd6;
      background: #eff6ff;
      color: #0b6bd6;
      font-size: 12px;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
      white-space: nowrap;
      transition: background 0.2s ease;
    }
    .transfer-pill-btn:hover {
      background: #dbeafe;
    }
  </style>

  <form method="post" action="{{ route('admin.transfer.do') }}">
    @csrf

    <div style="margin:10px 0; display:flex; gap:10px; align-items:center;">
      <label>
        Transfer selected to:
        <select name="to_teacher_id" class="transfer-select" required>
          <option value="">— Choose teacher —</option>
          @foreach($teachers as $t)
            <option value="{{ $t->id }}">{{ $t->name }}</option>
          @endforeach
        </select>
      </label>

      {{-- Blue pill button --}}
      <button type="submit" class="transfer-pill-btn">
        Transfer Selected
      </button>
    </div>

    <table class="transfer-table">
      <thead>
        <tr>
          <th style="width:5%;"></th>
          <th style="width:25%;">Name</th>
          <th style="width:30%;">Email</th>
          <th style="width:30%;">Current Teacher</th>
        </tr>
      </thead>
      <tbody>
        @foreach($students as $s)
          @php
            $t = $teachers->firstWhere('id', $s->teacher_id);
            $teacherName = $t->name ?? '—';
          @endphp
          <tr>
            <td>
              <input type="checkbox" name="student_ids[]" value="{{ $s->id }}">
            </td>
            <td>{{ $s->first_name }} {{ $s->last_name }}</td>
            <td>{{ $s->email }}</td>
            <td>{{ $teacherName }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

  </form>

@endif
@endsection