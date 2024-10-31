<div>
    @auth()
        <button type="button" 
        class="btn btn-like btn-default @if($isLiked) liked @endif"  wire:click="toggleLike">
        <i class="bi bi-heart{{ $isLiked ? '-fill text-baakh' : '' }} }"></i></button>
    @else
        <button type="button" class="btn btn-like btn-default not-logged-in" data-url="{{ url()->current() }}" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-heart"></i>
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