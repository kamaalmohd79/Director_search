<?php
namespace App\Http\Controllers;

use App\Models\SearchLog;

class AnalyticsController extends Controller
{
    public function index()
    {
        $total = SearchLog::count();
        $recent = SearchLog::latest()->limit(20)->get();
        return view('analytics.index', compact('total','recent'));
    }
}
