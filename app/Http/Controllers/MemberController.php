<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::when($request->search, function ($query, $search) {
            $query->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis_nim', 'like', "%{$search}%");
        })->latest()->paginate(10)->withQueryString();

        return inertia('Members/Index', [
            'members' => $members,
            'search' => $request->search,
        ]);
    }

    public function create()
    {
        return inertia('Members/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|max:255',
            'nis_nim' => 'required|max:50|unique:members',
            'no_hp' => 'nullable|max:20',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('members', 'public');
        }

        Member::create($validated);

        return redirect()->route('members.index')->with('success', 'Anggota berhasil ditambahkan');
    }

    public function show(Member $member)
    {
        //
    }

    public function edit(Member $member)
    {
        return inertia('Members/Edit', [
            'member' => $member,
        ]);
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'nama' => 'required|max:255',
            'nis_nim' => 'required|max:50|unique:members,nis_nim,' . $member->id,
            'no_hp' => 'nullable|max:20',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            // Hapus foto lama agar tidak menjadi berkas yatim
            if ($member->foto) {
                Storage::disk('public')->delete($member->foto);
            }
            $validated['foto'] = $request->file('foto')->store('members', 'public');
        } else {
            // Tanpa foto baru, buang kunci 'foto' agar foto lama tidak tertimpa kosong
            unset($validated['foto']);
        }

        $member->update($validated);

        return redirect()->route('members.index')->with('success', 'Anggota berhasil diperbarui');
    }

    public function destroy(Member $member)
    {
        // Hapus berkas foto dulu, baru barisnya
        if ($member->foto) {
            Storage::disk('public')->delete($member->foto);
        }

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Anggota berhasil dihapus');
    }
}