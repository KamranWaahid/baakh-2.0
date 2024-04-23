
document.addEventListener('DOMContentLoaded', () => {
  "use strict";
  
  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

  /**
   * Sticky header on scroll
   */
  const selectHeader = document.querySelector('#header');
  if (selectHeader) {
    document.addEventListener('scroll', () => {
      window.scrollY > 100 ? selectHeader.classList.add('sticked') : selectHeader.classList.remove('sticked');
    });
  }

  /**
   * Navbar links active state on scroll
   */
  let navbarlinks = document.querySelectorAll('#navbar a');

  function navbarlinksActive() {
    navbarlinks.forEach(navbarlink => {

      if (!navbarlink.hash) return;

      let section = document.querySelector(navbarlink.hash);
      if (!section) return;

      let position = window.scrollY + 200;

      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        navbarlink.classList.add('active');
      } else {
        navbarlink.classList.remove('active');
      }
    })
  }
  window.addEventListener('load', navbarlinksActive);
  document.addEventListener('scroll', navbarlinksActive);

  /**
   * Mobile nav toggle
   */
  const mobileNavShow = document.querySelector('.mobile-nav-show');
  const mobileNavHide = document.querySelector('.mobile-nav-hide');

  document.querySelectorAll('.mobile-nav-toggle').forEach(el => {
    el.addEventListener('click', function(event) {
      event.preventDefault();
      mobileNavToogle();
    })
  });

  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    mobileNavShow.classList.toggle('d-none');
    mobileNavHide.classList.toggle('d-none');
  }

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navbar a').forEach(navbarlink => {

    if (!navbarlink.hash) return;

    let section = document.querySelector(navbarlink.hash);
    if (!section) return;

    navbarlink.addEventListener('click', () => {
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
    });

  });

  /**
   * Toggle mobile nav dropdowns
   */
  const navDropdowns = document.querySelectorAll('.navbar .dropdown > a');

  navDropdowns.forEach(el => {
    el.addEventListener('click', function(event) {
      if (document.querySelector('.mobile-nav-active')) {
        event.preventDefault();
        this.classList.toggle('active');
        this.nextElementSibling.classList.toggle('dropdown-active');

        let dropDownIndicator = this.querySelector('.dropdown-indicator');
        dropDownIndicator.classList.toggle('bi-chevron-up');
        dropDownIndicator.classList.toggle('bi-chevron-down');
      }
    })
  });

  /**
   * Scroll top button
   */
  const scrollTop = document.querySelector('.scroll-top');
  if (scrollTop) {
    const togglescrollTop = function() {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
    window.addEventListener('load', togglescrollTop);
    document.addEventListener('scroll', togglescrollTop);
    scrollTop.addEventListener('click', window.scrollTo({
      top: 0,
      behavior: 'smooth'
    }));
  }
 

  /**
   * Init swiper slider with 1 slide at once in desktop view
   */
  new Swiper('.slides-1', {
    
    speed: 1000,
    loop: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true,
      resumeOnMouseLeave: true,
    },
    simulateTouch: false,
    slidesPerView: 'auto',
    pagination: {
      el: '.swiper-pagination',
      type: 'bullets',
      clickable: false
    },
    navigation: {
      nextEl: '.carousel-2-btn-prev',
      prevEl: '.carousel-2-btn-next',
    }
  });
  

  /**
   * Poets Slider
   */
  new Swiper('.trending-poets-slider', {
    speed: 400,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 1000,
      disableOnInteraction: true,
      pauseOnMouseEnter: true,
      resumeOnMouseLeave: true,
    },
    slidesPerView: 'auto',
    pagination: {
      el: '.swiper-pagination',
      type: 'bullets',
      clickable: true
    },
    breakpoints: {
      320: {
        slidesPerView: 2,
        spaceBetween: 20
      },
      640: {
        slidesPerView: 3,
        spaceBetween: 20
      },
      992: {
        slidesPerView: 5,
        spaceBetween: 20
      }
    },
    navigation: {
      nextEl: '.carousel-3-btn-prev',
      prevEl: '.carousel-3-btn-next',
    }
  });

  /**
   * poetry-bundles
   */
  new Swiper('.poetry-bundles-slider', {
    speed: 3000,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 1000,
      disableOnInteraction: true,
      pauseOnMouseEnter: true,
      resumeOnMouseLeave: true,
    },
    slidesPerView: 'auto',
    pagination: {
      el: '.swiper-pagination',
      type: 'bullets',
      clickable: true
    },
    breakpoints: {
      320: {
        slidesPerView: 2,
        spaceBetween: 20
      },
      640: {
        slidesPerView: 3,
        spaceBetween: 20
      },
      992: {
        slidesPerView: 5,
        spaceBetween: 20
      }
    },
    navigation: {
      nextEl: '.bundle-slider-btn-prev',
      prevEl: '.bundle-slider-btn-next',
    }
  });


  /**
   * couplet-bundles
   */
  new Swiper('.couplet-bundles-slider', {
    speed: 3000,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 10000,
      disableOnInteraction: true,
      pauseOnMouseEnter: true,
      resumeOnMouseLeave: true,
    },
    slidesPerView: 'auto',
    pagination: {
      el: '.swiper-pagination',
      type: 'bullets',
      clickable: true
    },
    breakpoints: {
      
      768: {
        slidesPerView: 3,
        spaceBetween: 20
      },
      640: {
        slidesPerView: 2,
        spaceBetween: 20
      },
      992: {
        slidesPerView: 5,
        spaceBetween: 20
      }
    },
    navigation: {
      nextEl: '.bundle-slider-btn-prev',
      prevEl: '.bundle-slider-btn-next',
    }
  });

   /**
   * Book Slider
   */
   new Swiper('.book-slider', {
    speed: 400,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true,
      resumeOnMouseLeave: true,
    },
    slidesPerView: 'auto',
    pagination: {
      el: '.swiper-pagination',
      type: 'bullets',
      clickable: true
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
        spaceBetween: 20
      },
      640: {
        slidesPerView: 4,
        spaceBetween: 20
      },
      992: {
        slidesPerView: 6,
        spaceBetween: 20
      }
    },
    navigation: {
      nextEl: '.book-slider-btn-prev',
      prevEl: '.book-slider-btn-next',
    }
  });

  /**
   * Animation on scroll function and init
   */
  function aos_init() {
    
    AOS.init({
      duration: 1000,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', () => {
    aos_init();
  });

/**
 * Left Side Language Selector
 * 
*/
  var dropdown = $('.dropdown');
  var dropdownUl = dropdown.find('ul');
  dropdown.on('click', function (e) {
      e.stopPropagation();

      // Toggle class on the .dropdown
      dropdown.toggleClass('active');
      dropdownUl.toggleClass('show');

      $('.dropdown').not(dropdown).removeClass('active');
      $('.dropdown ul').not(dropdownUl).removeClass('show');
  });

  // Close dropdown when clicking outside of it
  $(document).on('click', function () {
      dropdown.removeClass('active');
      dropdownUl.removeClass('show');
  });

  // Prevent the click event inside the dropdown from closing it
  dropdown.on('click', function (e) {
      e.stopPropagation();
  });

  $('[data-lang]').click(function (e) {
    e.preventDefault();
    // Get the selected language code
    var selectedLang = $(this).data('lang');
  

    // Get the current URL and remove any existing lang parameter
    var currentUrl = window.location.href;
    currentUrl = currentUrl.replace(/(\?|\&)lang=[a-zA-Z_-]+/, '');

    // Add the selected language code as a query parameter
    var newUrl = ''
    if(selectedLang === 'sd')
    {
      newUrl = currentUrl;
    }else{
      newUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'lang=' + selectedLang;
    }
  

    // Redirect to the new URL
    window.location.href = newUrl;
  });

  $(document).on('click','.btn-baakh-search', function () {
    $(this).toggleClass('active')
    $('.search-container').slideToggle(200, 'swing')
    $('.search-container').toggleClass('show')
    
    /* $(".search-container").slideToggle("500", "easeInOutCirc"); */
    $(".search-input").focus();
  })

 


 
});


