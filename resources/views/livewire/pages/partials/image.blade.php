@php $current = $data[$key] ?? null; $media = $media ?? false; @endphp
<div>
    <span class="label">{{ __($label) }}</span>
    <div class="flex flex-wrap items-center gap-3">
        @if ($current)
            @if ($media && str_starts_with((string) ($data['mime'] ?? ''), 'video/'))
                <video src="{{ \App\Models\Page::fileUrl($current) }}" class="h-20 w-32 rounded-xl bg-black object-cover" muted></video>
            @else
                <img src="{{ \App\Models\Page::fileUrl($current) }}" class="h-20 w-32 rounded-xl object-cover ring-1 ring-black/10" alt="">
            @endif
        @endif
        <label class="btn-ghost btn-sm cursor-pointer">
            <input type="file" wire:model="image" class="sr-only" accept="{{ $media ? 'image/*,video/mp4,video/webm' : 'image/jpeg,image/png,image/webp' }}">
            {{ __($current ? 'Ersetzen' : 'Datei wählen') }}
        </label>
        @if ($photosReady)
            <button wire:click="openPhotos" class="btn-ghost btn-sm">{{ __('🔎 Fotobibliothek') }}</button>
        @endif
        @if ($current)
            <button wire:click="removeImage" class="text-sm text-red-600 hover:underline">{{ __('Entfernen') }}</button>
        @endif
        <span wire:loading wire:target="image" class="text-sm text-muted">{{ __('Wird hochgeladen …') }}</span>
    </div>
    @error('image') <p class="error">{{ $message }}</p> @enderror
    @if ($media) <p class="hint">{{ __('Bilder bis 15 MB, Videos (MP4) bis 100 MB. Videos laufen ohne Ton.') }}</p> @endif
</div>

@if ($photoOpen)
    <div class="mt-4 rounded-2xl bg-paper p-4 ring-1 ring-black/5">
        <form wire:submit="searchPhotos" class="flex gap-2">
            <input wire:model="photoQuery" class="input" placeholder="{{ __('z. B. coffee, pizza, haircut') }}">
            <button class="btn-primary btn-sm shrink-0">{{ __('Suchen') }}</button>
            <button type="button" wire:click="$set('photoOpen', false)" class="btn-ghost btn-sm shrink-0">×</button>
        </form>
        <div wire:loading wire:target="searchPhotos,openPhotos,pickPhoto" class="mt-3 text-sm text-muted">{{ __('Einen Moment …') }}</div>
        @if ($photoError) <p class="error mt-3">{{ $photoError }}</p> @endif
        <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
            @foreach ($photos as $i => $ph)
                <button type="button" wire:click="pickPhoto({{ $i }})" class="group relative aspect-[4/3] overflow-hidden rounded-xl ring-1 ring-black/10" title="{{ __('Foto') }}: {{ $ph['by'] }}">
                    <img src="{{ $ph['thumb'] }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" referrerpolicy="no-referrer">
                </button>
            @endforeach
        </div>
        @if ($photos) <p class="hint mt-2">{{ __('Fotos von Pexels – kostenlos und ohne Namensnennung nutzbar.') }}</p> @endif
    </div>
@endif
