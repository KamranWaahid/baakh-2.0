<div class="comment" id="comment_{{ $id }}" data-id="{{ $id }}">
    <div class="d-flex justify-content-between">
        <div class="user-profile d-flex justify-content-aware align-items-center">
        <img src="{{ $avatar }}" class="rounded-circle" width="40px" height="auto" alt="{{ $name }}">
        <h5 class="px-2">{{ $name }}</h5>
        </div>{{-- /.user-profile --}}

        <div class="comment-time d-flex justify-content-between">
            <p class="message">{{ $time }}</p>
            {!! ($editable) ? '<button class="btn btn-sm btn-edit-comment"><i class="bi bi-pencil-square"></i></button>' : '' !!}
        </div>
        
    </div>
    <div class="comment-text">
        {!! $comment !!}
    </div>
</div>