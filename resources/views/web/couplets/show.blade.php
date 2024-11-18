@extends('layouts.web')
 
@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/couplets_quotes.css') }}" />
@endpush

@section('body')
    <!-- ====== Top Spacer Start ====== -->
    <div class="top-spacer"></div>
    <!-- ====== Top Spacer End ====== -->
 
    <!-- ========== Start Section Poetry ========== -->
    <section id="poetry-couplets" class="section-bg py-md-2 pt-md-5 poetry-detail-page">
      <div class="App">
       
        <div class="quote__wrp" style="opacity: 1;">
          <div class="quote__content">
            <div class="quote__sentence text-primary">
             
              <p>{!! nl2br($couplet->couplet_text) !!}</p>
            </div>
            <div class="quote__footer" style="background: linear-gradient(to right bottom, rgb(251, 146, 60), rgb(219, 39, 119));">
              <a href="{{ URL::localized(route('poets.slug', ['name' => $couplet->poet->poet_slug])) }}" class="text-white" target="_blank" rel="noreferrer">- {{ $couplet->poet->poet_laqab }} </a>
              <a href="{{ URL::localized(route('poets.slug', ['name' => $couplet->poet->poet_slug])) }}" target="_blank" rel="noreferrer">
                <img src="{{ asset($couplet->poet->poet_pic) }}" class="img-fluid" style="width:50px; border-radius:50px;" alt="">
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ========== End Section Poetry ========== -->

 
@endsection


@section('js')


<script>
  var player; // Define a variable to hold the YouTube player instance

  $(function () {
   
    // youtube play clicked
    $('.btn-youtube').on('click', function () {
      $(this).toggleClass('active')
      $('.video-content').toggle()
      
      $('.audio-content').hide();
      $('.btn-audio').removeClass('active');

      // play pause video
      var activeItem = $('.video-links .active');
      if (activeItem.length > 0) {
          var item = activeItem.data('video-url');
      } else {
          // If there's no element with the .active class, select the first <li> and add the .active class
          var firstItem = $('li.video-links:first');
          firstItem.addClass('active');
          var item = firstItem.data('video-url');
      }

      playVideo(item)
    })

    // on item click
    $('.video-links').on('click', function () {
      var url = $(this).data('video-url')
      $('.video-links').not(this).removeClass('active');
      $(this).addClass('active');
      playVideo(url);
    })

    // audio play clicked
    $('.btn-audio').on('click', function () {
      $(this).toggleClass('active')
      $('.audio-content').toggle()
      
      $('.video-content').hide();
      $('.btn-youtube').removeClass('active');

      // play pause audio
      var activeItem = $('.audio-links .active');
      if (activeItem.length > 0) {
          var item = activeItem.data('audio-url');
      } else {
          // If there's no element with the .active class, select the first <li> and add the .active class
          var firstItem = $('li.audio-links:first');
          firstItem.addClass('active');
          var item = firstItem.data('audio-url');
      }
    })

    // audio links player
    $('.audio-links').on('click', function(){
      $('.audio-player').toggle();
      var url = $(this).data('video-url')
      $('.audio-links').not(this).removeClass('active');
      $(this).addClass('active');
    }) 

    // check if there is Ghazal format
    var ghazalClasses = $('.justified');
    if(ghazalClasses.length > 0)
    {
      baakhJustified('justified') 
    }



    

  })

  


</script>




@endsection