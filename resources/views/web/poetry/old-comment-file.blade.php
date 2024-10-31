
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