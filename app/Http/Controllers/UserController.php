<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Gestione utenti (admin e superadmin).
 * Un admin NON vede e NON puo gestire i superadmin, ne assegnare quel ruolo.
 */
class UserController extends Controller
{
    /** Ruoli che l attore corrente puo assegnare. */
    private function assignableRoles(User $actor): array
    {
        return $actor->isSuperAdmin()
            ? Role::cases()
            : [Role::ADMIN, Role::USER];
    }

    /** Blocca (rende invisibile) la gestione di un superadmin da parte di un admin. */
    private function guard(User $actor, User $target): void
    {
        if (! $actor->isSuperAdmin() && $target->isSuperAdmin()) {
            abort(404);
        }
    }

    public function index(Request $request)
    {
        $users = User::query()
            ->with('patient')
            ->when(! $request->user()->isSuperAdmin(), fn ($q) => $q->where('role', '!=', Role::SUPERADMIN->value))
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(Request $request)
    {
        return view('users.create', [
            'user' => new User(),
            'roles' => $this->assignableRoles($request->user()),
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $user = User::create($data);

        Log::channel('audit')->info('user.created', ['by' => $request->user()->id, 'user_id' => $user->id, 'role' => $user->role->value]);
        activity('utenti')->causedBy($request->user())->performedOn($user)
            ->event('created')->withProperties(['role' => $user->role->value, 'email' => $user->email])
            ->log('Utente creato');

        return redirect()->route('users.index')->with('success', 'Utente creato.');
    }

    public function edit(Request $request, User $user)
    {
        $this->guard($request->user(), $user);

        return view('users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles($request->user()),
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->guard($request->user(), $user);
        $data = $this->validateData($request, $user);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        Log::channel('audit')->info('user.updated', ['by' => $request->user()->id, 'user_id' => $user->id]);
        activity('utenti')->causedBy($request->user())->performedOn($user)
            ->event('updated')->log('Utente aggiornato');

        return redirect()->route('users.index')->with('success', 'Utente aggiornato.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->guard($request->user(), $user);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Non puoi disattivare il tuo stesso account.');
        }

        $user->update(['is_active' => false]);

        Log::channel('audit')->warning('user.deactivated', ['by' => $request->user()->id, 'user_id' => $user->id]);
        activity('utenti')->causedBy($request->user())->performedOn($user)
            ->event('deactivated')->log('Utente disattivato');

        return back()->with('success', 'Utente disattivato.');
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        $assignable = collect($this->assignableRoles($request->user()))->map(fn ($r) => $r->value)->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in($assignable)],
            'patient_id' => ['nullable', 'exists:patients,id', Rule::unique('users', 'patient_id')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Il collegamento all anagrafica ha senso solo per il ruolo "user" (paziente)
        if ($data['role'] === Role::USER->value) {
            $request->validate(['patient_id' => ['required', 'exists:patients,id']]);
        } else {
            $data['patient_id'] = null;
        }
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
