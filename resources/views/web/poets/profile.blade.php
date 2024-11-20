@extends('layouts.web')
 

@php
    $profileUrl = URL::localized(route('poets.slug', $profile->poet_slug));
@endphp


{{-- Body Section --}}
@section('body')

    @include('web.poets.partials.poet_header')

<!-- ========== Start Poetry Section [sidebar advertisiments] ========== -->
<section class="poetry-section p-0 pb-4">
    <!---= START .container =--->
    <div class="container rb-5" style="background: var(--color-poet-sections);">
        <!---= START main row [divide into col-4 & col-8] =--->
        <div class="row pb-3">
            <!---= START poetry lists column [col-lg-9 col-md-8 col-sm-8 col-xs-12] =--->
            <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 pb-3 poets-stuff-col">
                <!---= START buttons for filter & total likes counter =--->
                <div class="category-buttons pt-2 pb-3 ps-2 pe-1 ">
                    
                    <a href="{{ URL::localized($poet_url.'/all') }}" class="btn btn-sm btn-secondary btn-gol {{ $active_category === 'all' ? 'active' : '' }}" style="font-size:1.2rem;">{{ trans('buttons.all') }}</a>
                    @if ($total_couplets > 0)    
                        <a href="{{ URL::localized($poet_url.'/couplets') }}" class="btn btn-sm btn-secondary btn-gol {{ $active_category === 'couplets' ? 'active' : '' }}" style="font-size:1.2rem;">{{ trans('menus.couplets') }} <span style="font-size:0.8rem;">{{ $total_couplets }}</span></a>
                    @endif

                    @foreach ($categoriesWithCounts as $item)
                    <a href="{{ URL::localized($poet_url.'/'.$item->slug) }}" class="btn btn-sm btn-secondary btn-gol {{ $active_category === $item->slug ? 'active' : '' }}" style="font-size:1.2rem;">{{ Str::ucfirst($item->detail->cat_name_plural) }} <span style="font-size:0.8rem;">{{ $item->poetry_count }}</span></a>
                    @endforeach
                    
                </div>
                <div class="spacer-dotted h"></div>
                <!---= END buttons for filter & total likes counter =--->

                @if ($poetry_limited !=null)
                    {!! $poetry_limited !!}
                @else
                <!---= START include poetry-list according to GIVEN CATEGORY =--->
                <div id="poetry-container">
                    <div class="loaded-poetry" data-limit="10" data-start="0"></div>
                </div>
                <div class="loader_spinner">
                    <div class="d-flex align-items-center justify-content-center p-5">
                        <div class="spinner-grow text-danger" role="status"></div>
                    </div>
                </div>
                <!---= END include poetry-list according to BUNDLE TYPE =--->
                @endif
            </div>

            <!---start[separateor ]--->
            <div class="category-buttons pt-2 pb-3 ps-2 pe-1 d-md-none d-xs-block">
                    
                <a href="{{ URL::localized($poet_url.'/all') }}" class="btn btn-sm btn-secondary btn-gol {{ $active_category === 'all' ? 'active' : '' }}" style="font-size:1.2rem;">{{ trans('buttons.all') }}</a>
                @if ($total_couplets > 0)    
                    <a href="{{ URL::localized($poet_url.'/couplets') }}" class="btn btn-sm btn-secondary btn-gol {{ $active_category === 'couplets' ? 'active' : '' }}" style="font-size:1.2rem;">{{ trans('menus.couplets') }} <span style="font-size:0.8rem;">{{ $total_couplets }}</span></a>
                @endif

                @foreach ($categoriesWithCounts as $item)
               
                <a href="{{ URL::localized($poet_url.'/'.$item->slug) }}" class="btn btn-sm btn-secondary btn-gol {{ $active_category === $item->slug ? 'active' : '' }}" style="font-size:1.2rem;">{{ Str::ucfirst($item->cat_name) }} <span style="font-size:0.8rem;">{{ $item->poetry_count }}</span></a>
                @endforeach
                
            </div>
            <!---end[separateor ]--->
            <!---= END poetry lists column [col-lg-9 col-md-8 col-sm-8 col-xs-12] =--->

            <!---= START sidebar column [col-lg-3 col-md-4 col-sm-4 col-xs-12] =--->
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                <div class="separator d-lg-none d-xs-block" style="border-top: 4px solid black;"></div>
               <!---= START more poets =--->
               <div class="more-poets">
                <h5 class="text-center pt-3 pb-3 mb-0" style="color: var(--text-primary-color);">{{ trans('labels.famous_poets') }}</h5>
                <div class="spacer-dotted h"></div>
                @if ($famous_poets)
                @foreach ($famous_poets as $p)
                    <div class="poet-item" onclick="openUrl(this)" data-url="{{ URL::localized(route('poets.slug', $p->poet_slug)) }}">
                        <div class="image">
                            <img src="{{ url($p->poet_pic) }}" class="img-fluid" alt="">
                        </div>
                        <div class="info">
                            <h4 class="p-0 m-0">{{ $p->details->poet_laqab }}</h4>
                            <span class="tagline">{{ $p->details->tagline }}</span>
                        </div>
                    </div>   
                @endforeach
                @endif

                
               
               </div>
               <!---= END more poets =--->
            </div>
            <!---= END sidebar column [col-lg-3 col-md-4 col-sm-4 col-xs-12] =--->
        </div>
        <!---= END main row [divide into col-4 & col-8] =--->
    </div>
    <!---= END .container =--->