// Header Slider Images
const carousel = document.querySelector('.carousel');
if (carousel !=null) {
  const images = carousel.querySelectorAll('.main-slider-img');

  const prevBtn = document.querySelector('.carousel-btn-prev');
  const nextBtn = document.querySelector('.carousel-btn-next');  
  
  
  let currentIndex = 0;
  let autoSlideInterval;


  function showImage(index) {
    if (index < 0) {
      index = images.length - 1;
    } else if (index >= images.length) {
      index = 0;
    }
  
    images.forEach((img) => {
      img.classList.remove('active');
    });
  
    images[index].classList.add('active');
    currentIndex = index;
  }
  
  function fadeOutCurrentImage() {
    images[currentIndex].style.opacity = '0';
  }
  
  function fadeInNextImage() {
    images[currentIndex].style.opacity = '1';
  }
  
  function prevImage() {
    fadeOutCurrentImage();
    showImage(currentIndex - 1);
    fadeInNextImage();
    resetAutoSlide();
  }
  
  function nextImage() {
    fadeOutCurrentImage();
    showImage(currentIndex + 1);
    fadeInNextImage();
    resetAutoSlide();
  }
  
  function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
      fadeOutCurrentImage();
      showImage(currentIndex + 1);
      fadeInNextImage();
    }, 10000);
  }
  
  function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    startAutoSlide();
  }
  
  prevBtn.addEventListener('click', prevImage);
  nextBtn.addEventListener('click', nextImage);
  
  
  // Show the first image initially
  showImage(0);
  images[0].style.opacity = '1';
  
  // Start auto slide
  startAutoSlide();
  
}


