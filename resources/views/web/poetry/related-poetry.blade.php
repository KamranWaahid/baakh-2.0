

    <a href="{{ route('poetry.with-slug', ['category'=> $poetry->category->slug, 'slug' => $poetry->poetry_slug]).'?lang='.$poetry->lang }}">
        <div class="row poets-with-name" id="poets-with-name">
        <div class="col-2 poet-pic text-center">
            <img src="{{ asset($poetry->poet->poet_pic) }}" width="50px" class="img-fluid rounded-circle p-2" alt="">
        </div>
        <div class="col-10 poet-info p-2">
            <h6 class="text-primary">{{ $poetry->poetry_title }}</h6>
            <small class="text-secondary">{{ $poetry->poet->details->poet_laqab }}</small>
        </div>
        </div>
        @if ($key != $total -1)
        <div class="baakh-list-divider"></div>
        @endif
    </a>
    