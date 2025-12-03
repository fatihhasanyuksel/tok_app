<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'ToK Learning Space')</title>

    {{-- ToK Learning Space Module CSS with stable versioning --}}
    @php
        $lsCssVersion = @filemtime(public_path('tok-ls/css/tok-learning-space.css'));
    @endphp
    <link rel="stylesheet"
          href="{{ asset('tok-ls/css/tok-learning-space.css') }}@if($lsCssVersion)?v={{ $lsCssVersion }}@endif">

    {{-- Minimal inline styles for flash messages (scoped to ToK LS only) --}}
    <style>
        .tok-ls-flash-region {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 16px;
        }
        .tok-ls-alert {
            padding: 10px 12px;
            border-radius: 4px;
            margin: 12px 0;
            font-size: 0.95rem;
        }
        .tok-ls-alert--success {
            background: #e6ffed;
            border: 1px solid #22c55e;
            color: #166534;
        }
        .tok-ls-alert--error {
            background: #fef2f2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
    </style>
</head>

<body class="tok-ls tok-ls--teacher">
    <div class="tok-ls-shell">

        {{-- HEADER --}}
        <header class="tok-ls-header">
            <div class="tok-ls-header-left">
                <div class="tok-ls-brand">
                    <img
                        src="{{ asset('tok-ls/ToKLoopLogo.svg') }}"
                        alt="ASAD ToK Loop logo">
                    <span class="tok-ls-dot">&bull;</span>
                    <span class="tok-ls-logo-text">
                        @yield('ls_header_title', 'ToK Learning Space')
                    </span>
                </div>
            </div>

            <div class="tok-ls-header-right">
                @hasSection('tok_ls_breadcrumb')
                    <nav class="tok-ls-breadcrumb">
                        @yield('tok_ls_breadcrumb')
                    </nav>
                @else
                    <nav class="tok-ls-breadcrumb">
                        <a href="{{ route('tok-ls.teacher.classes') }}">Classes</a>
                    </nav>
                @endif
            </div>
        </header>

        {{-- Global flash messages for the LS module --}}
        @if (session('success') || session('error') || ($errors->any() ?? false))
            <div class="tok-ls-flash-region">

                @if (session('success'))
                    <div class="tok-ls-alert tok-ls-alert--success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="tok-ls-alert tok-ls-alert--error">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="tok-ls-alert tok-ls-alert--error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>
        @endif

        {{-- MAIN CONTENT --}}
        <main class="tok-ls-container">
            @yield('content')
        </main>

    </div>

    {{-- REQUIRED FOR TIPTAP / MAIN JS --}}
    @vite('resources/js/app.js')

</body>
</html>