</section>
<!-- ========== End Poetry Section [sidebar advertisiments] ========== -->




<!---= START poetInfoDetailModal =--->
<div class="modal fade" id="poetInfoDetailModal" tabindex="-1" aria-labelledby="poetInfoDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="poetInfoDetailModalLabel">{{ $profile->details->poet_laqab }}</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <table>
              <tr>
                  <th class="px-2s">{{ trans('labels.o_name') }}</th>
                  <td>{{ $profile->details->poet_name }}</td>
              </tr>
              @if ($profile->details->pen_name)
              <tr>
                <th class="px-2s">{{ trans('labels.p_name') }}</th>
                <td>{{ $profile->details->pen_name }}</td>
              </tr>
              @endif
              <tr>
                  <th class="px-2">{{ trans('labels.dob') }}</th>
                  <td>{{ sindhi_date('D، d M Y', strtotime($profile->date_of_birth)) }}</td>
              </tr>
              <tr>
                <th class="px-2">{{ trans('labels.birth_place') }}:</th>
                <td>{{ $profile->details->birthPlace->city_name }}</td>
              </tr>

             @if ($profile->date_of_death)
              <tr>
                  <th class="px-2">{{ trans('labels.dod') }}</th>
                  <td>{{ sindhi_date('D، d M Y', strtotime($profile->date_of_death)) }}</td>
              </tr>
              <tr>
                <th class="px-2">{{ trans('labels.death_place') }}</th>
                <td>{{ $profile->details->deathPlace->city_name }} </td>
              </tr>
              @endif

              
          </table>
          {!! nl2br($profile->details->poet_bio) !!}
        </div>
      </div>
    </div>
</div>
<!---= END poetInfoDetailModal =--->

@endsection
{{-- End of Body Section --}}



{{-- CSS with Custom Styles --}}

@section('css')
<style>
</style>
@endsection

{{-- Java Script --}}
@section('js')
<livewire:LoginModal />
<script>
    $(function () {
        var loadingData = false;
        var loadedAllData = false;
        var start = 0; // Initialize start value
    
        if ("{{ $active_category }}" !== 'all') {
            loadMorePoetry(function() {
                loadingData = false;
            });
        }
 
         
    
        // Event listener for scroll events
        $(window).scroll(function() {
            const poetryContainer = $('#poetry-container');
            const lastPoetry = poetryContainer.children('.loadMorePoetry').last();
    
            // Check if the last couplet is visible on the screen
            if (lastPoetry.length > 0) {
                const lastPoetryOffset = lastPoetry.offset().top + lastPoetry.outerHeight();
                const pageOffset = $(window).scrollTop() + $(window).height();
                const bottomOffset = 200; // Bottom margin to trigger the event
    
                if (pageOffset > lastPoetryOffset - bottomOffset && !loadingData && !loadedAllData) {
                    // Load more couplets
                    loadingData = true;
                    loadMorePoetry(function() {
                        loadingData = false;
                    });
                }
            }
        });
    
        function loadMorePoetry() {
             
    
            // Assign data to variables
            var bsurl = '{!! url('/') !!}';
            var site_lang = '{{ app()->getLocale() }}' // selected language
            var poet = {{ $profile->id }}; // $poet->id
            var page_category = '{{ $active_category }}'; // $show_category
            var page_category_id = '{{ $active_category_id }}';
            var start_from = $('.loaded-poetry').last().data('start');
            var display_items =  $('.loaded-poetry').last().data('limit');
             
            loadingData = true;
    
            $.ajax({
                url: bsurl+'/poets/poetry/load-more-poetry',
                method: 'post',
                data:{lang:site_lang, poet_id:poet, category:page_category, category_id:page_category_id, start:start_from, limit:display_items},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                beforeSend:function(){
                    $(".loader_spinner").show();
                },
                success:function(result){
                    if(result.type === 'success'){
                        loadingData = false;
                        $('#poetry-container').append(result.html)
                    }
                    $(".loader_spinner").hide();
                    loadingData = false;
    
                    if(result.code == 204){
                        loadedAllData = true;
                    }
                },
                error:function(xhr, status, errorThrown){
                    loadingData = false;
                    $(".loader_spinner").hide();
                    console.log('error called =--- ' + xhr.responseText)
                }
            });
        }
     
    });

    function openUrl(item)
    {
        window.location.href=$(item).data('url')
    }
    
    </script>
@endsection