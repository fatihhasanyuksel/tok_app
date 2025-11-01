@extends('layout')

{{-- Hide the layout’s global flash so we control the single banner here --}}
@section('suppress_global_flash', true)

@section('content')
  <div style="max-width:900px; margin:24px auto; padding:0 16px;">

    {{-- Single, persistent welcome banner (green) --}}
    <div class="flash">Welcome, {{ auth()->user()->name ?? 'Student' }}</div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px;">
      <div class="thread-card">
        <h2 style="margin:0 0 8px;">Theory of Knowledge Essay</h2>
        <p class="muted" style="margin:0 0 12px;">Draft, receive feedback, and revise in one place.</p>
        <form method="POST" action="{{ route('student.start', ['type' => 'essay']) }}">
          @csrf
          <button type="submit" class="btn">✍️ Start / Resume Essay</button>
        </form>
      </div>

      <div class="thread-card">
        <h2 style="margin:0 0 8px;">Theory of Knowledge Exhibition</h2>
        <p class="muted" style="margin:0 0 12px;">Build your exhibition text and artifacts.</p>
        <form method="POST" action="{{ route('student.start', ['type' => 'exhibition']) }}">
          @csrf
          <button type="submit" class="btn">🖼️ Start / Resume Exhibition</button>
        </form>
      </div>
    </div>

    <div style="margin-top:24px;">
      <h3 style="margin:0 0 8px;">Tips</h3>
      <ul style="margin:0 0 0 18px;">
        <li>Select part of your draft and click “Comment selection” to start a thread.</li>
        <li>Use the panel on the right to view and reply inline.</li>
        <li>Use “Messages” for general chat separate from inline feedback.</li>
      </ul>
    </div>
  </div>
@endsection