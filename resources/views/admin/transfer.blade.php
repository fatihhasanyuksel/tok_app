@extends('layout')

@section('body')
<h2>Transfer Students</h2>

@php
  // ✅ Scoped flash key to prevent duplicate messages from layout
  $ok = session('ok_transfer') ?? session()->pull('ok');
@endphp
@if($ok)
  <div class="flash" style="margin:10px 0; color:green;">
    {{ $ok }}
  </div>
@endif

<form method="get" action="{{ route('admin.transfer') }}" style="margin-bottom:16px; display:flex; gap:10px; align-items:center;">
  <label>
    Filter by teacher:
    <select name="from_id" onchange="this.form.submit()">
      <option value="">— All teachers —</option>
      @foreach($teachers as $t)
        <option value="{{ $t->id }}" {{ request('from_id') == $t->id ? 'selected' : '' }}>
          {{ $t->name }}
        </option>
      @endforeach
    </select>
  </label>
  <noscript><button class="btn">Filter</button></noscript>
</form>

@if($students->isEmpty())
  <p>No students found for the selected teacher.</p>
@else
  <form method="post" action="{{ route('admin.transfer.do') }}">
    @csrf

    <div style="margin:10px 0;">
      <label>
        Transfer selected to:
        <select name="to_teacher_id" required>
          <option value="">— Choose teacher —</option>
          @foreach($teachers as $t)
            <option value="{{ $t->id }}">{{ $t->name }}</option>
          @endforeach
        </select>
      </label>
      <button class="btn" style="margin-left:8px;">Transfer Selected</button>
    </div>

    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%; margin-top:10px;">
      <thead style="background:#f5f5f5;">
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
            <td><input type="checkbox" name="student_ids[]" value="{{ $s->id }}"></td>
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