<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facility;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all facilities
        $facilities = Facility::all();

        // Pass to the dashboard view
        return view('admin.dashboard', compact('facilities'));
    }
}
