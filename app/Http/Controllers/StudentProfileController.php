<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    /**
     * Halaman profil mahasiswa: identitas resmi read-only.
     */
    public function edit(Request $request)
    {
        return view('student.profile.edit', [
            'user' => $request->user(),
            'student' => $request->user()->student,
        ]);
    }

    /**
     * Mahasiswa TIDAK dapat mengubah NIM/nama/prodi (identitas resmi).
     * Yang dapat diubah hanya preferensi tampilan (mis. gelar panggilan).
     * Endpoint ini sengaja tidak mengubah kolom data resmi apa pun.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:60'],
        ]);

        // MVP: profil resmi bersifat read-only. Tidak ada kolom yang diubah.
        // display_name disimpan ke session sebagai preferensi tampilan sederhana.
        $request->session()->put('display_name', $validated['display_name'] ?? null);

        return back()
            ->with('status_type', 'success')
            ->with('status_message', 'Pengaturan tampilan tersimpan. Data identitas resmi tidak diubah.');
    }
}
