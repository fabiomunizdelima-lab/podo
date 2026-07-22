<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Gestione utenti — riservata a superadmin (vedi rotte).
 * Checklist: IAM, Account Lifecycle, Logging amministratori.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', ['user' => new User(), 'roles' => Role::cases()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = User::create($data);

        Log::channel('audit')->info('user.created', [
            'by' => $request->user()->id,
            'user_id' => $user->id,
            'role' => $user->role->value,
        ]);

        return redirect()->route('users.index')->with('success', 'Utente creato.');
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::cases()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        Log::channel('audit')->info('user.updated', [
            'by' => $request->user()->id,
            'user_id' => $user->id,
        ]);

        return redirect()->route('users.index')->with('success', 'Utente aggiornato.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Non puoi disattivare il tuo stesso account.');
        }

        // Disattivazione (lifecycle) invece di cancellazione fisica
        $user->update(['is_active' => false]);

        Log::channel('audit')->warning('user.deactivated', [
            'by' => $request->user()->id,
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Utente disattivato.');
    }
}
