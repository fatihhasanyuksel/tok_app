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

    {{-- Table + button styling --}}
    <style>
      /* Base table */
      table { width: 100%; border-collapse: collapse; margin-top: 10px; }
      th, td { padding: 8px 10px; border-bottom: 2px solid #eee; text-align: left; font-size: 14px; }
      th { font-weight: 600; background: #fafafa; white-space: nowrap; } /* don't wrap headers */
      tr:hover td { background: #f9f9f9; }
      .t-name { font-weight: 600; }
      .actions { display: flex; flex-direction: column; gap: 6px; align-items: flex-end; }
      .muted { color: #777; font-size: 13px; }

      /* Make long emails behave */
      td:nth-child(2), /* Student Email */
      td:nth-child(4)  /* Parent Email */{
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }


/* Stage selects: stable width across rows & reloads */
select.checkpoint-stage{
  width: 160px;        /* fixed width prevents content-based expansion */
  max-width: 100%;
  min-width: 0;        /* override any UA minimums */
  padding: 3px 6px;
  box-sizing: border-box;
}

      /* Horizontal scroll on narrow viewports */
      .table-wrap{
        overflow-x: auto;
      }

     /* Subtle border emphasis + transition for the colored borders */
.btn.status-border {
  border: 2px solid #BDBDBD;  /* fallback; inline border-color still overrides this */
  border-radius: 6px;         /* new: rounded corners to match other buttons */
  color: #222 !important;
  transition: border-color 140ms ease-in;
  
  /* NEW: inner spacing */
  padding: 3px 6px;            /* vertical | horizontal */
  text-decoration: none;        /* ensures clean text edges */
  display: inline-block;        /* keeps correct box sizing */
}
    </style>

    @php
      // Ensure we have collections/arrays we can safely read from
      $stagesLocal   = (isset($stages) && $stages instanceof \Illuminate\Support\Collection) ? $stages : collect();
      $statusesSafe  = (isset($statuses) && $statuses instanceof \Illuminate\Support\Collection) ? $statuses : collect();
      $statusMeta    = $statusMeta ?? []; // [student_id][type] => ['selected_at','selected_by_name','status_code']

      // Map status -> border color (hex)
      $statusBorder = [
        'no_submission' => '#E63946', // red
        'draft_1'       => '#FF7B00', // deep orange
        'draft_2'       => '#FFB266', // light orange
        'draft_3'       => '#FFD54D', // yellow
        'final'         => '#8BC34A', // light green
        'approved'      => '#2ECC71', // bright green
      ];
      $defaultBorder = '#BDBDBD'; // gray for unknown / none
    @endphp

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:16%">Name</th>
            <th style="width:16%">Student Email</th>
            <th style="width:14%">Parent Name</th>
            <th style="width:16%">Parent Email</th>
            <th style="width:12%">Parent Phone</th>
            <th style="width:13%">Exhibition Progress</th>
            <th style="width:13%">Essay Progress</th>
            <th style="width:10%; text-align:right">Workspaces</th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $s)
            @php
              $uid   = \DB::table('users')->where('email', $s->email)->value('id');
              $name  = trim(($s->first_name ?? '').' '.($s->last_name ?? '')) ?: ($s->email ?? '—');

              // Optional “current” values (if your controller still provides $statuses)
              $byStudent = $statusesSafe->get($s->id, collect());
              $exhLegacy = optional($byStudent->firstWhere('work_type','exhibition')); // legacy shape
              $esyLegacy = optional($byStudent->firstWhere('work_type','essay'));

              // Try multiple sources to determine current status codes:
              $exhFromMeta = $statusMeta[$s->id]['exhibition']['status_code'] ?? null;
              $esyFromMeta = $statusMeta[$s->id]['essay']['status_code']      ?? null;

              $exhNewRow   = $byStudent->firstWhere('type', 'exhibition');
              $esyNewRow   = $byStudent->firstWhere('type', 'essay');

              $exhCode = $exhFromMeta
                         ?? ($exhNewRow->status_code ?? null)
                         ?? ($exhLegacy->stage_key ?? null);

              $esyCode = $esyFromMeta
                         ?? ($esyNewRow->status_code ?? null)
                         ?? ($esyLegacy->stage_key ?? null);

              $exhBorder = $statusBorder[$exhCode] ?? $defaultBorder;
              $esyBorder = $statusBorder[$esyCode] ?? $defaultBorder;

              // “Updated … by …” line support
              $exhMeta = $statusMeta[$s->id]['exhibition'] ?? null;
              $esyMeta = $statusMeta[$s->id]['essay']      ?? null;
            @endphp

            <tr>
              <td class="t-name">{{ $name }}</td>
              <td title="{{ $s->email }}">{{ $s->email ?? '—' }}</td>
              <td>{{ $s->parent_name ?? '—' }}</td>
              <td title="{{ $s->parent_email }}">{{ $s->parent_email ?? '—' }}</td>
              <td>{{ $s->parent_phone ?? '—' }}</td>

              {{-- Exhibition progress dropdown + updated meta --}}
              <td>
                @includeIf('partials.checkpoints.stage-dropdown', [
                    'studentId'       => $s->id,
                    'workType'        => 'exhibition',
                    'currentStageKey' => $exhCode,
                    'currentLabel'    => null,
                    'stages'          => $stagesLocal,
                    'updatedAt'       => $exhMeta['selected_at']      ?? null,
                    'updatedBy'       => $exhMeta['selected_by_name'] ?? null,
                ])
              </td>

              {{-- Essay progress dropdown + updated meta --}}
              <td>
                @includeIf('partials.checkpoints.stage-dropdown', [
                    'studentId'       => $s->id,
                    'workType'        => 'essay',
                    'currentStageKey' => $esyCode,
                    'currentLabel'    => null,
                    'stages'          => $stagesLocal,
                    'updatedAt'       => $esyMeta['selected_at']      ?? null,
                    'updatedBy'       => $esyMeta['selected_by_name'] ?? null,
                ])
              </td>

              {{-- Workspace links with colored borders --}}
              <td style="text-align:right">
                <div class="actions">
                  @if ($uid)
                    <a
                      id="btn-exhibition-{{ $s->id }}"
                      class="btn status-border"
                      style="border-color: {{ $exhBorder }}"
                      href="{{ route('workspace.show', ['type' => 'exhibition']) }}?student={{ $uid }}"
                    >
                      Exhibition
                    </a>
                    <a
                      id="btn-essay-{{ $s->id }}"
                      class="btn status-border"
                      style="border-color: {{ $esyBorder }}"
                      href="{{ route('workspace.show', ['type' => 'essay']) }}?student={{ $uid }}"
                    >
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
    </div>

    <div style="margin-top:12px">
      {{ $students->links() }}
    </div>

  @else
    <p>No students yet. <a href="{{ route('students.create') }}">Add the first one</a>.</p>
  @endif
@endsection