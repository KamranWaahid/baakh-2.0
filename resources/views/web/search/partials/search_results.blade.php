<div class="search-results">
    @foreach($results as $result)
    {{-- {{ json_encode($result, JSON_UNESCAPED_UNICODE) }} --}}
        <div class="result-item">
            @if ($result->poetry && $result->poetry->category)
                @php
                    $link = route('poetry.with-slug', ['category' => $result->poetry->category->slug, 'slug' => $result->poetry->poetry_slug])
                @endphp
            @else 
                @php
                    $link = ''
                @endphp
            @endif

            @php
                $poetUrl = route('poets.slug', ['category' => '', 'name' => $result->poet->poet_slug])
            @endphp
               
            @if($result->type === 'poetry')
                <div class="card mb-2">
                    <div class="card-body">
                        <a href="{{ URL::localized($link) }}">
                            <p class="result-couplet-text">{!! nl2br($result->title)  !!}</p>
                        </a>

                        <a href="{{ URL::localized($poetUrl) }}" class="text-end">
                            <p class="result-category">{{ $result->category->cat_name ?? __('Unknown Category') }}<br>
                                {{ trans('labels.search_poet') }}: {{ $result->poet->poet_laqab ?? __('Unknown Poet') }}</p>
                        </a>
                    </div>
                </div>
            @elseif($result->type === 'couplets')
                <div class="card mb-2">
                    <div class="card-body">
                        <a href="{{ URL::localized($link) }}">
                            <p class="result-couplet-text">{!! nl2br($result->title)  !!}</p>
                        </a>

                        <a href="{{ URL::localized($poetUrl) }}" class="text-end">
                            <p class="result-poet">{{ trans('labels.search_poet') }}: {{ $result->poet->poet_laqab ?? __('Unknown Poet') }}</p>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @endforeach

    <!-- Render pagination links -->
    <div class="pagination">
        {{ $results->links() }}
    </div>
</div>
