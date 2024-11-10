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