<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the main CAT interface
     */
    public function index()
    {
        return view('cat.index');
    }
}
