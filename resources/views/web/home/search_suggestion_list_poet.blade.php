<a href="{{ $link }}">
    <div class="hero-suggestion-item">
        <img src="{{ $image }}" alt="poet image" width="35px" style="border-radius: 2px;" height="auto">
        <div class="name">
            <p class="p-0 m-0" style="font-size: 1rem;">{{ $text }}</p>
            <small>{{ trans('labels.search_poet') }} - {{ $poet_name }}</small>
        </div>
    </div>
</a>