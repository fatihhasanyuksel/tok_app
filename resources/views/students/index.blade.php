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
      th { font-weight: 600; background: #fafafa; white-space: nowrap; }
      tr:hover td { background: #f9f9f9; }
      .t-name { font-weight: 600; }
      .actions { display: flex; flex-direction: column; gap: 6px; align-items: flex-end; }
      .muted { color: #777; font-size: 13px; }

      td:nth-child(2),
      td:nth-child(4) {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      select.checkpoint-stage{
        width: 160px;
        max-width: 100%;
        min-width: 0;
        padding: 3px 6px;
        box-sizing: border-box;
      }

      .table-wrap{
        overflow-x: auto;
      }

      .btn.status-border {
        border: 2px solid #BDBDBD;
        border-radius: 6px;
        color: #222 !important;
        transition: border-color 140ms ease-in;
        padding: 3px 6px;
        text-decoration: none;
        display: inline-block;
        background: #fff;
      }

      .insights-row {
        background: #f9fafb;
      }

      .insights-cell {
        padding: 12px 10px;
        border-bottom: 2px solid #eee;
      }

      /* --- Progress + metrics styles (shared partial) --- */
      .progress-grid {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-top: 8px;
      }

      .progress-line {
        display: flex;
        flex-direction: column;
        gap: 4px;
      }

      .progress-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: #374151;
      }

      .stage-pill {
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background: rgba(59,130,246,0.1);
        color: #1d4ed8;
      }

      .stage-pill-secondary {
        background: rgba(16,185,129,0.1);
        color: #047857;
      }

      .progress-track {
        position: relative;
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: #f3f4f6;
        overflow: hidden;
      }

      .progress-fill {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 0%;
        border-radius: 999px;
        background: linear-gradient(90deg,#0b6bd6,#60a5fa);
        transition: width 0.25s ease-out;
      }

      .progress-fill-secondary {
        background: linear-gradient(90deg,#059669,#22c55e);
      }

      .workspace-links {
        margin-top: 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .workspace-link-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        border: 1px solid #0b6bd6;
        color: #0b6bd6;
        background: #eff6ff;
        text-decoration: none;
      }

      .workspace-link-btn:hover {
        background: #dbeafe;
      }

      .workspace-link-btn-secondary {
        border-color: #059669;
        color: #047857;
        background: #ecfdf5;
      }

      .workspace-link-btn-secondary:hover {
        background: #d1fae5;
      }

      .insights-card .workspace-links {
        display: none;
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
      $defaultBorder = '#BDBDBD';

      $statusLabels = [
        'no_submission' => 'No submission',
        'draft_1'       => 'Draft 1',
        'draft_2'       => 'Draft 2',
        'draft_3'       => 'Draft 3',
        'final'         => 'Final',
        'approved'      => 'Approved',
      ];
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
            <th style="width:12%; text-align:right">Workspaces</th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $s)
            @php
              // Use pre-resolved user IDs from controller (no DB call here)
              $uid   = $studentUserIds[$s->id] ?? null;
              $name  = trim(($s->first_name ?? '').' '.($s->last_name ?? '')) ?: ($s->email ?? '—');

              $byStudent = $statusesSafe->get($s->id, collect());
              $exhLegacy = optional($byStudent->firstWhere('work_type','exhibition'));
              $esyLegacy = optional($byStudent->firstWhere('work_type','essay'));

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

              $exhMeta = $statusMeta[$s->id]['exhibition'] ?? null;
              $esyMeta = $statusMeta[$s->id]['essay']      ?? null;

              $exhLabel = $statusLabels[$exhCode] ?? 'No submission';
              $esyLabel = $statusLabels[$esyCode] ?? 'No submission';
            @endphp

            {{-- Main student row --}}
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

              {{-- Workspace links + Insights toggle --}}
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

                  <button
                    type="button"
                    class="btn status-border insights-toggle"
                    data-student-id="{{ $s->id }}"
                    style="border-color:#6b7280;"
                  >
                    Insights
                  </button>
                </div>
              </td>
            </tr>

            {{-- Accordion row for student insights (shared metrics partial) --}}
            <tr
              id="insights-row-{{ $s->id }}"
              class="insights-row"
              style="display:none;"
            >
              <td colspan="8" class="insights-cell">
                @php
                  $bundle = $metricsByStudent[$s->id] ?? null;
                @endphp

                @if($bundle && $bundle['selectedStudent'])
                  <div class="insights-card">
                    @include('partials.student_metrics', [
                        'selectedStudent' => $bundle['selectedStudent'],
                        'progress'        => $bundle['progress'],
                        'studentMetrics'  => $bundle['studentMetrics'],
                        'selectedUserId'  => $bundle['selectedUserId'],
                        'emptyMessage'    => 'This student has no Exhibition or Essay submissions yet.',
                        'showWorkspaces'  => false,
                    ])
                  </div>
                @else
                  <p class="muted">No data available for this student yet.</p>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:12px">
      {{ $students->links() }}
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const toggles = document.querySelectorAll('.insights-toggle');

        toggles.forEach(btn => {
          btn.addEventListener('click', function () {
            const id  = this.dataset.studentId;
            const row = document.getElementById('insights-row-' + id);
            if (!row) return;

            const isHidden = (row.style.display === 'none' || row.style.display === '');
            row.style.display = isHidden ? 'table-row' : 'none';

            this.textContent = isHidden ? 'Hide insights' : 'Insights';
          });
        });
      });
    </script>

  @else
    <p>No students yet. <a href="{{ route('students.create') }}">Add the first one</a>.</p>
  @endif
@endsection