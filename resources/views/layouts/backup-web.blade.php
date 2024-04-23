<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $langDir }}">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    
    {!! SEOMeta::generate() !!}
    {!! OpenGraph::generate() !!}
    {!! Twitter::generate() !!}
    {!! JsonLd::generate() !!}

    @if (isset($siteLanguages))
      @foreach ($siteLanguages as $item)
      <link rel="alternate" hreflang="{{ $item->lang_code }}" href="{{ url()->current() }}?lang={{ $item->lang_code }}" />
      @endforeach
    @endif
      
    

    <link href='https://fonts.googleapis.com/css?family=Bitter' rel='stylesheet'>
    <script src="{{ asset('assets/js/dark-mode.js') }}"></script>
    
    
    <!--====== Favicon Icon ======-->
    <link
      rel="shortcut icon"
      href="{{ asset('assets/images/favicon.svg') }}"
      type="image/svg"
    />
 
    <!-- ===== All CSS files ===== -->
    @if ($langDir == 'rtl')
      <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.rtl.css') }}" />
      <link rel="stylesheet" href="{{ asset('assets/css/main.rtl.css') }}" />
    @else
      <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.css') }}" />
      <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}" />
    @endif
    
    
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <!-- <link href="https:/cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"> -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/aos/aos.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/lineicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/swiper/swiper-bundle.min.css') }}" />
    <script src="https:/code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    @yield('css')
  </head>
  <body>


    <!-- ========== Start NavBar ========== -->
    

  <!-- ======= Header ======= -->
  
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">

      <a href="{{ URL::localized(url('/')) }}" class="logo d-flex align-items-center me-auto me-lg-0">
      <img src="{{ asset('assets/img/Baakh.svg') }}">
        <h1>بيٽا<span>.</span></h1>
      </a>

      <nav id="navbar" class="navbar">
        <ul>
            <li><a href="{{ URL::localized(url('couplets')) }}">{{ trans('menus.couplets') }}</a></li>
            <li><a href="{{ URL::localized(url('poets')) }}">{{ trans('menus.poets') }}</a></li>
            <li><a href="{{ URL::localized(url('genre')) }}">{{ trans('menus.genre') }}</a></li>
            <li><a href="{{ URL::localized(url('period')) }}">{{ trans('menus.period') }}</a></li>
            <li><a href="{{ URL::localized(url('prosody')) }}">{{ trans('menus.prosody') }}</a></li>
            <li><a href="{{ URL::localized(url('about')) }}">{{ trans('menus.about_us') }}</a></li>
            <li><a href="{{ URL::localized(url('contact')) }}">{{ trans('menus.contact') }}</a></li>
        </ul>
        
      </nav><!-- .navbar -->
  
      <a class="btn-baakh-search"><i class="bi bi-search"></i></a>
      <a class="btn-baakh-theme" id="btn-baakh-theme">
        <i id="btnDarkThemeIcon" class="bi bi-moon"></i>
      </a>      
      <div class="left-side d-flex justify-content-between">
        <li class="dropdown" style="margin-top:3px;">
          <button type="button" class="btn"><i class="bi bi-globe dropdown-indicator"  style="font-size:1.5rem;"></i></button>
          <ul class="dropdown">
            @foreach ($siteLanguages as $item)
              <li data-lang="{{ $item->lang_code }}">{{ $item->lang_title }}</li>    
            @endforeach
          </ul>
        </li>

        <div class="user-profile">
          @php
              $userAvatar = (Auth::user() && file_exists(auth()->user()->avatar)) ? asset(auth()->user()->avatar) : NULL;
          @endphp

          <a href="{{ (Auth::user()) ? url('user/profile') : url('login') }}">
            @if (isset($userAvatar))
              <div class="user-image" style="margin-top:15px">
                  <img src="{{ $userAvatar }}" class="rounded-circle" style="width:40px;height:auto" alt="">
              </div>
              @else
                <div class="login-icon" ata-toggle="tooltip" title="Login with Google" data-placement="top">
                  <i style="font-size: 1.6rem;" class="bi bi-person-circle" ></i>
                </div>
              @endif
              
          </a>

        </div>
      </div>
       
      <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
      <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>

    </div>

    
  </header><!-- End Header -->
    <!-- ========== End NavBar ========== -->
    
    
    <!-- ========== Start Search Box Block ========== -->
    <div class="search-container">
      <div class="searchbox container">
        <form method="search">
          <button type="submit" class="btn btn-submit m-0"><i class="bi bi-search"></i></button>
          <input type="text" name="query" class="form-control search-input" placeholder="{{ trans('menus.search_placeholder') }}">
        </form>
      </div>
    </div>
    <!-- ========== End Search Box Block ========== -->


   <main id="main">
    @yield('body')
   </main>

    
  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">

    <div class="container">
      <div class="row gy-3">
        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-geo-alt icon"></i>
          <div>
            <h4>{{ trans('menus.address_label') }}</h4>
            <p>{{ trans('menus.address') }}</p>
          </div>

        </div>

        <div class="col-lg-3 col-md-6 footer-links d-flex">
          <i class="bi bi-telephone icon"></i>
          <div>
            <h4>{{ trans('menus.for_contact') }}</h4>
            <p>
              <strong>{{ trans('menus.phone') }}:</strong> +1 5589 55488 55<br>
              <strong>{{ trans('menus.email') }}:</strong> connect@baakh.com<br>
            </p>
          </div>
        </div>

      

        <div class="col-lg-3 col-md-6 footer-links">
          <h4>{{ trans('menus.follow_us') }}</h4>
          <div class="social-links d-flex">
            <a href="www.twitter.com/baakhconnect" class="twitter"><i class="bi bi-twitter"></i></a>
            <a href="www.facebook.com/baakhconnect" class="facebook"><i class="bi bi-facebook"></i></a>
            <a href="www.instagram.com/baakhconnect" class="instagram"><i class="bi bi-instagram"></i></a>
            <a href="www.linkedin.com/baakhconnect" class="linkedin"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

      </div>
    </div>

    <div class="container">
      <div class="copyright">
        &copy; {{ trans('menus.copyright') }}
      </div>

    </div>

  </footer><!-- End Footer -->
  <!-- End Footer -->

  <a href="#" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/aos/aos.js') }}"></script>
  <!-- <script src="{{ asset('assets/vendor/glightbox/js/glightbox.min.js') }}"></script> -->
  <!-- <script src="{{ asset('assets/vendor/purecounter/purecounter_vanilla.js') }}"></script> -->
  <script src="{{ asset('assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
  <!-- <script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script> -->

  

  <!-- Template Main JS File -->
  <script src="{{ asset('assets/js/main.js') }}"></script>
  <script src="{{ asset('assets/js/social-share.js') }}"></script>
  {{-- <script src="https://accounts.google.com/gsi/client" async defer></script> --}}
 
  @yield('js')
 
  {{-- <div id="g_id_onload"
      data-client_id="{{ env('GOOGLE_CLIENT_ID') }}"
      data-context="singin"
      data-close_on_tap_outeside="false"
      data-login_uri="{{ route('login.with-google') }}">
  </div> --}}
</body>
</html>