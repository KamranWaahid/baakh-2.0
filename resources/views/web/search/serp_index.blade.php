@extends('layouts.web')

@push('css')
    <script>
        {
  "@context": "https://schema.org",
  "@type": "WebSite",
  "url": "https://mydomain.com/",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://mydomain.com/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}

    </script>

    <style>
        #serp_content .search-result {
    margin-bottom: 20px;
}
.result-title {
    font-size: 18px;
    font-weight: bold;
    color: #1a0dab;
    text-decoration: none;
}
.result-title:hover {
    text-decoration: underline;
}
.result-url {
    font-size: 14px;
    color: #006621;
}
.result-snippet {
    font-size: 14px;
    color: #545454;
}
#infoBox .card {
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.related-info-list {
    list-style-type: none;
    padding: 0;
}
.related-info-list li {
    margin-bottom: 10px;
}

    </style>
@endpush

@section('body')
<div class="top-spacer"></div>
<div class="top-spacer"></div>

<section id="serp">
    <div class="container">
        <div class="row">
            <!-- Start [Col-8 for results] -->
            <div id="serp_content" class="col-md-8 col-sm-12">
                <h4 class="mb-4">Search results for: "{{ $query }}"</h4>
                <div class="search-result">
                    <a href="#" class="result-title">Result Title 1</a>
                    <p class="result-url">https://example.com/page1</p>
                    <p class="result-snippet">
                        This is a brief snippet or description for result 1.
                    </p>
                </div>
                <div class="search-result">
                    <a href="#" class="result-title">Result Title 2</a>
                    <p class="result-url">https://example.com/page2</p>
                    <p class="result-snippet">
                        This is a brief snippet or description for result 2.
                    </p>
                </div>
                <!-- Additional results can be dynamically added here -->
            </div>
            <!-- End [Col-8 for results] -->

            <!-- Start [Col-4 for InfoBox] -->
            <div id="infoBox" class="col-md-4 col-sm-12">
                <div class="card card-body">
                    <h5>Related Information</h5>
                    <p>Here you can add related information or a knowledge panel style content, such as:</p>
                    <ul class="related-info-list">
                        <li><strong>Title:</strong> Example</li>
                        <li><strong>Description:</strong> Brief description</li>
                        <li><strong>More Info:</strong> <a href="#">Visit Page</a></li>
                    </ul>
                </div>
            </div>
            <!-- End [Col-4 for InfoBox] -->
        </div>
    </div>
</section>

@endsection

@push('js')
    <script>
        $(function () {
            var query = '{{ $query }}';
            $('.btn-baakh-search').trigger('click');
            $('.btn-baakh-search').focus(false);
            $('.search-input').val(query);
        })
    </script>
@endpush