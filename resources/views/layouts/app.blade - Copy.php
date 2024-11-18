<!DOCTYPE html>
<html lang="sd" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dummy Title</title>

    <!-- Primary Meta Tags -->
    <meta name="title" content="Dummy Title">
    <meta name="description" content="Baakh = A Treasure of Sindhi Poetry Team">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Dummy Title">
    <meta property="og:description" content="Baakh = A Treasure of Sindhi Poetry Team">
    <meta property="og:image" content="">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="">
    <meta property="twitter:title" content="Baakh = A Treasure of Sindhi Poetry">
    <meta property="twitter:description" content="Baakh = A Treasure of Sindhi Poetry Team">
    <meta property="twitter:image" content="">

    <!--====== Favicon Icon ======-->
    <link
      rel="shortcut icon"
      href="{{ asset('assets//images/favicon.svg') }}"
      type="image/svg"
    />

    <!-- ===== All CSS files ===== -->
    <link rel="stylesheet" href="{{ asset('assets//vendor/bootstrap/css/bootstrap.rtl.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets//vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"> -->
    <link rel="stylesheet" href="{{ asset('assets//vendor/aos/aos.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets//css/lineicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets//vendor/swiper/swiper-bundle.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets//css/main.css') }}" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    @yield('style-css')
  </head>
  <body>


    <!-- ========== Start NavBar ========== -->
    

  <!-- ======= Header ======= -->
  
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">

      <a href="{{ url('/') }}" class="logo d-flex align-items-center me-auto me-lg-0">
      <img src="{{ asset('assets//img/Baakh.svg') }}">
        <h1>بيٽا<span>.</span></h1>
      </a>

      <nav id="navbar" class="navbar">
        <ul>
            <li><a href="{{ url('couplets') }}>">شعر</a></li>
            <li><a href="{{ url('poets') }}">شاعر</a></li>
            <li><a href="{{ url('designed-poetry') }}">ڊزائن ٿيل شاعري</a></li>
            <li><a href="{{ url('about') }}">اسان بابت</a></li>
            <li><a href="{{ url('contact') }}">رابطو</a></li>

          <li class="dropdown" style="left:0;"><a href="#"><span>ٻوليون</span> <i class="bi bi-chevron-down dropdown-indicator"></i></a>
            <ul>
              <li><a href="#">سنڌي</a></li>
              <li><a href="#">انگريزي</a></li>
            </ul>
          </li>

        </ul>
        
      </nav><!-- .navbar -->
       

      <a class="btn-book-a-table" href="#book-a-table">ڳولها ڪريو</a>
      <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
      <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>

    </div>
  </header><!-- End Header -->
    <!-- ========== End NavBar ========== -->

   <main id="main">
    @yield('body-section')
   </main>

    
  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">

    <div class="container">
      <div class="row gy-3">
        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-geo-alt icon"></i>
          <div>
            <h4>پتو</h4>
            <p>
              آفيس 428، ڇوٿون فلور، مشرق سينٽر <br>
              ايڪسپوسينٽر، ڪراچي، سنڌ<br>
            </p>
          </div>

        </div>

        <div class="col-lg-3 col-md-6 footer-links d-flex">
          <i class="bi bi-telephone icon"></i>
          <div>
            <h4>رابطي لاءِ</h4>
            <p>
              <strong>Phone:</strong> +1 5589 55488 55<br>
              <strong>Email:</strong> connect@baakh.com<br>
            </p>
          </div>
        </div>

      

        <div class="col-lg-3 col-md-6 footer-links">
          <h4>فالو ڪريو</h4>
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
        &copy; هن پليٽفارم جا سڀ حق ۽ واسطا <strong><span>باک</span></strong>. فائونڊيشن وٽ محفوظ آهن
      </div>

    </div>

  </footer><!-- End Footer -->
  <!-- End Footer -->

  <a href="#" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets//vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets//vendor/aos/aos.js') }}"></script>
  <!-- <script src="{{ asset('assets//vendor/glightbox/js/glightbox.min.js') }}"></script> -->
  <!-- <script src="{{ asset('assets//vendor/purecounter/purecounter_vanilla.js') }}"></script> -->
  <script src="{{ asset('assets//vendor/swiper/swiper-bundle.min.js') }}"></script>
  <!-- <script src="{{ asset('assets//vendor/php-email-form/validate.js') }}"></script> -->

  <!-- Template Main JS File -->
  <script src="{{ asset('assets//js/main.js') }}"></script>

  @yield('js-scripts')
 
</body>

</html>