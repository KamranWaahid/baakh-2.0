@extends('layouts.web')

@php
    $lang = 'sd';
@endphp
 


@section('body')
    <!-- ========== Start Body of All Poets ========== -->
    <div class="top-spacer"></div>
    <section id="inner-header" class="section-bg py-5">
        <div class="container">
            <div class="row" id="search-row">
                <div class="d-flex justify-content-between">
                    <h2 class="pb-2 text-primary">{{ trans('labels.poet_index') }}</h2>
                    <p>{{ trans_choice('labels.total_poets_found', $total_poets, ['count' => $total_poets]) }}</p>
                </div>
                <form action="{{ URL::localized(route('poets.all')) }}">
                <div class="search-box">
                    <div class="search-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <input type="text" name="query" class="search-input" placeholder="{{ trans('labels.search_placeholder') }}">
                </div>
                </form>   
            </div>

            <div class="row mt-2">
                <div class="search-options">
                    <ul class="list-inline">
                        <li class="list-inline-item"><a href="{{ URL::localized(route('poets.all')) }}">{{ trans('labels.all_poets') }}</a></li>
                        @if ($poet_tags)
                            @foreach ($poet_tags as $tag)
                                <li class="list-inline-item"><a href="{{ URL::localized(route('poets.with-tags', $tag->slug)) }}">{{ $tag->tag }}</a></li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="d-flex justify-content-aware">
                    <h3>{{ trans('labels.names') }}</h3>
                    <ul class="list-inline ms-3">
                         {!! $alphabets !!}
                    </ul>
                </div>
            </div>
            <hr>
        </div>
    </section>
    <!-- ========== End Body of All Poets ========== -->    

    <!-- ======= All Poets Section ======= -->
    <section id="poets" class="poets section-bg mt-0 pt-0">
        <div class="container" data-aos="fade-up">
         
            <div class="row gy-3">
              @foreach ($poets as $k=> $p)
              <!-- Start poets Section -->
              <div class="col-lg-2 col-md-3 col-sm-6 col-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="100">
                  <div class="poet-info">
                    <a href="{{ URL::localized(route('poets.slug', ['name' => $p->poet_slug])) }}">
                      <div class="poet-img">
                          <img src="{{ asset($p->poet_pic) }}" class="img-fluid" alt="">
                      </div>
                      <h4 class="p-2">{{ $p->details->poet_laqab }}</h4>
                    </a>
                  </div>
              </div>
              <!-- End poets Section -->
              @endforeach
          </div>        
          
    </section><!-- End Chefs Section -->

    <div class="pagination container">
        {{ $poets->withQueryString()->links() }}
    </div>
@endsection {{-- body section --}}