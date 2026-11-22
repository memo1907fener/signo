@php $current = $data[$key] ?? null; $media = $media ?? false; @endphp
<div>
    <span class="label">{{ $label }}</span>
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
            {{ $current ? 'Ersetzen' : 'Datei wählen' }}
        </label>
        @if ($current)
            <button wire:click="removeImage" class="text-sm text-red-600 hover:underline">Entfernen</button>
        @endif
        <span wire:loading wire:target="image" class="text-sm text-muted">Wird hochgeladen …</span>
    </div>
    @error('image') <p class="error">{{ $message }}</p> @enderror
    @if ($media) <p class="hint">Bilder bis 15 MB, Videos (MP4) bis 100 MB. Videos laufen ohne Ton.</p> @endif
</div>
