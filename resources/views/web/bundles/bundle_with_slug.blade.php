@extends('layouts.web')
 


{{-- Body Section --}}
@section('body')

<!-- ========== Start Information Section ========== -->
<section id="basic-info" class="bundle-info p-0">
        
    <!---= START background image if available =--->
    <div class="bg-cover-bundle" style="background-image: url('{{  file_exists($bundle->bundle_cover) ? asset($bundle->bundle_cover) : asset('assets/img/placeholder-slider.jpg') }}'); background-size:cover;"></div>
    <!---= END background image if available =--->
    

    <!---= START container =--->
    <div class="container p-2" style=" background: var(--color-poet-sections); margin-top:-100px; min-height:120px;">
    <!---= START Poet Picture =--->
    <div class="row pt-lg-3 pt-5 pt-sm-5 ps-3 pe-3">
        <!---= START col-3 for photo =--->
        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12" id="author-picture">
            <img src="{{ file_exists($bundle->bundle_thumbnail) ? asset($bundle->bundle_thumbnail) : asset('assets/img/placeholder290x293.jpg') }}" class="img-fluid shadow" alt="">
        </div>
        <!---= END col-3 for photo =--->

        <!---= START col-9 =--->
        <div class="col-lg-10 col-md-10 col-sm-10 col-xs-12" id="poet-info">
            <!---= START d-flex and buttons with info divider =--->
            <div class="info-box">
                <!---= START basic info div =--->
                <div class="basic-info">
                    <h2 class="text-primary">
                        {{ $bundle->title; }}
                    </h2>
                    
                    <div class="bundle-info">
                        {!! $bundle->description !!}
                    </div>
                </div>
                <!---= END basic info div =--->

                <div class="buttons d-flex justify-content-between">
                    <div class="right-buttons text-center">
                        <h4 class="m-0" style="margin-bottom:-10px !important;">2</h4>
                        <span>{{ trans('buttons.likes') }}</span>
                    </div>
                    <div class="left-buttons">
                        <div class="d-flex justify-content-between">
                            
                            <x-baakh_share_buttons poetryUrl="{{ $profileUrl }}" shareText="" componentId="buttons_social_profile"  />
                            <button type="button" class="btn btn-share btn-default" data-id="buttons_social_profile"><i class="bi bi-share"></i></button>
                            <button type="button" class="btn btn-like btn-default" data-uri="{{ url('/') }}" data-type="Bundles" data-type_id="{{ $bundle->id }}"><i class="bi-solid bi-heart{{ $liked }}"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <!---= END d-flex and buttons with info divider =---> 
        </div>
        <!---= END col-9 =--->
    </div>
    <!---= END Poet Picture =--->
     
     <div class="spacer-dotted mt-3"></div>
  </div>
  <!---= END container =--->
</section>
<!-- ========== End Information Section ========== -->

<!-- ========== Start Poetry Section [sidebar advertisiments] ========== -->
<section class="poetry-section p-0 pb-4">
    <!---= START .container =--->
    <div class="container rb-5" style="background: var(--color-poet-sections);">
        <!---= START main row [divide into col-4 & col-8] =--->
        <div class="row pb-3">
            <!---= START poetry lists column [col-lg-9 col-md-8 col-sm-8 col-xs-12] =--->
            <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 pb-3 poets-stuff-col">
               
                <!---= START include poetry-list according to GIVEN CATEGORY =--->
                <div id="poetry-container">
                    {!! $bundle_poetry !!}
                </div>
                 
                <!---= END include poetry-list according to BUNDLE TYPE =--->
              
            </div>
            <!---= END poetry lists column [col-lg-9 col-md-8 col-sm-8 col-xs-12] =--->

            <!---= START sidebar column [col-lg-3 col-md-4 col-sm-4 col-xs-12] =--->
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
               <!---= START more poets =--->
               <div class="more-poets">
                <h5 class="text-center pt-3 pb-3 mb-0" style="color: var(--text-primary-color);">{{ trans('labels.couplet_bundles') }}</h5>
                <div class="spacer-dotted h"></div>
                @if ($other_bundles)
                @foreach ($other_bundles as $b)
                    <div class="bundle-item" onclick="openUrl(this)" data-url="{{ URL::localized(route('poetry.bundle.slug', $b->slug)) }}">
                        <div class="image">
                            <img src="{{ file_exists($b->bundle_thumbnail) ? asset($b->bundle_thumbnail) : asset('assets/img/placeholder290x293.jpg') }}" class="img-fluid" alt="">
                        </div>
                        <div class="info">
                            <h4 class="p-0 m-0">{{ $b->title }}</h4>
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



 

@endsection
{{-- End of Body Section --}}




{{-- CSS with Custom Styles --}}

@section('css')
<style>
</style>
@endsection

{{-- Java Script --}}
@section('js')
<script>
    $(function () {
        var loadingData = false;
        var loadedAllData = false;
        var start = 0; // Initialize start value
    
        {{--  if ("{{ $active_category }}" !== 'all') {
            loadMorePoetry(function() {
                loadingData = false;
            });
        } --}}

         
         
    
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
            var poet = {{ $bundle->id }}; // $poet->id
            var page_category = '{{ $bundle->id }}' // $show_category
            var start_from = $('.loaded-poetry').last().data('start');
            var display_items =  $('.loaded-poetry').last().data('limit');
             
            loadingData = true;
    
            $.ajax({
                url: bsurl+'/poets/poetry/load-more-poetry',
                method: 'post',
                data:{lang:site_lang, poet_id:poet, category:page_category, start:start_from, limit:display_items},
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