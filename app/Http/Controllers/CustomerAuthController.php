<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class CustomerAuthController extends Controller
{
    public function showRegister(): View
    {
        return view('shop.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $username = trim((string) ($validated['username'] ?? ''));
            if ($username === '') {
                $base = Str::of($validated['email'])->before('@')->slug('_')->value() ?: 'customer';
                $candidate = $base;
                $suffix = 1;
                while (User::query()->where('username', $candidate)->exists()) {
                    $candidate = "{$base}_{$suffix}";
                    $suffix++;
                }
                $username = $candidate;
            }

            $user = User::query()->create([
                'name' => $validated['name'],
                'username' => $username,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            Role::firstOrCreate([
                'name' => 'customer',
                'guard_name' => 'web',
            ]);
            $user->assignRole('customer');

            Customer::query()->firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                ]
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('shop.index')->with('success', 'Akun berhasil dibuat. Selamat berbelanja!');
    }
}
