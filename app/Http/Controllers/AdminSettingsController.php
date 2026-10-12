<?php

namespace App\Http\Controllers;

use App\Models\AllowedEmailDomain;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    /**
     * Pengaturan domain email (khusus super_admin).
     */
    public function domains()
    {
        return view('admin.settings.domains', [
            'domains' => AllowedEmailDomain::orderBy('domain')->get(),
            'configDomains' => config('portal.allowed_email_domains', []),
        ]);
    }

    public function storeDomain(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9.\-]+\.[a-z]{2,}$/i', 'unique:allowed_email_domains,domain'],
        ], [
            'domain.regex' => 'Format domain tidak valid (contoh: student.universitas.ac.id).',
        ]);

        $domain = AllowedEmailDomain::create([
            'domain' => strtolower(trim($validated['domain'])),
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record($request->user()->id, 'domain.added', 'allowed_email_domain', $domain->id, [
            'domain' => $domain->domain,
        ]);

        return back()->with('status_type', 'success')
            ->with('status_message', 'Domain '.$domain->domain.' ditambahkan.');
    }

    public function toggleDomain(Request $request, AllowedEmailDomain $domain)
    {
        $domain->update(['is_active' => ! $domain->is_active]);

        AuditLog::record($request->user()->id, 'domain.toggled', 'allowed_email_domain', $domain->id, [
            'domain' => $domain->domain,
            'is_active' => $domain->is_active,
        ]);

        return back()->with('status_type', 'success')
            ->with('status_message', 'Domain '.$domain->domain.($domain->is_active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function destroyDomain(Request $request, AllowedEmailDomain $domain)
    {
        $name = $domain->domain;
        $domain->delete();

        AuditLog::record($request->user()->id, 'domain.removed', 'allowed_email_domain', null, [
            'domain' => $name,
        ]);

        return back()->with('status_type', 'success')
            ->with('status_message', 'Domain '.$name.' dihapus.');
    }
}
