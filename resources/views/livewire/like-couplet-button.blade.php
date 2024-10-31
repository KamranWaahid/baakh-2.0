<div>
    @auth()
        <button type="button" 
        class="btn btn-like btn-default @if($isLiked) liked @endif"  wire:click="toggleLike">
        <i class="bi-solid bi-heart{{ $isLiked ? '-fill text-baakh' : '' }} me-2"></i>
        {{ $isLiked ? trans('buttons.like_it_false')  : trans('buttons.like_it') }}
    </button>
    @else
        <button type="button" class="btn btn-like btn-default not-logged-in" data-url="{{ url()->current() }}" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi-solid bi-heart me-2"></i>{{ trans('buttons.like_it') }}
        </button>
        @push('js')
            <script>
                $(function() {
                    // Optional: Pre-fill the redirect URL when the modal opens
                    $('#loginModal').on('shown.bs.modal', function () {
                        var url = $('.not-logged-in').data('url');
                        var url_current = window.location.href;
                        $('.bg-google').attr('href', '{{ route('login.with-google') }}' + '?url=' + encodeURIComponent(url_current));
                    });
                });
            </script>
        @endpush
    @endauth
</div>