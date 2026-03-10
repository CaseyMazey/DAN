<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $rolle = auth()->user()->rolle;

        return match($rolle) {
            'admin'     => view('dashboard.admin'),
            'ausbilder' => view('dashboard.ausbilder'),
            'azubi'     => view('dashboard.azubi'),
            default     => abort(403)
        };
    }
}
