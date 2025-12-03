@extends('layout')

{{-- Load the admin pill/button stylesheet --}}
@section('head')
    <link rel="stylesheet" href="{{ asset('tok-admin/css/tok-admin-dashboard.css') }}">
@endsection

@section('body')
<h2 style="margin-top:0;">Add New Admin Account</h2>

{{-- Breadcrumb --}}
<p style="margin-top:4px;">
  <a href="{{ route('admin.admins.index') }}">← Back to Manage Admins</a>
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

<style>
  .form {max-width:720px}
  .row {margin:14px 0}
  .label {display:block;font-weight:600;margin-bottom:6px}
  .field {width:100%;padding:10px;border:1px solid #ccc;border-radius:8px}
  .actions {display:flex;gap:8px;margin-top:18px}
  .error {color:#b00020;font-size:12px;margin-top:6px}
  .hint {display:block;color:#666;font-size:12px;margin-top:4px}
</style>

<form class="form" method="POST" action="{{ route('admin.admins.store') }}">
  @csrf

  <div class="row">
    <label class="label" for="name">Full name</label>
    <input id="name" name="name" class="field" value="{{ old('name') }}" required>
    @error('name') <div class="error">{{ $message }}</div> @enderror
  </div>

  <div class="row">
    <label class="label" for="email">Email</label>
    <input id="email" type="email" name="email" class="field" value="{{ old('email') }}" required>
    @error('email') <div class="error">{{ $message }}</div> @enderror
  </div>

  <div class="row">
    <label class="label" for="password">Password</label>
    <input id="password" type="password" name="password" class="field" placeholder="Min 8 characters" required>
    <small class="hint">Choose a temporary password; the admin can change it later.</small>
    @error('password') <div class="error">{{ $message }}</div> @enderror
  </div>

  <div class="actions">
    {{-- Primary pill button (blue outline) --}}
    <button class="workspace-link-btn" type="submit">
      Create Admin
    </button>

    {{-- Secondary pill button (same style, just a link) --}}
    <a href="{{ route('admin.admins.index') }}" class="workspace-link-btn">
      Cancel
    </a>
  </div>
</form>
@endsection