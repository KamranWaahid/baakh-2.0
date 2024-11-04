@extends('layouts.web')
 


@section('body')
    <!-- ====== Top Spacer Start ====== -->
    <div class="top-spacer"></div>
    <!-- ====== Top Spacer End ====== -->
 
    <!-- ========== Start Section Poetry ========== -->
    <section id="poetry-couplets" class="section-bg py-md-2 pt-md-5 poetry-detail-page">
        <!---= START container =--->
        <div class="container">
          <!---= START row for 8 col poetry & 4 col info =--->
          <div class="row" id="poetry-row">
            <!---= START col-8 for couplets =--->
            <div class="col-lg-8 col-sm-12 col-xs-12">

              <!-- ========== Start Poetry Name ========== -->
              <div class="poetry-title shadow-none mb-2 px-3 py-3 align-items-center d-flex justify-content-between">
                <h2 class="text-primary poetry-heading">
                    {{ $couplet->couplet_title }}
                </h2>
                <div class="author-image ">
                  <a href="{{ URL::localized(route('poets.slug', ['name' => $poet_info->poet_slug])) }}" class="d-md-flex justify-content-aware">
                    <h4 class="pe-2 poet-name d-lg-block d-md-none text-primary">{{ $poet_detail->poet_laqab }}</h4>
                    <img src="{{ file_exists($poet_info->poet_pic) ? asset($poet_info->poet_pic) : asset('assets/img/placeholder290x293.jpg') }}" style="width:50px; height:50px;" class="rounded-circle" alt="author" />
                  </a>
                </div> 

              </div>
              <!-- ========== End Poetry Name ========== -->

              <!---= START card of poetry-media =--->
              @isset($media_videos)
              <div class="poetry-title card p-2 shadow-none m-0 mb-2 poetry-media">
                <div class="buttons d-flex justify-content-between">
                    <div class="right-buttons text-center">
                      @if (count($media_videos) > 0)
                        <button type="button" class="btn btn-youtube btn-default"><i class="bi bi-youtube"></i></button>    
                      @endif
                      @if (count($media_audios) > 0)
                        <button type="button" class="btn btn-audio btn-default"><i class="bi bi-volume-up"></i></button>    
                      @endif
                      
                    </div>
                    <div class="left-buttons">
                      <div class="d-flex justify-content-between">
                        {{-- <button type="button" class="btn btn-like btn-default" data-uri="{{ url('/') }}" data-type="Poetry" data-type_id="{{ $couplet->id }}"><i class="bi bi-heart{{ $liked }}"></i></button> --}}
                          <x-baakh_share_buttons poetryUrl="{{ $poetryUrl }}" shareText="" componentId="buttons_social_poetry"  />
                        <button type="button" class="btn btn-share btn-default" data-id="buttons_social_poetry"><i id="share-icon" class="bi bi-share"></i></button>
                      </div>
                    </div>
                </div>
                @if (count($media_videos) > 0)
                <div class="video-content spacer-dotted mt-2">
                  <div class="row">
                    <div class="singer-names col-sm-6 col-xs-12">
                      <ul class="list-unstyled">
                        @foreach ($media_videos as $item)
                        <li class="video-links py-2" data-video-url="{{ $item->media_url }}">
                          <img src="https://i.ytimg.com/vi/{{ $item->media_url }}/hqdefault.jpg" alt="" class="video-thumbnail">
                          <span class="singer-name">
                            {{ $item->media_title }}
                          </span>
                        </li>
                        @endforeach
                      </ul>
                    </div>
                    <div class="video col-sm-6 col-xs-12" id="video-iframe-container">
                      <iframe id="youtube-player" width="100%" height="215" src="" frameborder="0" allowfullscreen></iframe>
                    </div>
                  </div>
                </div>
                @endif {{-- endif .video-content media > 0 --}}

                @if (count($media_audios) > 0)
                <div class="audio-content spacer-dotted mt-2">
                  <div class="row">
                    <div class="singer-names col-sm-6 col-xs-12">
                      <ul class="list-unstyled">
                        @foreach ($media_audios as $item)
                        <li class="audio-links py-2" data-video-url="{{ $item->media_url }}">
                          <i class="bi bi-volume-up"></i>
                          <span class="singer-name">
                            {{ $item->media_title }}
                          </span>
                        </li>
                        @endforeach
                      </ul>
                    </div>
                    <div class="audio col-sm-6 col-xs-12" id="video-iframe-container">
                      
                    </div>
                  </div>
                </div>
                @endif {{-- endif .video-content media > 0 --}}
                
              </div>
              @endisset
              <!---= END card of poetry-media =--->

              <!---= START card of couplets =--->
              <div class="poetry-title shadow-none text-center baakh-border-bottom">
               <div class="couplets-list">
                @foreach ($poetry->all_couplets as $k => $c)
                  <div class="poetry single-couplet"> 
                      <p class="{{ $poetry->content_style }}">
                          @php
                              $lines = explode("\n", $c->couplet_text);
                          @endphp
                          @foreach ($lines as $line)
                              <span class="line">
                                  @php
                                      $words = explode(' ', $line);
                                  @endphp
                                  @foreach ($words as $w)
                                      <span class="w">{!! $w !!}</span>
                                  @endforeach
                              </span>
                              <br> <!-- Add a <br> tag for a new line between lines -->
                          @endforeach
                      </p>
                  </div>
                @endforeach

              </div>
            </div>
            <!---= END card of couplets =--->
  
               
             
              <!---= START Navigation for NEXT & PREVIOUS poetry =--->
              <div class="row mt-4 poetry-navigator">
                <!---= START col-6 for each =--->
                @if ($previous_poetry !=null)
                
                <div class="@if ($next_poetry !=null) col-6 @else col-12 @endif" id="previous-poetry-navigator">
                  <div class="p-2 poetry-title">
                    <a href="{{ URL::localized(route('poetry.with-slug', ['category' => $previous_poetry->category->slug, 'slug' => $previous_poetry->poetry_slug])) }}">
                      <div class="d-flex justify-content-between align-items-center p-2 poetry-title">
                        <i class="lni lni-chevron-right"></i>
                        <div class="npoetry">
                          <h5 class="text-secondary nav-title">{{ trans('buttons.next_poetry') }}</h5>
                          <p class="p-0 m-0 text-secondary nav-content">{{ $previous_poetry->info->title }}</p>
                          <small class="text-italic nav-poet">{{ $poet_detail->poet_laqab }}</small>
                        </div>
                      </div>
                    </a>
                  </div>
                </div>
                @endif {{-- @if{previous_poetry !=null} --}}

                @if ($next_poetry !=null)
                
                <div class="@if ($previous_poetry !=null) col-6  @else col-12 text-center @endif" id="next-poetry-navigator">
                  <div class="p-2 poetry-title">
                    <a href="{{ URL::localized(route('poetry.with-slug', ['category' => $next_poetry->category->slug, 'slug' => $next_poetry->poetry_slug])) }}">
                      <div class="d-flex justify-content-between align-items-center p-2 poetry-title">
                        
                        <div class="npoetry">
                          <h5 class="text-secondary  nav-title">{{ trans('buttons.previous_poetry') }}</h5>
                          <p class="p-0 m-0 text-secondary nav-content">{{ $next_poetry->info->title }}</p>
                          <small class="text-italic nav-poet">{{ $poet_detail->poet_laqab }}</small>
                        </div>
                        <i class="lni lni-chevron-left"></i>
                      </div>
                    </a>
                  </div>
                </div>
                @endif
                <!---= END col-6 for each =--->
              </div>
              <!---= END Navigation for NEXT & PREVIOUS poetry =--->
  
            </div>
            <!---= END col-8 for couplets =--->
  

            <!---= START col-3 for details & related poetry =--->
            <div class="col-lg-4 col-sm-12 col-xs-12" id="left_div">
              <!---= START card for related poetry =--->
              <div class="shadow-none about-poet row">
                <!---= START picture =--->
                <div class="col-lg-12 col-4">
                  <img src="{{ asset($poet_info->poet_pic) }}" class="img-fluid" alt="author" />
                </div>
                <!---= END picture =--->
                <div class="col-lg-12 col-8">
                  <p class="paragraph pt-3">
                    {!! strip_tags(Str::words($poet_detail->poet_bio, 40)) !!}
                  </p>
                  <button type="button" class="btn btn-xs btn-baakh mt-2 px-2 py-1" data-bs-toggle="modal" data-bs-target="#poetInfoDetailModal"><i class="lni lni-list"></i> {{ trans('buttons.detail') }}</button>
                </div>
              </div>
                <!---= END card for related poetry =--->
              
              
              <!-- ========== Start Mozu (Tags) ========== -->
              @if ($used_tags)
              <div class="poetry-tags mt-2 pb-3" id="poetry_tags">
                <h3 class="text-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Tags">{{ trans('labels.tags') }}</h3>
                <div class="spacer-dotted mt-1"></div>
                <div class="tags-list py-2">
                  @foreach ($used_tags as $key => $tag)
                    <a href="{{ URL::localized(route('poetry.with-tag', $key)) }}" class="btn btn-sm mt-1 m-0 btn-baakh px-4 py-1" style="font-size:1rem;">{{ $tag }}</a>
                  @endforeach
                </div>
              </div>
              @endif
              <!-- ========== End Mozu (Tags) ========== -->

              @if ($poetry->info)
              <!-- ========== Start about poetry section ========== -->
              <div class="about-poetry mt-2">
                <h3 class="text-primary">ھِن {{ $poetry->category->detail->cat_name }} بابت</h3>
                <div class="spacer-dotted mt-1" style="border-bottom:2px solid;border-color:#DDDDDD;"></div>
                <p class="paragraph">
                  {{ strip_tags($poetry->info->info) }}
                </p>
              </div>
              <!-- ========== End about poetry section ========== -->
              @endif {{-- endif $poetry->info --}}              


            
            </div>

            <!---= END col-3 for details & related poetry =--->
          </div>
          <!---= END row for 8 col poetry & 4 col info =--->
        </div>
        <!---= END container =--->
    </section>
    <!-- ========== End Section Poetry ========== -->


    <!-- ========== Start Comments Section ========== -->
    <section class="comments py-2 text-primary" id="comments">
      <div class="comments-container">
        <div class="d-flex justify-content-between">
          <h3 class="title">{{ trans('labels.reviews') }}</h3>
          <span>{{ trans_choice('labels.comments', $total_comments, ['count' => $total_comments]) }}</span>
          @if (!Auth::user())
            <a href="{{ url('/login') }}" class="btn btn-baakh"><i class="bi bi-login"></i>{{ trans('buttons.login_to_comment') }}</a>    
          @endif
          
        </div>
        <div class="update-comment-div"></div>
        @if (Auth::user() && is_null($already_commented))
        <div class="user-inputs mt-3">
          @php
              $avatar = auth()->user()->avatar;
          @endphp
          <div class="d-flex justify-content-between">
            <div class="user-profile d-flex justify-content-aware align-items-center">
              <img src="{{ file_exists(auth()->user()->avatar) ? asset($avatar) : $avatar }}" class="rounded-circle" id="loggedInUserAvatar" width="50px" height="auto" alt="">
              <h5 class="px-2" id="loggedInUserName">{{ app()->getLocale() == 'sd' ? auth()->user()->name_sd : auth()->user()->name }}</h5>
            </div>{{-- /.user-profile --}}
  
            <div class="remaining-letters">
              <p class="message">توھان <span class="counts">200</span> اکرن ۾ پنھنجي راءِ ڏئي سگهو ٿا</p>
            </div>  
          </div>

          <div class="input-area">
            <textarea name="user_comment" id="userCommentTextArea" rows="3" placeholder="{{ trans('labels.write_something_for_comment') }}"></textarea>
          </div>
          <div class="comment-buttons mt-2 d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-baakh btn-submit-comment" data-poetry-id="{{ $poetry->id; }}">{{ trans('buttons.submit_comment') }}</button>
            <div class="text-actions">
              <button type="button" class="btn btn-default" onclick="insertLink()"><bi class="bi bi-link-45deg"></bi></button>
              <button type="button" class="btn btn-default" onclick="applyItalic()"><bi class="bi bi-type-italic"></bi></button>
              <button type="button" class="btn btn-default" onclick="applyBold()"><bi class="bi bi-type-bold"></bi></button>
            </div>
          </div>

        </div>{{-- /.user-input --}}
        @endif

        
         
       
        <div class="user-comments mt-3">
          {!! $user_comments !!}
        </div>
        @if ($total_comments > 0)
        <button type="button" class="btn btn-load-more-comments w-100 btn-inline-block">{{ trans('buttons.more_comments') }}</button>
        <button type="button" class="btn btn-inline-block btn-load-more-spinner w-100 disabled" style="display: none">
          <div class="d-flex justify-content-center">
            <div class="spinner-border" role="status">
              <span class="sr-only"></span>
            </div>
          </div>
        </button>
        @endif
      </div>
    </section>
    <!-- ========== End Comments Section ========== -->

    <!-- ========== Start Audio Player Ranger ========== -->
    <section class="audio-player fix-bottom" id="audio_player_bottom">
      <h5 class="text-center">Name of Audio Singer - Name of Ghazal or Bait</h5>
      <input type="range" class="audio-range" min="1" dir="ltr" max="100" value="30" id="audioRangeID">
      <div class="audio-controller text-center">
        <i class="btn-player-icon bi bi-2x bi-step-backward"></i>
        <i class="btn-player-icon bi bi-2x bi-play-circle"></i>
        <i class="btn-player-icon bi bi-2x bi-step-forward"></i>
      </div>
    </section>
    <!-- ========== End Audio Player Ranger ========== -->

 


