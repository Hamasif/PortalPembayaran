<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Logout yang aman: invalidate session + regenerate token.
     */
    public function store(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('status_type', 'success')
            ->with('status_message', 'Anda telah berhasil keluar dari sistem.');
    }
}
