<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
public function index(Request $request)
{
    $data = $request->validate([
        'q' => ['nullable','string','max:255'],
        'per_page' => ['nullable','integer','min:1','max:100'],
    ]);

    $q = trim($data['q'] ?? '');
    $perPage = $data['per_page'] ?? 10;

    $users = User::query()
        ->with('roles')
        ->when($q !== '', function ($query) use ($q) {
            // If numeric => treat as ID
            if (ctype_digit($q)) {
                $query->where('id', (int)$q);
                return;
            }

            // If contains @ => treat as email search
            if (str_contains($q, '@')) {
                $query->where('email', 'like', "%{$q}%");
                return;
            }

            // Otherwise treat as name search (and also email fallback)
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        })
        ->orderByDesc('id')
        ->paginate($perPage);

    return response()->json($users);
}


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('users','email')],
            'password' => ['required','string','min:8'],
            'role' => ['required', Rule::in(['student','faculty'])],
            'is_active' => ['sometimes','boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
            'must_change_password' => true,
        ]);

        $user->assignRole($data['role']);

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
            ]
        ], 201);
    }

    public function setStatus(Request $request, User $user)
    {
        $data = $request->validate([
            'is_active' => ['required','boolean'],
        ]);

        // Prevent disabling self
        if ($request->user()->id === $user->id && $data['is_active'] === false) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.'
            ], 422);
        }

        $user->is_active = $data['is_active'];
        $user->save();

        return response()->json(['ok' => true, 'is_active' => $user->is_active]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required','string','min:8'],
        ]);

        $user->password = Hash::make($data['password']);
        $user->must_change_password = true;
        $user->save();

        // revoke tokens so old sessions are invalid
        $user->tokens()->delete();

        return response()->json(['ok' => true]);
    }
}
