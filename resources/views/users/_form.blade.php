@csrf
<div class="space-y-4">
    <div>
        <label class="label" for="name">Nome e cognome *</label>
        <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
    </div>
    <div>
        <label class="label" for="email">Email *</label>
        <input class="input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
    </div>
    <div>
        <label class="label" for="role">Ruolo *</label>
        <select class="input" id="role" name="role" required>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="password">Password {{ $user->exists ? '(lascia vuoto per non cambiare)' : '*' }}</label>
            <input class="input" id="password" name="password" type="password" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
        </div>
        <div>
            <label class="label" for="password_confirmation">Conferma password</label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
        </div>
    </div>
    <p class="text-xs text-slate-400">Minimo {{ config('podo.security.password_min_length') }} caratteri, con maiuscole, minuscole, numeri e simboli.</p>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="rounded border-slate-300 text-brand-600">
        Account attivo
    </label>
</div>
