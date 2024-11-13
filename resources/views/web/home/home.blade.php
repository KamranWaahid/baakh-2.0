@extends('layouts.web')

@section('body')

<section class="hero" style="height: 100vh !important;">
  <div class="container">
    <div class="col-md-9 col-12 m-auto">
        @if (isset($doodles) && $doodles->reference)
          @php
              $ref=  $doodles->reference;
              $dateOfBirth = $ref->date_of_birth ? \Carbon\Carbon::parse($ref->date_of_birth)->locale(app()->getLocale()) : null;
              $dateOfDeath = $ref->date_of_death ? \Carbon\Carbon::parse($ref->date_of_death)->locale(app()->getLocale()) : null;
          @endphp

          <a href="{{ URL::localized(route('poets.slug', $ref->poet_slug)) }}">
            <img src="{{ file_exists($doodles->image) ? asset($doodles->image) : asset('assets/img/Baakh-beta.svg') }}" 
            class="main-slider-img" 
            alt="{{ $doodles->title }}">
          </a>
            <h2 class="text-center">{{ $ref->poet_laqab }}</h2>
            <p class="text-center">
                {{ trans('labels.from_date_to_date', [
                    'fromDate' => $dateOfBirth ? $dateOfBirth->translatedFormat('d F, Y') : '',
                    'toDate' => $dateOfDeath ? $dateOfDeath->translatedFormat('d F, Y') : ''
                ]) }}
            </p>

        @elseif (isset($doodles))
          <img src="{{ file_exists($doodles->image) ? asset($doodles->image) : asset('assets/img/Baakh-beta.svg') }}" 
          class="main-slider-img" 
          alt="{{ $doodles->title }}">

        @else
          <img src="{{ asset('assets/img/Baakh-beta.svg') }}" 
          class="center" 
          height="300px"
          alt="Baakh Poetry">
          <h2 class="text-center">{{ trans('labels.title') }}</h2>
        @endif
        @include('web.home.search_box')
    </div>
  </div>
</section>

{{-- random poetry --}}
@if (isset($random_poetry))
    <!-- ======= Today'sPoetryHeader ======= -->
    <section id="top10poetry" class="top10poetry">
        <div class="container" data-aos="fade-up">

        <div class="section-header">
            <p>{{ trans('labels.bundle_of_10c') }}</p>
        </div>

        <div class="slides-1" data-aos="fade-up" data-aos-delay="200">
            <div class="swiper-wrapper">
            {!! $random_poetry !!}
            </div>
        
            <div class="swiper-pagination"></div>
      </div>
        <button class="carousel-btn carousel-2-btn-prev">&#8249;</button>
        <button class="carousel-btn carousel-2-btn-next">&#8250;</button>
        </div>
    </section>
    <!-- ======= END Today'sPoetryHeader ======= -->
@endif


