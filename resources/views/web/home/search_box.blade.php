<div class="hero-search-container">
    <input type="text" id="hero-search-box" class="form-control hero-search-box" placeholder="ڳولھا ڪريو.">
    <i class="bi bi-search hero-search-icon"></i>
    <div class="hero-suggestions-card" id="hero-suggestions-card">
        <!-- AJAX suggestion items will be inserted here -->
    </div>
</div>

@push('css')
<style>
/* Search Container Styling */
.hero-search-container {
    position: relative;
    max-width: 500px;
    margin: 20px auto;
    border: 1px solid #dfe1e5;
    border-radius: 24px;
    background-color: #fff;
    /*box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);*/
    display: flex;
    align-items: center;
    transition: box-shadow 0.3s ease;
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
    display: none; /* This will be controlled by JavaScript */
    max-height: 250px;
    overflow-y: auto;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
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
    opacity: 0.8; /* Reduced opacity for a more subtle effect */
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); /* Softer shadow */
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
                suggestions.push({
                    text: poetMatch.keyword + ' ' + categoryMatch.keyword,
                    link: poetMatch.route.replace(/\/+$/, '') + '/' + categoryMatch.route
                });
            } else if (poetMatch) {
                suggestions.push({
                    text: poetMatch.keyword,
                    link: poetMatch.route
                });
                categories.forEach(category => {
                    suggestions.push({
                        text: poetMatch.keyword + ' ' + category.keyword,
                        link: poetMatch.route.replace(/\/+$/, '') + '/' + category.route
                    });
                });
            }

            // Display suggestions
            let suggestionsHTML = '';
            suggestions.slice(0, 8).forEach(item => {
                suggestionsHTML += `<div class="hero-suggestion-item">
                    <i class="bi bi-search"></i> <a href="${item.link}">${item.text}</a>
                </div>`;
            });
            $('#hero-suggestions-card').html(suggestionsHTML).addClass('show');
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
