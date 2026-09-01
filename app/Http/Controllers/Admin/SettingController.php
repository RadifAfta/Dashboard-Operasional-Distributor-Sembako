<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Display general and system settings.
     */
    public function index(): Response
    {
        $settings = Setting::all()->groupBy('group');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings in storage.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*.key' => ['required_with:settings', 'string', 'exists:settings,key'],
            'settings.*.value' => ['nullable'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        // Handle removing existing logo
        if ($request->boolean('remove_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo) {
                $filePath = str_replace('/storage/', '', $oldLogo);
                Storage::disk('public')->delete($filePath);
            }
            Setting::set('app_logo', '', 'general', 'text', 'Logo resmi perusahaan klien');
            AuditLog::record('settings', 'Menghapus file logo perusahaan');
        }

        // Handle new logo upload
        if ($request->hasFile('logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo) {
                $filePath = str_replace('/storage/', '', $oldLogo);
                Storage::disk('public')->delete($filePath);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $logoUrl = '/storage/'.$path;
            Setting::set('app_logo', $logoUrl, 'general', 'text', 'Logo resmi perusahaan klien');
            AuditLog::record('settings', 'Mengunggah logo baru perusahaan');
        }

        if ($request->has('settings')) {
            foreach ($request->input('settings') as $item) {
                Setting::set($item['key'], $item['value'] ?? '');
            }
            AuditLog::record('settings', 'Memperbarui konfigurasi sistem');
        }

        return redirect()->route('admin.settings.index')->with('success', [
            'title' => 'Pengaturan Berhasil Disimpan',
            'message' => 'Seluruh perubahan parameter, warna brand, dan logo perusahaan telah diterapkan.',
        ]);
    }
}