@if (isset($famous_poet))
     <!-- ========== Start Famous Poets ========== -->
  <section id="about" class="about">
    <!--.container-->
    <div class="container" data-os="fade-up">
      <!-- #gallery.section-bg -->
      <div id="gallery" class="gallery section-bg">
        <!-- .section-header -->
        <div class="section-header">
          <p>{{ trans('labels.poet_index') }}</p>
        </div>
        <!-- .section-header -->

        <!--- .poet-list-buttons --->
        @if ($poet_tags)
        <div id="poetry-list-btn">
          @foreach ($poet_tags as $tag)
            <a class="TodaysPoetrybtn" href="{{ URL::localized(route('poets.with-tags', $tag->slug)) }}">{{ $tag->tag }}</a>
          @endforeach
        </div>
        @endif
        <!--- /.poet-list-buttons --->

        <!--- .trending-poets-slider --->
        <div class="trending-poets-slider swiper">
          <div class="swiper-wrapper align-items-center">
            @foreach ($famous_poet as $k => $p)
            <!--- .swiper-slider #slide_item --->
            <div class="swiper-slide" id="slider_item">
              <a href="{{ URL::localized(route('poets.slug', ['name' => $p->poet_slug])) }}">
                <img src="{{ file_exists($p->poet_pic) ? asset($p->poet_pic) : asset('assets/img/placeholder290x293.jpg') }}" class="img-fluid center-cropped" alt="{{ $p->details->poet_laqab }}">
              </a>
              <p id="poets-slider">{{ $p->details->poet_laqab }}</p>
            </div>
            <!--- /.swiper-slider #slide_item --->
            @endforeach
          </div>
          <button class="carousel-btn carousel-3-btn-prev" style="padding-bottom: 80px;">&#8249;</button>
          <button class="carousel-btn carousel-3-btn-next"style="padding-bottom: 80px;">&#8250;</button>
        </div>
        <!--- /.trending-poets-slider --->

        <!--- .slider-buttons --->
        <div id="Poetry-List-btn"> 
          <a class="TodaysPoetrybtn" style="float: left; margin-left: 0px;" href="{{ URL::localized(route('poets.all')) }}">{{ trans('buttons.seemore') }}</a>
        </div>
        <div class="swiper-pagination"></div>
        <!--- /.slider-buttons --->


      </div>
      <!-- /#gallery.section-bg -->
    </div>
    <!--/.container-->
  </section>
  <!-- ========== End Famous Poets ========== -->
@endif {{-- @endif famous_poet --}}



@if (isset($ghazal_of_day))
    <!-- ========== Start Ghazal Of The Day ========== -->
  <section id="ghazal-of-the-day">
    <!--- .container --->
    <div class="container" data-os="fade-up">
     <!--section-header-->
     <div class="section-header">
       <p>{{ trans('labels.ghazal_of_day') }}</p>
     </div>
     <!--/.section-header-->

     <!---#row.content_of_ghazal-->
     <div class="row" id="content_of_ghazal">

       <!--PoetPicture-->
       <div class="col-lg-2 col-md-4 col-sm-4 col-xs-4">
         <div class="single-poet">
           <div class="poet-img">
             <a href="{{ URL::localized(route('poets.slug', ['name' => $ghazal_of_day_poet->poet_slug])) }}"><img src="{{ asset($ghazal_of_day_poet->poet_pic) }}" class="img-fluid" alt=""></a>
             <div class="poet-content text-center">
               <h4><?php echo $ghazal_of_day_poet->details->poet_laqab; ?></h4>
             </div>
           </div>
         </div>
       </div>

       <!--./PoetPicture-->

       <!---GhazalCard-->
       <div class="col-lg-10 col-md-8 col-sm-8 col-xs-8">
         
         <div class="today-ghazal-card p-2">
             <h2 class="t"><?php echo $ghazal_of_day->info->title; ?></h2>
             <div class="poetry poetry-faded p-2">
                @foreach ($ghazal_of_day->all_couplets as $k => $c)
                <p>{!! nl2br($c->couplet_text) !!}</p>
               @endforeach
             </div>

             <div class="left-button-overly">
               <a class="btn-baakh me-20" href="{{ URL::localized(route('poetry.with-slug', ['category' => $ghazal_of_day->category->slug , 'slug' => $ghazal_of_day->poetry_slug])) }}">{{ trans('buttons.readmore') }}</a>
             </div>
             <div class="poetry-shade-overly"></div>
         </div>
         
       </div>
       <!---/.GhazalCard-->

       <div><!---#row.content_of_ghazal-->

    </div>
    <!--- /.container --->
 </section>
 <!-- ========== End Ghazal Of The Day ========== -->
@endif {{-- @endif ghazal_of_the_day --}}


