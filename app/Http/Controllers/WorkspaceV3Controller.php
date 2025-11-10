<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkspaceV3Controller extends Controller
{
    public function show(Request $request)
    {
        return view('workspace_v3', [
            'type'  => $request->get('type', 'submission'),
            'docId' => $request->get('doc'),
        ]);
    }
}