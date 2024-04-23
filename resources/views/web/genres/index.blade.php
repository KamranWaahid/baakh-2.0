@extends('layouts.web')

@section('body')
    <div class="top-spacer"></div>
    <!-- ========== Start Genres Section ========== -->
    <section class="section-bg pt-5 pb-2">
        <div class="container">
            <h3 class="text-baakh">{{ trans('menus.genre') }}</h3>
            <p class="text-justify">
                باک جي ھِن صفحي تي توھان سنڌي شاعريءَ جي اھم صنفن بابت معلومات حاصل ڪري سگهو ٿا ۽ گڏوگڏ انھن صنفن ۾ موجود شاعريءَ کي پڙھي سگهو ٿا.
            </p>
        </div>
    </section>
    <!-- ========== End Genres Section ========== -->

    <!-- ========== Start Genres Section ========== -->
    <section class="section-bg py-2 genres-section">
        <div class="container">
            <div class="row gy-3">
                @foreach ($genres as $item)
                    @php
                        $info = $item->detail;
                    @endphp
                    <div class="col-6 col-md-3">
                        <a href="{{ route('genres.show', $item->slug) }}" class="text-primary">
                            <div class="card genre-card translate-1">
                                <div class="card-body">
                                    <h4 class="text-center genre-title">{{ $info->cat_name }}</h4>
                                    <p class="text-justify">{!! Str::limit($info->cat_detail, 200) !!}</p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <!-- ========== End Genres Section ========== -->
    
@endsection