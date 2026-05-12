<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ActivityLog::query()
            ->with('user')
            ->visibleTo($request->user())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('activity-logs.index', compact('activities'));
    }
}
