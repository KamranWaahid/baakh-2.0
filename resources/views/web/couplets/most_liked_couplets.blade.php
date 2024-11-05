@extends('layouts.web')


@section('body')
<!-- ====== Top Spacer Start ====== -->
<div class="top-spacer"></div>
<!-- ====== Top Spacer End ====== -->


<!-- ========== Start Top Section ========== -->
<section class="section-bg pb-0" id="couplet_page">
    <div class="container">
        <h3 class="text-baakh">{{ trans('labels.most_liked_couplets') }}</h3>
    </div>
</section>
<!-- ========== End Top Section ========== -->


<!-- ========== Start Pasand Kayal Shaer ========== -->
<section class="favorite-couplets pt-0">
    <div class="container">
        <div class="row">
            @foreach ($couplets as $item)
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="couplet-body text-center ">
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
            </div>
        @endforeach 
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-content-center pagination  mt-3">
            {{ $couplets->withQueryString()->links() }}
        </div>
    </div>
</section>
<!-- ========== End Pasand Kayal Shaer ========== -->



@endsection