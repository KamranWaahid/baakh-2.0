<div class="swiper-slide">
@php
    if($item->poetry_id !==0){
      $link = '<a href="'.URL::localized(route('poetry.with-slug', ['category' => $item->poetry->category->slug, 'slug' => $item->poetry->poetry_slug])).'" class="btn btn-default"><i class="lni lni-hourglass me-2"></i>'.trans('buttons.ghazal_parho').'</a>';
    }else{
      $link = '';
    }
    $couplet_url = URL::localized(route('web.couplets.single', $item->couplet_slug));
    $text = $item->couplet_text;
@endphp
      <!--- Slide item --->
      <div class="testimonial-item">
      <div class="row gy-4 justify-content-center">
          <div class="col-lg-6">
          <div class="testimonial-content">
              <p>{!! nl2br($item->couplet_text) !!}</p>
              <h3>{{ $item->poet_laqab }}</h3>
          </div>
          <div class="buttons">
              <hr>
              @if (isset($usedTags))
                <div class="tags text-center">
                  <ul class="list-inline py-0">
                      <span class="me-2"><i class="bi bi-tags"></i></span>
                      @foreach ($usedTags as $key => $tag)
                        <li class="list-inline-item"><a href="{{ URL::localized(route('poetry.with-tag', $key)) }}">{{ $tag }}</a></li>
                      @endforeach
                  </ul>
                </div>
              @endif
              <div class="buttons d-flex justify-content-center mt-2">
                {!! $link; !!}
                <button type="button" class="btn btn-default btn-share" data-id="social_btns{{ $item->id }}" data-couplet_id="{{ $item->id }}"><i class="bi bi-share me-2"></i><span class="label">{{ trans('buttons.share') }}</span></button>
                <div class="buttons-social social_btns{{ $item->id }}" id="social_btns{{ $item->id }}">
                    <button type="button" class="btn btn-share-on btn-default" data-platform="fb" data-share_url="{{ $couplet_url }}" data-share_text="{{ $text }}"><i class="bi bi-facebook"></i></button>
                    <button type="button" class="btn btn-share-on btn-default" data-platform="tw" data-share_url="{{ $couplet_url }}" data-share_text="{{ $text }}"><i class="bi bi-twitter"></i></button>
                    <button type="button" class="btn btn-share-on btn-default" data-platform="wa" data-share_url="{{ $couplet_url }}" data-share_text="{{ $text }}"><i class="bi bi-whatsapp"></i></button>
                </div>
                <button type="button" class="btn btn-like btn-default" data-uri="{{ route('item.like-dislike') }}" data-type="Couplets" data-type_id="{{ $item->id }}"><i class="bi-solid bi-heart{{ $liked }} me-2"></i><span class="label">{{ trans('buttons.like_it') }}</span></button>
              </div>
          </div>
          </div>
      </div>
      </div>

  </div><!-- End testimonial item -->