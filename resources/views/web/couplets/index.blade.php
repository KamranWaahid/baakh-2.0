@extends('layouts.web')


@section('body')
<!-- ====== Top Spacer Start ====== -->
<div class="top-spacer"></div>
<!-- ====== Top Spacer End ====== -->


<!-- ========== Start Top Section ========== -->
<section class="section-bg" id="couplet_page">
    <div class="container">
        <h3 class="text-baakh">{{ trans('labels.couplets') }}</h3>
        <p class="text-justify" id="text-baakh-welcome">
            {{ trans('labels.welcome_to_poetry_page') }}
        </p>
    </div>
</section>

    <div class="container mt-5">
        <div class="d-flex justify-content-between">
            <h3 class="text-baakh">{{ trans_choice('labels.topic', 0, ['count' => 0]) }}</h3>
            <a href="{{ URL::localized(route('web.tags')) }}" class="btn btn-baakh">
                <span class="text">{{ trans('buttons.see_all_topics') }}</span>
                <i class="bi bi-chevron-{{ trans('buttons.i_left') }}"></i>
            </a>
        </div>
        <div class="row mt-3">
            
            @foreach ($tags as $tag)
                <div class="col-lg-2 col-md-3 col-6" id="cplts_tgs_container">
                    <a href="{{ URL::localized(route('poetry.with-tag', $tag->slug)) }}">
                        <div class="d-flex justify-content-between align-items-center"  id="cplts_tgs">
                            <div class="letter text-center" style="min-width: 40px">
                                <span>{{ Str::limit($tag->tag, 1, '') }}</span>
                            </div>
                            <div class="word">
                                {{ $tag->tag }}
                            </div>
                            <div class="icon">
                                <i class="bi bi-chevron-{{ trans('buttons.i_left') }}"></i>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        
    </div>
</section>
<!-- ========== End Top Section ========== -->


<!-- ========== Start Pasand Kayal Shaer ========== -->
<section class="favorite-couplets">
    <div class="container">
        <div class="row">
            <h3 class="text-baakh mt-2 title">{{ trans('labels.most_liked_couplets') }}</h3>
            <div class="col-lg-6 col-md-6 col-sm-12" id="right-side-poetry">
                @foreach ($topCouplets['left'] as $item)
                    <div class="couplet-body text-center">
                        <div class="poetry text-center">
                            <p class="m-0 p-0">{!! nl2br($item->couplet_text) !!}</p>
                            <span class="poet-name">
                                <a href="{{ URL::localized(route('poets.slug', ['name' => $item->poet->poet_slug])) }}">{{ $item->poet_laqab }}</a>
                            </span>
                        </div>
                        <hr class="hr">
                        <div class="buttons mt-2 d-flex justify-content-center">
                            <livewire:LikeCoupletButton :couplet="$item" />
                            @if ($item->poetry)
                                <a href="{{ URL::localized(route('poetry.with-slug', ['category' => $item->poetry->category_slug , 'slug' => $item->poetry->poetry_slug])) }}" class="btn btn-default"><i class="bi bi-list me-2"></i><span class="txt">{{ trans('buttons.ghazal_parho') }}</span></a>
                            @endif
                            <button type="button" class="btn btn-default"><i class="bi bi-share me-2"></i><span class="txt">{{ trans('buttons.share') }}</span></button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12" id="left-side-poetry">
                @foreach ($topCouplets['right'] as $item)
                    <div class="couplet-body text-center">
                        <div class="poetry text-center">
                            <p class="m-0 p-0">{!! nl2br($item->couplet_text) !!}</p>
                            <span class="poet-name">
                                <a href="{{ URL::localized(route('poets.slug', ['name' => $item->poet->poet_slug])) }}">{{ $item->poet_laqab }}</a>
                            </span>
                        </div>
                        <hr class="hr">
                        <div class="buttons mt-2 d-flex justify-content-center">
                            <livewire:LikeCoupletButton :couplet="$item" />
                            @if ($item->poetry)
                                <a href="{{ URL::localized(route('poetry.with-slug', ['category' => $item->poetry->category_slug , 'slug' => $item->poetry->poetry_slug])) }}" class="btn btn-default"><i class="bi bi-list me-2"></i><span class="txt">{{ trans('buttons.ghazal_parho') }}</span></a>
                            @endif
                            {{-- <button type="button" class="btn btn-default"><i class="bi bi-list me-2"></i><span class="txt">{{ trans('buttons.ghazal_parho') }}</span></button> --}}
                            <button type="button" class="btn btn-default"><i class="bi bi-share me-2"></i><span class="txt">{{ trans('buttons.share') }}</span></button>
                        </div>
                    </div>
                @endforeach
                <div class="more-button">
                    <a href="{{ URL::localized(route('web.couplets.most-liked')) }}" class="btn btn-baakh">{{ trans('buttons.see_favorite_couplets', ['count'=> 400]) }}<i class="bi bi-chevron-{{ trans('buttons.i_left') }}" style="margin-{{ trans('buttons.i_right') }}:8px;"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ========== End Pasand Kayal Shaer ========== -->


<!-- ========== Start Couplet Bundles ========== -->
<section class="couplet-bundles mt-5">
    <div class="container">
        <h3 class="section-title text-baakh">{{ trans('labels.couplet_bundles') }}</h3>

        <!--- .couplet-bundles-slider --->
        <div class="couplet-bundles-slider swiper mt-5">
            <div class="swiper-wrapper align-items-center">
                @foreach ($bundles as $k => $p)
                <!--- .swiper-slider #slide_item --->
                <div class="swiper-slide" id="slider_item">
                <a href="{{ URL::localized(route('poetry.bundle.slug', $p->slug)) }}">
                    <img src="{{ file_exists($p->bundle_thumbnail) ? asset($p->bundle_thumbnail) : asset('assets/img/placeholder290x293.jpg') }}" class="img-fluid" alt="">
                </a>
                <p id="poets-slider">{{ $p->title }}</p>
                </div>
                <!--- /.swiper-slider #slide_item --->
                
                @endforeach
            </div>
            <button class="carousel-btn carousel-3-btn-prev" style="padding-bottom: 80px;">&#8249;</button>
            <button class="carousel-btn carousel-3-btn-next"style="padding-bottom: 80px;">&#8250;</button>
        </div>
        <!--- /.couplet-bundles-slider --->
    </div>
</section>
<!-- ========== End Couplet Bundles ========== -->

@endsection

@section('js')
<livewire:LoginModal /> 
@endsection