@if (isset($bundles))
    <!-- ========== Start Poetry Bundles ========== -->
  <section id="about" class="about">
    <!--.container-->
    <div class="container" data-os="fade-up">
      <!-- #poetry-bundles.section-bg -->
      <div id="poetry-bundles" class="poetry-bundles section-bg">
        <!-- .section-header -->
        <div class="section-header">
          <p>{{ trans('labels.poetry_bundles') }}</p>
        </div>
        <!-- .section-header -->


        <!--- .poetry-bundles-slider --->
        <div class="poetry-bundles-slider swiper">
          <div class="swiper-wrapper align-items-center">
            @foreach ($bundles as $k => $p)
            <!--- .swiper-slider #slide_item --->
            <div class="swiper-slide" id="slider_item">
            <a href="{{ URL::localized(route('poetry.bundle.slug', $p->slug)) }}">
        @if (file_exists(public_path($p->bundle_thumbnail))) <!-- Check if the file exists -->
          <img src="{{ asset($p->bundle_thumbnail) }}" class="img-fluid" alt="">
        @else
          <img src="{{ asset('assets/img/placeholder290x293.jpg') }}" class="img-fluid" alt=""> <!-- Display a placeholder image if the file doesn't exist -->
        @endif
      </a>

              <p id="poets-slider">{{ $p->title }}</p>
            </div>
            <!--- /.swiper-slider #slide_item --->
            
            @endforeach
          </div>
          <button class="carousel-btn carousel-3-btn-prev bundle-slider-btn-prev" style="padding-bottom: 80px;">&#8249;</button>
          <button class="carousel-btn carousel-3-btn-next bundle-slider-btn-next"style="padding-bottom: 80px;">&#8250;</button>
        </div>
        <!--- /.trending-poets-slider --->

        <!--- .slider-buttons --->
        <div id="Poetry-List-btn"> 
          <a class="TodaysPoetrybtn" style="float: left; margin-left: 0px;" href="#">{{ trans('buttons.seemore') }}</a>
        </div>
        <div class="swiper-pagination"></div>
        <!--- /.slider-buttons --->


      </div>
      <!-- /#poetry-bundles.section-bg -->
    </div>
    <!--/.container-->
  </section>
  <!-- ========== End Poetry Bundles ========== -->
@endif {{-- @endif bundles --}}


  <!-- ========== Start Tags Section ========== -->
  <section class="tags">
    <div class="container mt-5">
      <!-- .section-header -->
      <div class="section-header">
        <p>{{ trans_choice('labels.topic', 0, ['count' => 0]) }}</p>
      </div>
      <!-- .section-header -->
 
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
      <div class="float-left mt-3" style="float: left; margin-left: 0px;">
        <a href="{{ URL::localized(route('web.tags')) }}" class="btn btn-baakh">
          <span class="text">{{ trans('buttons.see_all_topics') }}</span>
          <i class="bi bi-chevron-{{ trans('buttons.i_left') }}"></i>
        </a>
      </div>
    </div>
  </section>
  <!-- ========== End Tags Section ========== -->

