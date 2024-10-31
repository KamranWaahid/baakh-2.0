<div class="row poetry-list couplet-item pt-3 justify-content-center loadMorePoetry" data-contenturi="loadMoreCouplets" data-current-index="'.$index.'">
    <div class="col pb-2 text-center">
        <div class="couplet-content">
            @php
                $text =nl2br($item->couplet_text);
                $couplet_url = URL::localized(route('web.couplets.single', $item->couplet_slug))
            @endphp
            <div class="couplet" style="font-size:larger;">{!! $text !!}</div>
        </div>
        @isset($poetName)
        <div class="poet-name">
            <p>{{ $poetName }}</p>
        </div>
        @endisset
        <div class="tags-and-buttons">
            @if (isset($usedTags))
                <div class="tags text-center mt-2">
                    <ul class="list-inline p-0 m-0">
                        <span class="me-2"><i class="bi bi-tags"></i></span>
                        @foreach ($usedTags as $key => $tag)
                            <li class="list-inline-item" style="font-size: small"><a href="{{ URL::localized(route('poetry.with-tag', $key)) }}">{{ $tag }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
             
            <div class="d-flex justify-content-center mt-2">
                <button type="button" class="btn btn-default btn-share" data-id="buttons_social_couplet{{ $item->id }}" data-couplet_id="{{ $item->id }}"><i class="bi bi-share me-2"></i><span class="label">{{ trans('buttons.share') }}</span></button>
                <div class="buttons-social" id="buttons_social_couplet{{ $item->id }}">
                    <button type="button" class="btn btn-share-on btn-default" data-platform="fb" data-share_url="{{ $couplet_url }}" data-share_text="{{ $text }}"><i class="bi bi-facebook"></i></button>
                    <button type="button" class="btn btn-share-on btn-default" data-platform="tw" data-share_url="{{ $couplet_url }}" data-share_text="{{ $text }}"><i class="bi bi-twitter"></i></button>
                    <button type="button" class="btn btn-share-on btn-default" data-platform="wa" data-share_url="{{ $couplet_url }}" data-share_text="{{ $text }}"><i class="bi bi-whatsapp"></i></button>
                </div>
                <livewire:LikeCoupletButton :couplet="$item" />
            </div>
        </div>
    </div>
    <div class="spacer-dotted" style="border-bottom:1px solid var(--color-body); width:90%; margin:auto;"></div>
</div>