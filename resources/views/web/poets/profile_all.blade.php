@extends('layouts.web')
 


{{-- Body Section --}}
@section('body')

<!-- ========== Start Information Section ========== -->
<section id="basic-info" class="bundle-info p-0">
        
    <!---= START background image if available =--->
    <div class="bg-cover-bundle" style="background-image: url('{{ asset('assets/images/site/baakh-header-01.jpg') }}'); background-size:cover;"></div>
    <!---= END background image if available =--->
    

    <!---= START container =--->
    <div class="container bg-white p-2" style="margin-top:-100px; min-height:120px;">
    <!---= START Poet Picture =--->
    <div class="row pt-3 ps-3 pe-3">
        <!---= START col-3 for photo =--->
        <div class="col-2" id="author-picture" style="margin-top:-50px;">
            <img src="{{ asset($profile->poet_pic) }}" class="img-fluid rounded shadow" alt="">
        </div>
        <!---= END col-3 for photo =--->

        <!---= START col-9 =--->
        <div class="col-10" id="author-info">
            <!---= START d-flex and buttons with info divider =--->
            <div class="d-flex justify-content-between">
                <!---= START basic info div =--->
                <div class="basic-info">
                    <h2 class="text-primary">
                        {{ $profile->details->poet_laqab; }}
                        <small class="text-secondary" style="font-size:1.0rem;">(<?php echo $profile->details->poet_name; ?>)</small>
                    </h2>
                    <p>
                        
                        <span><i class="lni lni-calendar mx-1"></i>{{ date('Y', strtotime($profile->date_of_birth)) }} @if ($profile->date_of_death)
                            - {{ date('Y', strtotime($profile->date_of_death)) }}
                        @endif </span>
                        <span class="ms-3"><i class="lni lni-map-marker"></i> {{ $profile->details->birthPlace->city_name }}</span>
                    </p>
                    <button type="button" class="btn btn-share btn-baakh" data-bs-toggle="modal" data-bs-target="#poetInfoDetailModal"><i class="lni lni-list"></i> {{ trans('buttons.detail') }}</button>
                </div>
                <!---= END basic info div =--->

                <div class="buttons d-flex justify-content-between">
                    <div class="right-buttons text-center">
                        <h4 class="m-0" style="margin-bottom:-10px !important;">3</h4>
                        <span>{{ trans('buttons.likes') }}</span>
                    </div>
                    <div class="left-buttons">
                        <button type="button" class="btn btn-share btn-default"><i class="bi bi-share"></i></button>
                        <button type="button" class="btn btn-share btn-default"><i class="bi bi-heart"></i></button>
                    </div>
                </div>
            </div>
            <!---= END d-flex and buttons with info divider =---> 
        </div>
        <!---= END col-9 =--->
    </div>
    <!---= END Poet Picture =--->
     
     <div class="spacer-dotted mt-3" style="border-bottom:1px dotted; width:80%; margin:auto;"></div>
  </div>
  <!---= END container =--->
</section>
<!-- ========== End Information Section ========== -->

<!-- ========== Start Poetry Section ========== -->
<section class="poetry-section p-0 pb-4">
    <!---= START .container =--->
    <div class="container bg-white rb-5">
        <!---= START main row [divide into col-4 & col-8] =--->
        <div class="row pb-3">
            <!---= START poetry lists column [col-lg-9 col-md-8 col-sm-8 col-xs-12] =--->
            <div class="col-lg-9 col-md-8 col-sm-8 col-xs-12 pb-3" style="min-height:100px;">
                <!---= START buttons for filter & total likes counter =--->
                <div class="buttons pt-2 pb-2 ps-2 pe-1">
                    
                    <a href="{{ $poet_url }}/all" class="btn btn-sm btn-secondary {{ $active_category === 'all' ? 'active' : '' }}" style="font-size:1.2rem;">سڀ</a>

                    @foreach ($categoriesWithCounts as $item)
                    <a href="{{ $poet_url }}/{{ $item->slug }}" class="btn btn-sm btn-secondary {{ $active_category === $item->slug ? 'active' : '' }}" style="font-size:1.2rem;">{{ $item->detail->cat_name }} <span style="font-size:0.8rem;">{{ $item->poetry_count }}</span></a>
                    @endforeach
                    
                </div>
                <div class="spacer-dotted" style="border-bottom:1px dotted;"></div>
                <!---= END buttons for filter & total likes counter =--->

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
            </div>
            <!---= END poetry lists column [col-lg-9 col-md-8 col-sm-8 col-xs-12] =--->

            <!---= START sidebar column [col-lg-3 col-md-4 col-sm-4 col-xs-12] =--->
            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
               <!---= START more poets =--->
               <div class="more-poets">
                <h5 class="text-primary text-center">شاعرن جي فھرست</h5>
                <ul class="list-unstyled">
                    <li><a href="">گهڻا پڙھيا ويندڙ شاعر</a></li>
                    <li><a href="">گهڻا پڙھيا ويندڙ شاعر</a></li>
                    <li><a href="">گهڻا پڙھيا ويندڙ شاعر</a></li>
                    <li><a href="">گهڻا پڙھيا ويندڙ شاعر</a></li>
                    <li><a href="">گهڻا پڙھيا ويندڙ شاعر</a></li>
                    <li><a href="">گهڻا پڙھيا ويندڙ شاعر</a></li>
                </ul>
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
                  <th class="px-2s">اصل نالو</th>
                  <td>{{ $profile->details->poet_name }}</td>
              </tr>
              <tr>
                  <th class="px-2">ڄمڻ جي تاريخ</th>
                  <td>{{ sindhi_date('D، d M Y', strtotime($profile->date_of_birth)) }}</td>
              </tr>
              <tr>
                <th class="px-2">رھائش:</th>
                <td>{{ $profile->details->birthPlace->city_name }}</td>
              </tr>

             @if ($profile->date_of_death)
              <tr>
                  <th class="px-2">وفات جي تاريخ</th>
                  <td>{{ sindhi_date('D، d M Y', strtotime($profile->date_of_death)) }}</td>
              </tr>
              <tr>
                <th class="px-2">وفات جو ھَنڌ</th>
                <td>{{ $profile->details->deathPlace->city_name }} </td>
              </tr>
              @endif

              
          </table>
          {{ $profile->details->poet_bio }}
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
    main.main-content {
        padding-top:45px;
    }
    .bg-cover-bundle {
        width: 100%;
        height: 276px;
        background-size: 100%;
        background-repeat: no-repeat;
        background-position: center top;
    }
    .rt-5{
        border-radius: 15px 15px 0px 0px;
    }
    .rb-5{
        border-radius: 0px 0px 15px 15px;
    }
  
    .poetry-list:hover {
        background:#fafafa;
        transition: background-color 0.2s ease-in-out;
        cursor: pointer;
    }

    .more-poets ul li {
        padding:10px;
    }
    .more-poets ul li:hover{
        background:#fafafa;
        transition: background-color 0.1s ease-in-out;
    }

    .more-poets ul li a{
        color:var(--color-secondary);
    }
    .more-poets ul li a:hover {
        color:var(--color-primary)
    }
</style>
@endsection

{{-- Java Script --}}
@section('js')
<script>
    $(function () {
        var loadingData = false;
        var loadedAllData = false;
        var start = 0; // Initialize start value
    
        loadMorePoetry(function() {
            loadingData = false;
        });
    
         
    
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
            var page_category = '{{ $active_category }}' // $show_category
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
    
    </script>
@endsection