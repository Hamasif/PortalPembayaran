<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    /**
     * Tampilkan halaman landing publik.
     */
    public function index()
    {
        return view('landing');
    }
}
