@extends('layouts.web')
 

@if (isset($meta_tags))
    @section('og_title')
    {{ $meta_tags['og_title'] }}
    @endsection
@endif


@section('body')
    <!-- ========== Start Body of All Poets ========== -->
    <div class="top-spacer"></div>
    <section id="inner-header" class="section-bg py-5">
        <div class="container">
            <div class="row" id="search-row">
                <div class="d-flex justify-content-between">
                    <h2 class="pb-2">
                        <a href="{{ URL::localized(route('poets.all')) }}" class="text-secondary"><i class="bi bi-arrow-{{ trans('buttons.i_right') }}"></i></a>
                        <span class="text-primary">{{ $active_tag->tag }}</span>
                    </h2>
                    <p>{{ trans_choice('labels.total_poets_found', count($poets), ['count' => count($poets)]) }}</p>
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
              <div class="col-lg-2 col-md-3 col-sm-6 d-flex align-items-stretch" data-aos="fade-up" data-aos-delay="100">
                  <div class="poet-info">
                    <a href="{{ route('poets.slug', ['name' => $p->poet_slug]) }}">
                      <div class="poet-img">
                          <img src="{{ file_exists($p->poet_pic) ? asset($p->poet_pic) : asset('assets/img/placeholder290x293.jpg') }}" class="img-fluid" alt="">
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
        {{ $poets->links() }}
    </div>
@endsection {{-- body section --}}