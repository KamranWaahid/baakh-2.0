<div class="poet-item d-flex justify-content-between" id="poet_{{ $item->poet_id }}" onclick="openUrl(this)" data-url="{{ URL::localized(route('poets.slug', $item->poet_slug)) }}">
    <div class="d-flex justify-content-between">
        <div class="image">
            <img src="{{ url($item->poet_pic) }}" class="img-fluid" alt="">
        </div>
        <div class="info">
            <h4 class="p-0 m-0">{{ $item->poet_laqab }}</h4>
            <span class="tagline">{{ $item->tagline }}</span>
        </div>
        
    </div>
    <div class="px-3">
        <i class="bi bi-chevron-left"></i>
    </div>
</div>
