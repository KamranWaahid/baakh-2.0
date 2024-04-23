"use strict";
$(document).ready(function () {
    // Button Social Share
  $(document).on('click', '.btn-share', function (event) {
    event.preventDefault();
    var divId = $(this).data('id');
    
    if($('.'+divId).length > 0)
    {
      $('#'+divId).toggle();
      $('.'+divId).toggle();
    }else{
      $('#'+divId).toggle();
    }
    
    $(this).toggleClass('active bg-primary')
    $(this).find('i').toggleClass('bi-share bi-x-lg')
    // hide label
    var label = $(this).find('span');
    if(label.length > 0)
    {
      label.toggle();
      $(this).find('i').toggleClass('me-2')
    }
    
  });


  // Facebook Share
  $(document).on('click', '.btn-share-on', function (e) {
    e.preventDefault();
    var url = $(this).data('share_url');
    var text = $(this).data('share_text');
    var platform = $(this).data('platform'); // fb == facebook, tw == twitter, wa == Whatsapp

    if (platform === 'fb') {
        var shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&quote=' + encodeURIComponent(text);
        window.open(shareUrl, 'Share on Facebook', 'width=600,height=400');
    } else if (platform === 'tw') {
        var shareUrl = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(text);
        window.open(shareUrl, 'Share on Twitter', 'width=600,height=400');
    } else if (platform === 'wa') {
        var shareUrl = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(text + ' ' + url);
        window.open(shareUrl);
    }

     
  });

  $(document).on('click', '.btn-like', function (e) {
    e.preventDefault();
    var path = $(this).data('uri'); //item.like-dislike
    var type = $(this).data('type');
    var typeId = $(this).data('type_id');
    var icon = $(this).find('i');
    /// Ajax Request for Like Dislike
    $.ajax({
      url:path+'/comments/like-dislike',
      type:'post',
      data:{likableType:type, likableId:typeId},
      headers: {
        'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
      },
      beforeSend: function (){
        /// do domthing
        console.log('---------- sent like request ----------');
      },
      success: function (response){
        console.log('success called on ajax request of Like Dislike')
        console.log(response);
        $(icon).toggleClass('bi-heart bi-heart-fill text-baakh')
        toastr.success('پسنديدہ ۾ شامل ٿي چُڪو')
        
      },
      error: function (xhr, ajaxOptions, thrownError){
        
        if(xhr.status == 401)
        {
          toastr.error('شعر کي پسند ڪرڻ لاءِ لاگ ان ٿيڻ ضروري آھي', {fadeAway: 3000})
        }
        console.error('error called on ajax request of Like Dislike')
        console.error(xhr.status)
        console.error(thrownError)
      }
    });
  })

})