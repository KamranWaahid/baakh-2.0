@extends('layouts.web')

@section('body')
    <div class="top-spacer"></div>
    <!-- ========== Start Genres Section ========== -->
    <section class="section-bg pt-5 pb-2">
        <div class="container">
            <div class="d-flex justify-content-between">
                <h3 class="text-baakh">{{ $info->cat_name }}</h3>
                <div class="buttons">
                    <a href="{{ route('genres.poetry', $genre->slug) }}" class="btn btn-baakh"><i class="bi bi-list mr-2"></i>{{ trans('labels.poetry') }}</a>
                </div>
            </div>
            <div class="text-justify">{!! $info->cat_detail !!}</div>
        </div>
    </section>
    <!-- ========== End Genres Section ========== -->

    
    
@endsection