<div>
    <!-- Student review START -->
	<div class="row">

        @foreach($comments as $comment)
		<!-- Review item START -->
		<div class="d-md-flex my-4">
			<!-- Avatar -->
			<div class="avatar avatar-xl me-4 flex-shrink-0">
				<img class="avatar-img rounded-circle" src="{{ asset($comment->user->profile_picture) }}" alt="avatar">
			</div>
			<!-- Text -->
			<div>
				<div class="d-sm-flex mt-1 mt-md-0 align-items-center">
					<h5 class="me-3 mb-0">{{ $comment->user->name }}</h5>
					<!-- Review star -->
					<ul class="list-inline mb-0">
                        @for ($i = 0; $i < 5; $i++)
                            <li class="list-inline-item me-0"><i class="@if($i >= $comment->stars) far @else fas @endif fa-star text-warning"></i></li>
                        @endfor
					</ul>
				</div>
				<!-- Info -->
				<p class="small mb-2">{{ $comment->created_at->locale('sd')->diffForHumans() }}</p>
				<p class="mb-2">{{ $comment->comment }}</p>
				<!-- Like and dislike button -->
				{{-- <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
                    <button 
                        wire:click="likeComment({{ $comment->id }})" 
                        class="btn btn-outline-light btn-sm mb-0">
                        <i class="far fa-thumbs-up me-1"></i> {{ $comment->likes }}
                    </button>
                    <button 
                        wire:click="dislikeComment({{ $comment->id }})" 
                        class="btn btn-outline-light btn-sm mb-0">
                        <i class="far fa-thumbs-down me-1"></i> {{ $comment->dislikes }}
                    </button>
                    <button wire:click="$set('commentId', {{ $comment->id }})">Reply</button>
				</div> --}}
			</div>

            {{-- @if ($commentId === $comment->id)
                <input type="hidden" wire:model="reply_id" value="{{ $comment->id }}">
                <textarea wire:model="reply" placeholder="Write a reply..." required></textarea>
                <button wire:click="submitReply">Submit Reply</button>
            @endif --}}

            {{-- @foreach($comments->where('parent_id', $comment->id) as $reply)
                <div class="reply">
                    <strong>{{ $reply->user->name }}</strong>
                    <p>{{ $reply->comment }}</p>
                </div>
            @endforeach --}}

		</div>
            {{-- @if($loop->index->last) 
                <!-- Divider -->
                <hr>
            @endif --}}
        @endforeach

		{{-- <!-- Comment children level 1 -->
		<div class="d-md-flex mb-4 ps-4 ps-md-5">
			<!-- Avatar -->
			<div class="avatar avatar-lg me-4 flex-shrink-0">
				<img class="avatar-img rounded-circle" src="{{ asset('assets/images/avatar/02.jpg') }}" alt="avatar">
			</div>
			<!-- Text -->
			<div>
				<div class="d-sm-flex mt-1 mt-md-0 align-items-center">
					<h5 class="me-3 mb-0">Louis Ferguson</h5>
				</div>
				<!-- Info -->
				<p class="small mb-2">1 days ago</p>
				<p class="mb-2">Water timed folly right aware if oh truth. Imprudence attachment him for sympathize. Large above be to means. Dashwood does provide stronger is. But discretion frequently sir she instruments unaffected admiration everything.</p>
			</div>
		</div> --}}

		<!-- Divider -->
		<hr>
		<!-- Review item END -->
 
		<!-- Divider -->
		<hr>
	</div>
	<!-- Student review END -->

    @auth
	<!-- Leave Review START -->
	<div class="mt-2">
		<h5 class="mb-4">{{ trans('buttons.comment') }}</h5>
		<div class="info">
			@if (session()->has('message'))
				<div class="alert alert-success">{{ session('message') }}</div>
			@endif

			@if (session()->has('error'))
				<div class="alert alert-danger">{{ session('error') }}</div>
			@endif
		</div>
		<form class="row g-3" wire:submit.prevent="submitComment">
			 
			<!-- Rating -->
			<div class="col-12 bg-light-input">
				<select wire:model="stars" class="form-select">
					<option value="5" selected="">★★★★★ (5/5)</option>
					<option value="4">★★★★☆ (4/5)</option>
					<option value="3">★★★☆☆ (3/5)</option>
					<option value="2">★★☆☆☆ (2/5)</option>
					<option value="1">★☆☆☆☆ (1/5)</option>
				</select>
			</div>
			<!-- Message -->
			<div class="col-12 bg-light-input">
                <textarea wire:model="comment" class="form-control" placeholder="{{ trans('buttons.comment') }}..."  rows="3" required></textarea>
                @error('comment') <span class="error">{{ $message }}</span> @enderror
			</div>
			<!-- Button -->
			<div class="col-12">
				<button type="submit" class="btn btn-primary mb-0">{{ trans('buttons.submit_comment') }}</button>
			</div>
		</form>
	</div>
	<!-- Leave Review END -->
    @else
		<p>{{ trans('buttons.login_to_comment') }}</p>
        {{-- <p>ڪتاب تي راءِ ڏيڻ لاءِ مھرباني ڪري <a href="{{ route('users.login') }}">لاگ ان</a> ڪريو.</p> --}}
    @endauth
</div>