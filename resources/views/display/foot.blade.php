{{-- Fußzeile mit automatischer Allergen-Legende --}}
@if (! empty($d['footer']) || $legendTags)
    <footer class="sg-foot">
        @if (! empty($d['footer']))<span>{{ $d['footer'] }}</span>@endif
        @if ($legendTags)
            <span class="sg-legend">@foreach ($legendTags as $t)<span><b>{{ $t }}</b> {{ $tagLabels[$t] ?? '' }}</span>@endforeach</span>
        @endif
    </footer>
@endif