@if (isset($quiz_couplet))
  <!-- ======= /. Quiz ======= -->
  <section id="quiz-box" class="quiz-box section-bg cursor-pointer">
    <div class="container aos-init aos-animate" data-aos="fade-up">
      <!---SectionHeader-->
      <div class="section-header">
        <h2 class="section-title">{{ trans('labels.quiz_heading') }}</h2>
        <p class="pt-2" style="font-size: medium">{{ trans('labels.quiz_sub_heading') }}</p>
      </div>
      <!---/SectionHeader-->

      <div class="row" id="quiz-body">
        <!--Poetry colLG4-->
        <div class="col-lg-4 poetry"  data-aos="fade-up" data-aos-delay="100">
          <!--.quiz-question-body-->
          <div class="quiz-question-body">
            <h4>{{ trans('labels.quiz_question') }}</h4>
            <div class="couplet" style="font-size: larger;">
              <p>{!! nl2br($quiz_couplet->couplet_text) !!}</p>
            </div>
            
            <!--ShareOnSocia-->
            <div class="text-center">
              <a href="#" class="more-btn">{{ trans('buttons.share_social_media') }} <i class="bx bx-chevron-right"></i></a>
            </div>
            <!--ShareOnSocia-->

          </div>
          <!--/.quiz-question-body-->
        </div>
        <!--./Poetry colLG4-->

        <!--#answer-box.answes-->
        <div id="answer-box" class="col-lg-8 answer-box justify-content-between">
          <!---#answer_items.row-->
          <div id="answer_items" class="row">
            @foreach ($quiz_poets as $k => $p)
            <div class="col"  data-aos="fade-up" data-aos-delay="200">
              <div class="icon-box answer-item poet-box d-flex flex-column justify-content-center align-items-center" data-main-id="{{ $quiz_couplet->poetry_id }}" data-poet-id="{{ $p->id }}" data-csrf="{{ csrf_token() }}" data-couplet-id="{{ $quiz_couplet->id }}">
                <i><img src="{{ asset($p->poet_pic) }}"></i>
                <h4>{{ $p->details->poet_laqab }}</h4>
              </div>
            </div><!-- End Icon Box -->
            
            @endforeach {{-- $quiz_poets --}}
            

          </div>
          <!---./#answer_items.row-->
        </div>
        <!--/#answer-box.answes-->

          <!--- #answer_items_overly --->
          <div id="answer-box-overly" class="p-5 col-lg-8 bg-white d-none">
            <div class="d-flex align-items-center justify-content-center">
            <h3>مھرباني ڪري انتظار ڪريو...</h3>
            
            </div>
            <div class="d-flex justify-content-center">
              <div class="spinner-border" role="status">
                <span class="sr-only"></span>
              </div>
            </div>
          </div>
          <!--- /#answer_items_overly --->

       

      </div>
      <div class="alert mt-2" id="answer-box-alert" role="alert"></div>
    </div> <!---/Container-->
  </section>
  <!-- ======= /. Quiz ======= -->
@endif

@endsection



{{-- JavaScript Section --}}
@section('js')
<script>
  $(function () {
    var base_url = '{!! url('/') !!}';
     
    $(document).on('click', '.poet-box', function (e) {
      e.preventDefault();
      /// Ajax Request for Quiz Answer Check
      var sel_poet = $(this).data('poet-id')
      var sel_couplet = $(this).data('couplet-id')
      var main_id = $(this).data('main-id')
      var curr_ans = 0;
      // remove class to block clicking
      $('#answer_items').find('.poet-box').removeClass('poet-box')

      $.ajax({
        url:base_url='/poetry/quiz-check',
        type:'post',
        data:{poet:sel_poet, couplet:sel_couplet, main_id:main_id},
        headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
          },
        beforeSend: function (){
          /// do domthing
          console.log("Requiest sent....");
          $('#answer-box').hide();
          $('#answer-box-overly').show();
        },
        success: function (response){
          
          if(response.type === 'success'){
            $('#answer-box-alert').addClass('alert-success');
            $('#answer-box-alert').html(response.message);
          }else{
            $('#answer-box-alert').addClass('alert-danger');
            $('#answer-box-alert').html(response.message);
          }
          curr_ans = response.correct_poet;
          console.log(response);
              
          $('.answer-item').css({'transform':'scale(1,1)', 'background':'#625a5e', 'color':'#fff', 'cursor':'not-allowed'});
          $('.answer-item[data-poet-id="'+curr_ans+'"]').css({'transform':'scale(1,1)', 'background':'#0f6a1a', 'color':'#fff',  'cursor':'not-allowed'});
          $('.answer-item').addClass('disabled');
          $('.answer-item').off('click')

          $('#answer-box').show();
          $('#answer-box-overly').hide();
          
        },
          error: function (xhr, ajaxOptions, thrownError){
          console.error('error called on ajax request of Quiz Answer Check')
          console.error(xhr.status)
          console.error(thrownError)
        }
      });
    })

  })
</script>
@endsection