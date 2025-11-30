@extends('layout')

@section('content')
{{-- Import Font (only if your main layout doesn't have it) --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    /* --- SCOPED CSS VARIABLES --- */
    .tok-dashboard {
        --bg-card: #ffffff;
        --accent: #2563eb;
        --text-main: #111827;
        --text-muted: #6b7280;
        --radius: 16px;
        --shadow: 0 10px 30px rgba(0,0,0,0.06);
        font-family: 'Inter', sans-serif;
        max-width: 1100px;
        margin: 0 auto;
        padding: 30px 20px;
        color: var(--text-main);
    }

    /* --- HEADER & LAYOUT --- */
    .tok-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .tok-logo { font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
    .tok-user { font-size: 14px; color: var(--text-muted); }
    
    .tok-intro { margin-bottom: 25px; }
    .tok-intro h1 { margin: 0 0 5px; font-size: 20px; font-weight: 600; }
    .tok-intro p { margin: 0; color: var(--text-muted); font-size: 14px; }

    /* --- GRID SYSTEM --- */
    .tok-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    /* --- CARDS --- */
    .tok-card {
        background: var(--bg-card);
        border-radius: var(--radius);
        padding: 24px;
        border: 1px solid #e5e7eb;
        box-shadow: var(--shadow);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .tok-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); border-color: #cbd5e1; }

    .tok-badge { 
        display: inline-block; font-size: 11px; font-weight: 600; text-transform: uppercase; 
        letter-spacing: 0.05em; color: var(--accent); margin-bottom: 8px;
    }
    
    .tok-card h2 { font-size: 17px; font-weight: 600; margin: 0 0 8px; }
    .tok-card p { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0 0 20px; flex-grow: 1; }

    /* --- BUTTONS --- */
    .tok-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 16px; border-radius: 50px;
        background: #eff6ff; color: var(--accent);
        font-size: 13px; font-weight: 600; text-decoration: none;
        transition: background 0.2s;
    }
    .tok-btn:hover { background: #dbeafe; }

    /* --- FOOTER TIPS --- */
    .tok-footer { display: flex; flex-wrap: wrap; gap: 20px; border-top: 1px solid #e5e7eb; padding-top: 25px; }
    .tok-tips { flex: 2; font-size: 13px; color: var(--text-muted); }
    .tok-tips h3 { font-size: 12px; text-transform: uppercase; color: #9ca3af; margin: 0 0 10px; }
    .tok-tips ul { padding-left: 18px; margin: 0; }
    .tok-meta { flex: 1; text-align: right; font-size: 12px; color: #9ca3af; }
    
    @media (max-width: 768px) { .tok-meta { text-align: left; } }
</style>

<div class="tok-dashboard">

    <div class="tok-intro">
        <h1>Welcome, {{ auth()->user()->name ?? 'Student' }}</h1>
        <p>Draft, receive feedback, and explore ToK in one place.</p>
    </div>

    {{-- Cards Grid --}}
    <div class="tok-grid">

        {{-- Card 1: Essay --}}
        <article class="tok-card">
            <div>
                <span class="tok-badge">Workspace</span>
                <h2>Theory of Knowledge Essay</h2>
                <p>Develop your ToK essay draft, track revisions, and respond to teacher feedback.</p>
            </div>
            <div>
                <a href="{{ route('workspace.show', 'essay') }}" class="tok-btn">
                    <span>✍️</span> Start / Resume Essay
                </a>
            </div>
        </article>

        {{-- Card 2: Exhibition --}}
        <article class="tok-card">
            <div>
                <span class="tok-badge">Workspace</span>
                <h2>Theory of Knowledge Exhibition</h2>
                <p>Build your exhibition commentary and manage your selected objects.</p>
            </div>
            <div>
                <a href="{{ route('workspace.show', 'exhibition') }}" class="tok-btn">
                    <span>🖼️</span> Start / Resume Exhibition
                </a>
            </div>
        </article>

        {{-- Card 3: Learning Space --}}
        <article class="tok-card">
            <div>
                <span class="tok-badge" style="color: #059669;">Learning Space</span>
                <h2>ToK Learning Space</h2>
                <p>Access your ToK class lessons, activities, and teacher feedback in one place.</p>
            </div>
            <div>
                <a href="{{ route('tok-ls.student.home') }}" class="tok-btn" style="background: #ecfdf5; color: #059669;">
                    <span>📚</span> Open Learning Space
                </a>
            </div>
        </article>

    </div>

    {{-- Footer / Tips --}}
    <footer class="tok-footer">
        <div class="tok-tips">
            <h3>Tips</h3>
            <ul>
                <li>Select text and click <strong>“Request Feedback”</strong> to start a thread.</li>
                <li>Use the right panel to view inline comments.</li>
                <li>Use <strong>“Messages”</strong> for general chat with your ToK teacher.</li>
            </ul>
        </div>
        <div class="tok-meta">
            For technical support, send an email to edtech@australianschool.ae.<br>
            Your work is auto-saved.
        </div>
    </footer>

</div>
@endsection