<!---= START poetInfoDetailModal =--->
<div class="modal fade" id="poetInfoDetailModal" tabindex="-1" aria-labelledby="poetInfoDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="poetInfoDetailModalLabel">{{ $poet_detail->poet_laqab }}</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table>
            <tr>
                <th class="px-2s">{{ trans('labels.o_name') }}</th>
                <td>{{ $poet_detail->poet_name }}</td>
            </tr>
            @if ($poet_detail->pen_name)
            <tr>
              <th class="px-2s">{{ trans('labels.p_name') }}</th>
              <td>{{ $poet_detail->pen_name }}</td>
            </tr>
            @endif
            <tr>
              <th class="px-2">{{ trans('labels.dob') }}</th>
                <td>{{ sindhi_date('D، d M Y', strtotime($poet_info->date_of_birth)) }}</td>
            </tr>
            <tr>
              <th class="px-2">{{ trans('labels.birth_place') }}:</th>
              <td>{{ $poet_detail->birthPlace->city_name }}</td>
            </tr>

           @if ($poet_info->date_of_death)
            <tr>
              <th class="px-2">{{ trans('labels.dod') }}</th>
                <td>{{ sindhi_date('D، d M Y', strtotime($poet_info->date_of_death)) }}</td>
            </tr>
            <tr>
              <th class="px-2">{{ trans('labels.death_place') }}</th>
              <td>{{ $poet_detail->deathPlace->city_name }} </td>
            </tr>
            @endif

            
        </table>
        <p class="paragraph">
          {!! nl2br($poet_detail->poet_bio) !!}
        </p>
      </div>
    </div>
  </div>