/**
 * Baakh Poetry Page Codes
*/

function baakhJustified(className)
{
  var ghazalClasses = $('.'+className);
  var firstLine = $('.couplets-list '+className+':first').find('.line:first');
    var spanCount = firstLine.find('span').length
 

    // Iterate over each .ghazal class
    var maxWidth = 0;
    ghazalClasses.each(function() {
        var currentGhazal = $(this);

        // Find all .line elements within the current .ghazal
        var lines = currentGhazal.find('.line');

        // Iterate over each .line element
        lines.each(function() {
            var lineWidth = $(this).width();
            
            // Check if the current line's width is greater than the current max width
            if (lineWidth > maxWidth) {
                maxWidth = lineWidth;
            }
            lines.attr('data-width', lineWidth)
        });

        // Add the maximum width to the .ghazal class
        //currentGhazal.css('max-width', maxWidth + 'px');
    });

    if (window.innerWidth < 768) {
      
        // Do not add spanCount + 2 for mobile devices  
        if(spanCount < 4)
        {
          var addSpace = maxWidth + 80;
          
        }else{
          /* var addSpace = window.innerWidth + spanCount; */
          var addSpace = maxWidth + (window.innerWidth / (spanCount+3))
        } 
        
    } else {
        // Add spanCount + 2 for non-mobile devices
        if(spanCount < 4)
        {
          var addSpace = maxWidth + 80;
        }
        else{
          if(spanCount <= 7)
          {
            var addSpace = maxWidth + (window.innerWidth / (spanCount+50))
          }else{
            var addSpace = maxWidth + (window.innerWidth / (spanCount+3))
          }
        }
        
    } 

    
    
    $('.single-couplet').css('width', addSpace)
    var ghazalParagraphs = $('p.'+className);

    // Apply the CSS properties to each <p> element
    ghazalParagraphs.css({
        'text-align': 'justify',
        'text-align-last': 'justify',
        'word-wrap': 'break-word',
        'hyphens': 'auto'
    });

    $('.couplets-list').css({
      'display': 'flex',
      'flex-direction': 'column',
      'justify-content': 'center',
      'align-items': 'center',
      'padding-right': '0px'
    })
     
}


// Function to initialize and play the YouTube video
function playVideo(videoUrl) {
  $('#youtube-player').attr('src', 'https://www.youtube.com/embed/'+videoUrl)
  if (typeof YT !== 'undefined' && typeof YT.Player !== 'undefined') {
      // If the YouTube API is loaded and available

      if (!player) {
          player = new YT.Player('youtube-player', {
              videoId: videoUrl,
              events: {
                  'onReady': function (event) {
                      event.target.playVideo(); // Play the video when it's ready
                  }
              }
          });
      } else {
          player.loadVideoById(videoUrl); // Load and play a new video by its ID
      }
  }
}
  
function getNextPageNumber(lastDiv) {
  var nextPageUrl = lastDiv.getAttribute('data-next-page_url')
    // Check if nextPageUrl is defined before trying to use match
    if (nextPageUrl) {
        const match = nextPageUrl.match(/page=(\d+)/);
        return match ? parseInt(match[1], 10) : null;
    } else {
        console.error('nextPageUrl is undefined');
        return null; // or handle it in a way that makes sense for your application
    }
}