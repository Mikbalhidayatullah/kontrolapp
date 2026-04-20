<?php

namespace App\Http\Controllers;

use App\Models\PerjadinEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerjadinController extends Controller
{
    public function index(): View
    {
        $entries = PerjadinEntry::query()->latest('submission_date')->latest()->get();

        return view('perjadin', [
            'title' => 'Perjadin',
            'entries' => $entries,
            'summary' => [
                'totalCount' => $entries->count(),
                'waitingVerification' => $entries->where('status', 'Menunggu Verifikasi')->count(),
                'verifiedCount' => $entries->where('status', 'Terverifikasi')->count(),
                'totalBudget' => (int) $entries->sum('budget_amount'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('add-perjadin', [
            'title' => 'Tambah Perjadin',
            'transportTypes' => ['Pesawat', 'Kapal', 'Mobil Dinas', 'Mobil Sewa', 'Motor Dinas', 'Lainnya'],
            'statuses' => ['Menunggu Verifikasi', 'Terverifikasi', 'Butuh Revisi Bukti'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'submission_date' => ['required', 'date'],
            'traveler_name' => ['required', 'string', 'max:255'],
            'destination_city' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'transport_type' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'budget_amount' => ['required', 'integer', 'min:0'],
            'verified_amount' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'verifier_notes' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $proofPath = null;
        $proofOriginalName = null;

        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('proofs/perjadin', 'public');
            $proofOriginalName = $request->file('proof_file')->getClientOriginalName();
        }

        PerjadinEntry::create([
            'submission_date' => $data['submission_date'],
            'traveler_name' => $data['traveler_name'],
            'destination_city' => $data['destination_city'],
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'transport_type' => $data['transport_type'],
            'purpose' => $data['purpose'],
            'budget_amount' => $data['budget_amount'],
            'verified_amount' => $data['verified_amount'] ?? 0,
            'status' => $data['status'],
            'verifier_notes' => $data['verifier_notes'] ?? null,
            'proof_path' => $proofPath,
            'proof_original_name' => $proofOriginalName,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('perjadin')->with('status', 'Data perjadin berhasil disimpan ke database.');
    }
}
