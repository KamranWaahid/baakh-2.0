<div class="hero-search-container">
    <input type="text" id="hero-search-box" class="form-control hero-search-box" placeholder="Search...">
    <i class="bi bi-search hero-search-icon"></i>
    <div class="hero-suggestions-card" id="hero-suggestions-card">
        <!-- AJAX suggestion items will be inserted here -->
    </div>
</div>

@push('css')
<style>
    /* Custom search box styling */
    .hero-search-container {
        position: relative;
        max-width: 500px;
        margin: 20px auto;
        border: 1px solid black;
        z-index: 999;
    }

    .hero-search-box {
        padding-right: 40px;
    }

    .hero-search-icon {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        color: #6c757d;
        cursor: pointer;
    }

    /* AJAX suggestions styling */
    .hero-suggestions-card {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 100;
        border: 1px solid #ddd;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: #fff;
        display: none;
        max-height: 250px;
        overflow-y: auto;
    }

    .hero-suggestion-item {
        padding: 10px;
        cursor: pointer;
    }

    .hero-suggestion-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('js')
    <script>
      $(document).ready(function() {
    let poets = [];
    let categories = [];
    let dataLoaded = false;

    // Load JSON files on the first focus
    $('#hero-search-box').on('focus', function() {
        if (!dataLoaded) {
            $.getJSON('/json/poets.json', function(data) {
                poets = data;
            });
            $.getJSON('/json/categories.json', function(data) {
                categories = data;
            });
            dataLoaded = true;
        }
    });

    // Handle input event for search suggestions
    $('#hero-search-box').on('input', function() {
        const query = $(this).val().trim();

        if (query.length > 1 && dataLoaded) {
            let suggestions = [];
            
            // Split query by spaces to handle potential multi-word inputs
            const queryParts = query.split(" ");
            let poetMatch = null;
            let categoryMatch = null;

            // Find a poet match in the query
            poets.forEach(poet => {
                if (queryParts.some(part => poet.keyword.includes(part))) {
                    poetMatch = poet;
                }
            });

            // Find a category match in the query
            categories.forEach(category => {
                if (queryParts.some(part => category.keyword.includes(part))) {
                    categoryMatch = category;
                }
            });

            // Generate suggestions based on matches
            if (poetMatch && categoryMatch) {
                // If both a poet and category match, show combined result
                suggestions.push({
                    text: poetMatch.keyword + categoryMatch.keyword,
                    link: poetMatch.route.replace(/\/+$/, '') + '/' + categoryMatch.route
                });
            } else if (poetMatch) {
                // If only poet matches, show the poet's name suggestion
                suggestions.push({
                    text: poetMatch.keyword,
                    link: poetMatch.route
                });

                // Also show concatenated suggestions for each category
                categories.forEach(category => {
                    suggestions.push({
                        text: poetMatch.keyword + category.keyword,
                        link: poetMatch.route.replace(/\/+$/, '') + '/' + category.route
                    });
                });
            }

            // Display suggestions
            let suggestionsHTML = '';
            suggestions.slice(0, 8).forEach(item => {
                suggestionsHTML += `<div class="hero-suggestion-item">
                    <a href="${item.link}">${item.text}</a>
                </div>`;
            });
            $('#hero-suggestions-card').html(suggestionsHTML).fadeIn();
        } else {
            $('#hero-suggestions-card').fadeOut();
        }
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.hero-search-container').length) {
            $('#hero-suggestions-card').fadeOut();
        }
    });

    // Handle suggestion click
    $(document).on('click', '.hero-suggestion-item', function() {
        $('#hero-search-box').val($(this).text().trim());
        $('#hero-suggestions-card').fadeOut();
    });
});


    </script>
@endpush