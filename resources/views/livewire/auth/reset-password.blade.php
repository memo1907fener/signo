<div class="card p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight">{{ __('Neues Passwort festlegen') }}</h1>
    <form wire:submit="save" class="mt-6 space-y-5">
        <div>
            <label class="label" for="email">{{ __('E-Mail-Adresse') }}</label>
            <input id="email" type="email" wire:model="email" class="input">
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password">{{ __('Neues Passwort') }}</label>
            <input id="password" type="password" wire:model="password" class="input" autocomplete="new-password">
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <button class="btn-primary w-full py-3">{{ __('Speichern') }}</button>
    </form>
</div>
