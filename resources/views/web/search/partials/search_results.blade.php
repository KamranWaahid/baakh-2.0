<div class="search-results">
    @foreach($results as $result)
        <!-- Render each search result here -->
        <div class="result-item">
            @if($result instanceof Poetry)
                <h3>{{ $result->title }}</h3>
            @elseif($result instanceof Couplet)
                <p>{{ $result->couplet_text }}</p>
            @endif
        </div>
    @endforeach

    <!-- Render pagination links -->
    <div class="pagination">
        {{ $results->links() }}
    </div>
</div>
