@extends('layout')

@section('content')
<div style="max-width:900px; margin:24px auto; padding:0 20px;">

  <h1 style="margin:0 0 8px;">ToK Resources Hub</h1>
  <p style="margin:0 0 16px; color:#6b7280;">
    Access guides, rubrics, and sample materials uploaded by your teachers.
  </p>

  @if (session('ok'))
    <div class="flash-ok">{{ session('ok') }}</div>
  @endif

  <div class="card" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px 20px; box-shadow:0 1px 2px rgba(0,0,0,.05);">
    @if (empty($files) || count($files) === 0)
      <div class="empty" style="color:#6b7280; font-style:italic; padding:10px 0;">
        No resources available yet. Please check back later.
      </div>
    @else
      @foreach ($files as $f)
        @php
          // Defensive: ensure keys exist
          $name    = e($f['name'] ?? 'Untitled file');
          $url     = $f['url']  ?? '#';
          $size    = e($f['size'] ?? '');
          $updated = e($f['updated'] ?? '');
        @endphp
        <div class="file" style="display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #f1f1f1; padding:10px 0;">
          <div>
            <a href="{{ $url }}" target="_blank" rel="noopener" title="Open {{ $name }}">
              {{ $name }}
            </a>
            <div class="meta" style="color:#6b7280; font-size:13px;">
              {{ $size }}
              @if($size && $updated) • @endif
              updated {{ $updated }}
            </div>
          </div>
          <a href="{{ $url }}" target="_blank" rel="noopener" class="btn secondary sm" aria-label="Open {{ $name }}" title="Open in new tab" style="text-decoration:none;">
            📥
          </a>
        </div>
      @endforeach
    @endif
  </div>

  <p style="color:#6b7280; font-size:14px; margin-top:10px;">
    (Files are stored securely on the school server and appear here as teachers upload them.)
  </p>
</div>
@endsection