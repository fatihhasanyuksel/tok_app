<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckpointStageRequest;
use App\Http\Requests\UpdateCheckpointStageRequest;
use App\Models\CheckpointStage;
use Illuminate\Http\Request;

class CheckpointStageController extends Controller
{
    public function index()
    {
        $stages = CheckpointStage::ordered()->get();

        return view('admin.checkpoints.stages.index', [
            'stages' => $stages,
        ]);
    }

    public function store(StoreCheckpointStageRequest $request)
    {
        $data = $request->validated();

        // If order omitted, append to bottom
        if (!isset($data['display_order'])) {
            $data['display_order'] = (int) (CheckpointStage::max('display_order') ?? 0) + 1;
        }

        CheckpointStage::create($data);

        return back()->with('ok', 'Stage created.');
    }

    public function update(UpdateCheckpointStageRequest $request, CheckpointStage $stage)
    {
        $data = $request->validated();
        $stage->update($data);

        return back()->with('ok', 'Stage updated.');
    }

    public function destroy(CheckpointStage $stage)
    {
        // Safer than hard delete; keeps history intact
        $stage->update(['is_active' => false]);

        return back()->with('ok', 'Stage deactivated.');
    }

    public function toggle(CheckpointStage $stage)
    {
        $stage->update(['is_active' => ! $stage->is_active]);

        return back()->with('ok', 'Stage visibility toggled.');
    }

    /**
     * Reorder stages.
     * Expects payload: order[<id>] = <int>
     */
    public function reorder(Request $request)
    {
        $order = $request->input('order', []); // ['5' => 1, '7' => 2, ...]

        foreach ($order as $id => $pos) {
            CheckpointStage::where('id', (int) $id)
                ->update(['display_order' => (int) $pos]);
        }

        return back()->with('ok', 'Order updated.');
    }
}