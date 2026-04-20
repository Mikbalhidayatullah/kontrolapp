<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderByRaw("case role when 'admin' then 1 when 'bendahara' then 2 when 'verifikator' then 3 else 4 end")
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'title' => 'Kelola User',
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'title' => 'Tambah User',
            'roles' => $this->roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys($this->roles()))],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')->with('status', 'User baru berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'title' => 'Edit User',
            'userData' => $user,
            'roles' => $this->roles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys($this->roles()))],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active');

        if ($request->user()->is($user) && ! $isActive) {
            return back()->withErrors([
                'is_active' => 'Akun yang sedang dipakai tidak boleh dinonaktifkan.',
            ])->withInput();
        }

        if ($user->role === 'admin' && $data['role'] !== 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors([
                'role' => 'Minimal harus ada satu administrator aktif di sistem.',
            ])->withInput();
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $isActive,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('users.index')->with('status', 'Data user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'users' => 'Akun yang sedang dipakai tidak bisa dihapus.',
            ]);
        }

        if ($user->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->withErrors([
                'users' => 'Administrator terakhir tidak boleh dihapus.',
            ]);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User berhasil dihapus.');
    }

    private function roles(): array
    {
        return [
            'admin' => 'Administrator',
            'bendahara' => 'Bendahara',
            'verifikator' => 'Verifikator',
        ];
    }
}
