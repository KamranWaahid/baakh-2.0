<div class="poetry-list poetry-item pt-3 loadMorePoetry">
    <div class="poetry-list-body">
        <div class="d-flex align-items-center">
            <livewire:LikePoetryButton :poetry="$item" />
            <div class="flex-grow-1 ps-2">

                <a href="{{ URL::localized(route('poetry.with-slug', ['category' => $item->category->slug, 'slug' => $item->poetry_slug ])) }}">
                    <h5 class="p-0 m-0">{{ Str::ucfirst($item->info->title) }}</h5>
                    @isset($poetName)
                    <span>{{ $poetName }}</span>
                    @endisset
                </a>
            </div>
            @if (count($item->media) > 0)
                @if ($item->media[0]->media_type == 'video')
                    <button type="button" class="btn btn-youtube btn-default"><i class="bi bi-youtube"></i></button>
                @endif
                @if ($item->media[0]->media_type == 'autio')
                    <button type="button" class="btn btn-music btn-default"><i class="bi bi-volume-up"></i></button>
                @endif
            @endif
            
            
            
        </div>
    </div>
</div>