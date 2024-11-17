<div class="hero-search-container">
    <input type="text" id="hero-search-box" class="form-control hero-search-box" placeholder="{{ trans('labels.search_placeholder') }}">
    <i class="bi bi-search hero-search-icon"></i>
    <div class="hero-suggestions-card" id="hero-suggestions-card">
        <!-- AJAX suggestion items will be inserted here -->
    </div>
</div>

@push('css')
<style>
    .hero {
        overflow: visible;
    }
/* Search Container Styling */
.hero-search-container {
    position: relative;
    max-width: 500px;
    margin: 20px auto;
    border: 1px solid #dfe1e5;
    border-radius: 24px;
    background-color: #fff;
    display: flex;
    align-items: center;
    transition: box-shadow 0.3s ease;
    z-index: 2;
}

/* Search Box Styling */
.hero-search-box {
    width: 100%;
    padding: 10px 40px 10px 15px;
    font-size: 16px;
    border: none;
    outline: none;
    background: none;
    color: #202124;
    border-radius: 24px;
    transition: box-shadow 0.2s ease;
}

/* Remove blue outline and add subtle shadow on focus */
.hero-search-box:focus {
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
}

/* Search Icon Styling */
.hero-search-icon {
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    color: #80868b;
    font-size: 16px; /* Smaller search icon */
    cursor: pointer;
    transition: transform 0.3s ease;
}

/* Suggestions Box Styling */
.hero-suggestions-card {
    position: absolute;
    top: calc(100% + 4px); /* Place just below the search box */
    left: 0;
    right: 0;
    border: 1px solid #dfe1e5;
    border-radius: 12px; /* Subtler rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    background-color: #fff;
    display: none;
    max-height: 450px;
    overflow-y: auto;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    z-index: 1000;
}

/* Individual Suggestion Item */
.hero-suggestion-item {
    padding: 10px 15px;
    cursor: pointer;
    font-size: 14px;
    color: #202124;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;

}

.hero-suggestion-item a {
    text-decoration: none;
    color: inherit;
    display: block;
    flex-grow: 1;
}

/* Search icon for each suggestion */
.hero-suggestion-item i {
    font-size: 14px;
    color: #80868b;
}

/* Hover effect on suggestion items */
.hero-suggestion-item:hover {
    background-color: #f1f3f4;
}

/* Show Suggestions Box */
.hero-suggestions-card.show {
    display: block;
    opacity: 1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); /* Softer shadow */
}

</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    
    var noResultFound = '<div class="hero-suggestion-item"> <i class="bi bi-search"></i>{{ trans('labels.search_no_results') }}</div>';

    $('#hero-search-box').on('focus', function () {
        $('html').scrollTop(80)
    });


    // Handle input event for search suggestions
    $('#hero-search-box').on('input', function() {
        const query = $(this).val().trim();

        if (query.length > 1) {
            
            var _lang = '{{ app()->getLocale() }}';
            var route = '{{ route('web.search.index') }}';

            /// Ajax Request for Get Suggestions
            $.ajax({
                url: route + '/suggestions/'+query + '/' +_lang ,
                type:'get',
                headers: {
                    'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function (){
                    console.log('beforeSend called on ajax request of Get Suggestions');
                },
                success: function (res){
                    if(res.error === false) {
                        $('#hero-suggestions-card').html(res.data);
                    }else{
                        $('#hero-suggestions-card').html(noResultFound)
                    }
                },
                error: function (xhr, ajaxOptions, thrownError){
                    console.error('error called on ajax request of Get Suggestions')
                    console.error(xhr.status)
                    console.error(thrownError)
                }
            });


            $('#hero-suggestions-card').addClass('show');

        } else {
            $('#hero-suggestions-card').removeClass('show');
        }
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.hero-search-container').length) {
            $('#hero-suggestions-card').removeClass('show');
        }
    });

    // Handle suggestion click
    $(document).on('click', '.hero-suggestion-item', function() {
        $('#hero-search-box').val($(this).text().trim());
        $('#hero-suggestions-card').removeClass('show');
    });
});
</script>
@endpush