</div>
<!---= END poetInfoDetailModal =--->    
    
@endsection


@section('js')
<script src="https://www.youtube.com/iframe_api"></script>

<script>
  var player; // Define a variable to hold the YouTube player instance

  $(function () {
    applyFilterOnTextArea();

    // Edit Comment
    $(document).on('click', '.btn-edit-comment', function () {
      var commentId = $(this).closest('div.comment').data('id');
      var commentDiv = $('#comment_'+commentId);
      var commentText = commentDiv.find('div.comment-text').text().trim();
      var picture = commentDiv.find('img').attr('src');
      var userName = commentDiv.find('h5.px-2').text();
      $('.update-comment-div').append(loadEditComment(picture, userName,commentText,commentId));
      applyFilterOnTextArea();
      commentDiv.remove();
    })
    
    // load more comments
    $(document).on('click', '.btn-load-more-comments', function () {
      var lastCommentId = $('.user-comments').find('.comment:last-child').data('id')
      //comments.load-more
      /// Ajax Request for Fetch More Comments
      $.ajax({
        url:'{{ route('comments.load-more') }}',
        type:'post',
        data:{poetry_id:{{ $poetry->id }}, last_comment_id: lastCommentId},
        headers: {
          'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function (){
          /// do domthing
          $('.btn-load-more-comments').hide();
          $('.btn-load-more-spinner').show();
        },
        success: function (response){
          
          if(response.status == 200)
          {
            $('.btn-load-more-comments').show();
            $('.btn-load-more-spinner').hide();
            $('.user-comments').append(response.html_comments)
          }

          if(response.status == 403)
          {
            // hide load more button
            $('.btn-load-more-spinner').remove();
            $('.btn-load-more-comments').remove();
          }
        },
          error: function (xhr, ajaxOptions, thrownError){
          console.error('error called on ajax request of Fetch More Comments')
          console.error(xhr.status)
          console.error(thrownError)
        }
      });
    })
    

    // post new comment
    $(document).on('click', '.btn-submit-comment', function (e) {
      e.preventDefault()
      var userComment = $('#userCommentTextArea').val().trim();
      var p_id = $(this).data('poetry-id');
      var userName = $('#loggedInUserName').html();
      var image = $('#loggedInUserAvatar').attr('src');
      /// Ajax Request for Submit Comment
      $.ajax({
        url:'{{ route('comment.send') }}',
        type:'post',
        data:{
          comment:userComment,
          avatar: image,
          name: userName,
          poetry_id:p_id, 
        },
        headers: {
          'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function (){
          $(this).attr('disabled', true);
        },
        success: function (response){
          console.log('success called on ajax request of Submit Comment')
          if(response.type ==='success')
          {
            $('.user-inputs').remove();
            $('.user-comments').prepend(response.comment)
          }

          console.log(response.message);
          
        },
          error: function (xhr, ajaxOptions, thrownError){
          console.error('error called on ajax request of Submit Comment')
          console.error(xhr.status)
          console.error(thrownError)
        }
      });
    });

    // update existing comment
    $(document).on('click', '.btn-update-comment', function (e) {
      e.preventDefault();
      var userComment = $('#userCommentTextArea').val().trim();
      var commentId = $('#commentId').val();
      var p_id = $(this).data('poetry-id');
      var userName = $('#loggedInUserName').html();
      var image = $('#loggedInUserAvatar').attr('src');

      /// Ajax Request for Updating Existing Poetry
      $.ajax({
        url:'{{ route('comment.update') }}',
        type:'post',
        data:{
          comment_id: commentId,
          comment:userComment,
          avatar: image,
          name: userName,
          poetry_id:p_id, 
        },
        headers: {
          'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function (){
          /// do domthing
          $(this).attr('disabled', true);
        },
        success: function (response){
          console.log('success called on ajax request of Updating Existing Poetry')
          if(response.type ==='success')
          {
            $('.user-inputs').remove();
            $('.user-comments').prepend(response.comment)
          }
          
        },
          error: function (xhr, ajaxOptions, thrownError){
          console.error('error called on ajax request of Updating Existing Poetry')
          console.error(xhr.status)
          console.error(thrownError)
        }
      });
    })

    

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



<script>
function applyFilterOnTextArea() {
  const textarea = $('#userCommentTextArea');
  
  if(textarea.length > 0)
  {
    // Set the minimum and maximum number of rows
    const minRows = 3;
    const maxRows = 10;
    // Function to adjust the textarea's rows based on content
    function adjustTextareaRows() {
      const lines = textarea.val().split('\n').length;
      const newRows = Math.min(maxRows, Math.max(minRows, lines));
      textarea.attr('rows', newRows);
    }
    
    // Attach input event handler to the textarea
    textarea.on('input', adjustTextareaRows);
    
    // Initialize rows on page load
    adjustTextareaRows();

    // Set the character limit and get references to relevant elements
    const characterLimit = 200;
    const $textarea = $('#userCommentTextArea');
    const $messageParagraph = $('.remaining-letters p.message');

    // Attach the input event handler to the textarea
    $textarea.on('input', function() {
      
      const inputText = $textarea.val();
      const remainingChars = characterLimit - inputText.length;

      // Define the text_message based on the remaining characters
      let text_message = '';
      if (remainingChars === 0) {
        text_message = 'You can not write more';
      } else if (remainingChars < 0) {
        text_message = `توھان ${Math.abs(remainingChars)} واڌاري اکر لکيا آھن، مھرباني ڪري اُھي ڊاھيو`;
      } else {
        text_message = `توھان ${remainingChars} وڌيڪ اکر لکي سگهو ٿا`;
      }


      // Update the message text
      $messageParagraph.text(text_message);

      // Add or remove the "red" class based on the condition
      if (remainingChars < 0) {
        $messageParagraph.addClass('red');
        $('.btn-submit-comment').attr('disabled', true)
      } else {
        $messageParagraph.removeClass('red');
        $('.btn-submit-comment').attr('disabled', false)
      }
    });
  }
}

function loadEditComment(picture, name,text,id)
{
  var html = '<div class="user-inputs mt-3">'+
  '  <div class="d-flex justify-content-between">'+
  '    <div class="user-profile d-flex justify-content-aware align-items-center">'+
  '      <img src="'+picture+'" class="rounded-circle" id="loggedInUserAvatar" width="50px" height="auto" alt="">'+
  '      <h5 class="px-2" id="loggedInUserName">'+name+'</h5>'+
  '    </div>'+
  '    <div class="remaining-letters">'+
  '      <p class="message">توھان <span class="counts">200</span> اکرن ۾ پنھنجي راءِ ڏئي سگهو ٿا</p>'+
  '    </div>'+
  '  </div>'+
  '  <div class="input-area">'+
  '    <textarea name="user_comment" id="userCommentTextArea" rows="3">'+text+'</textarea>'+
  '    <input type="hidden" id="commentId" value="'+id+'">'+
  '  </div>'+
  '  <div class="comment-buttons mt-2 d-flex justify-content-between">'+
  '    <button type="button" class="btn btn-sm btn-baakh btn-update-comment" data-poetry-id="{{ $poetry->id; }}">{{ trans('buttons.submit_comment') }}</button>'+
  '    <div class="text-actions">'+
  '      <button type="button" class="btn btn-default" onclick="insertLink()"><bi class="bi bi-link-45deg"></bi></button>'+
  '      <button type="button" class="btn btn-default" onclick="applyItalic()"><bi class="bi bi-type-italic"></bi></button>'+
  '      <button type="button" class="btn btn-default" onclick="applyBold()"><bi class="bi bi-type-bold"></bi></button>'+
  '    </div>'+
  '  </div>'+
  '</div>';
  return html;
}

function applyBold() {
  const textarea = document.getElementById('userCommentTextArea');
  const selectedText = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
  const newText = `<b>${selectedText}</b>`;
  insertTextAtCursor(newText);
}

function applyItalic() {
  const textarea = document.getElementById('userCommentTextArea');
  const selectedText = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
  const newText = `<i>${selectedText}</i>`;
  insertTextAtCursor(newText);
}

function insertLink() {
  const textarea = document.getElementById('userCommentTextArea');
  const linkURL = prompt('Enter the URL for the link:');
  if (linkURL) {
    const selectedText = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
    const newText = `<a href="${linkURL}" target="_blank">${selectedText}</a>`;
    insertTextAtCursor(newText);
  }
}

function insertTextAtCursor(text) {
  const textarea = document.getElementById('userCommentTextArea');
  const startPos = textarea.selectionStart;
  const endPos = textarea.selectionEnd;
  const scrollTop = textarea.scrollTop;
  textarea.value =
    textarea.value.substring(0, startPos) +
    text +
    textarea.value.substring(endPos, textarea.value.length);
  textarea.focus();
  textarea.selectionStart = startPos + text.length;
  textarea.selectionEnd = startPos + text.length;
  textarea.scrollTop = scrollTop;
}
</script>
 


@endsection