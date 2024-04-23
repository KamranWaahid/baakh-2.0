<div class="couplet-body text-center">
    <div class="poetry text-center">
        <h4>{{ $item->like_count }}</h4>
        <p class="m-0 p-0">{!! nl2br($item->couplets[0]->couplet_text) !!}</p>
        <span class="poet-name">{{ $item->couplets[0]->poet->details->poet_laqab }}</span>
    </div>
    <hr class="hr">
    <div class="buttons mt-2 d-flex justify-content-center">
        
        {{-- <button type="button" class="btn btn-default"><i class="bi bi-heart me-2"></i><span class="txt">{{ trans('buttons.like_it') }}</span></button> --}}
        <button type="button" class="btn btn-like btn-default" data-uri="{{ url('/') }}" data-type="Couplets" data-type_id="{{ $item->id }}"><i class="bi-solid bi-heart{{ $liked }} me-2"></i>{{ trans('buttons.like_it') }}</button>
        <button type="button" class="btn btn-default"><i class="bi bi-list me-2"></i><span class="txt">{{ trans('buttons.ghazal_parho') }}</span></button>
        <button type="button" class="btn btn-default"><i class="bi bi-share me-2"></i><span class="txt">{{ trans('buttons.share') }}</span></button>
    </div>
</div>