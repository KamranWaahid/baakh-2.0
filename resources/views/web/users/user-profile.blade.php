@extends('layouts.web')

@section('css')
    <style>
        .v-l {
            border-left: 3px solid #d7d7d7;
            height: auto;
        }
        .user-activities-container {
            background: var(--color-poet-sections);
        }
        .user-buttons {
            margin-top:50px;
        }
        .user-buttons .btn {
            padding-top:4px !important;
            padding-bottom:4px !important;
            padding-right:30px;
            padding-left:30px;
            font-size: 1.2rem;
        }
        .user-profile .picture 
        {
            width: 200px; 
            margin-top:-100px;
        }
        .user-profile .picture img {
            border: 5px solid white;
        }
    </style>
@endsection

{{-- Body Section --}}
@section('body')

<!-- ========== Start Information Section ========== -->
<section id="user-info" class="user-info p-0">
        
    <!---= START background image if available =--->
    <div class="bg-cover-bundle bg-primary" ></div>
    <!---= END background image if available =--->
    

    <!---= START container =--->
    <div class="container p-2" style="background: var(--color-poet-sections); margin-top:-70px; min-height:150px;">
    <!---= START User Picture =--->
    <div class="d-flex justify-content-between pt-3 ps-3 pe-3 user-profile">
        <div class="setting-button" style="min-width: 200px;">
            @if (Auth::user()->can('view.dashboard'))
                <a href="{{ route('dashboard') }}" class="btn btn-baakh"><i class="bi bi-speedometer2 mx-2"></i>ايڊمن پئنل</a>
            @endif
            <a href="{{ route('user.profile.edit') }}" class="btn btn-baakh">{{ trans('buttons.settings') }}</a>
        </div>
        <!---= START col-3 for photo =--->
        <div class="picture" id="user-picture">
            <img src="{{ file_exists($profile->avatar) ? asset($profile->avatar) : str_replace('s96-c', 's300-c', $profile->avatar) }}" width="300px" class="img-fluid rounded-circle" alt="">
            <h3 class="text-center mt-3">
                {{ $user_name; }}
            </h3>
            <div class="user-progress text-center d-flex justify-content-between mt-4">
                <div class="user-likes">
                    <h1>{{ $total_likes }}</h1>
                    <p>{{ trans('labels.likes') }}</p>
                </div>
                <div class="v-l"></div>
                <div class="user-comments">
                    <h1>{{ $total_comments }}</h1>
                    <p>{{ trans('labels.user_comments') }}</p>
                </div>
            </div>
        </div>
        <!---= END col-3 for photo =--->
        <div class="logout-button d-flex justify-content-end"  style="min-width: 200px;">
            <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-baakh"><i class="bi bi-door-closed"></i> {{ trans('buttons.logout') }}</button>
            </form>
        </div>
 
    </div>
    <!---= END User Picture =--->
     
     <div class="spacer-dotted mt-3"></div>
  </div>
  <!---= END container =--->
</section>
<!-- ========== End Information Section ========== -->

<!-- ========== Start User Activity Section ========== -->
<section class="section-bg mt-0 pt-0">
    <div class="container py-4 px-3 user-activities-container">
        <h3 class="text-center">{{ trans('labels.likes') }}</h3>
        <!-- ========== Start buttons ========== -->
        <div class="user-buttons text-center">
            <a href="{{ URL::localized(route('user.favorites', 'couplets')) }}" class="btn btn-secondary btn-gol col {{ $active_category == 'couplets' ? 'active' : '' }}">{{ trans('menus.couplets') }}</a>
            <a href="{{ URL::localized(route('user.favorites', 'poets')) }}" class="btn btn-secondary btn-gol col {{ $active_category == 'poets' ? 'active' : '' }}">{{ trans('menus.poets') }}</a>
            <a href="{{ URL::localized(route('user.favorites', 'bundles')) }}" class="btn btn-secondary btn-gol col {{ $active_category == 'bundles' ? 'active' : '' }}">{{ trans('labels.bundles') }}</a>
            <a href="{{ URL::localized(route('user.favorites', 'tags')) }}" class="btn btn-secondary btn-gol col {{ $active_category == 'tags' ? 'active' : '' }}">{{ trans('labels.tags') }}</a>
            @foreach ($categories as $item)
                <a href="{{ URL::localized(route('user.favorite.poetry', $item->slug)) }}" class="btn btn-secondary btn-gol {{ $active_category == $item->slug ? 'active' : '' }} col">{{ Str::ucfirst($item->cat_name) }} ({{ $item->item_count }})</a>    
            @endforeach
            
        </div>
        <!-- ========== End buttons ========== -->

        <!-- ========== Start Liked Items ========== -->
        <div class="liked-items py-3 col-lg-8 col-md-8 col-sm-12 m-auto" id="liked-items">
            <div class="loaded-items" data-limit="10" data-category="{{ $active_category }}" data-item_type="{{ $item_type }}" data-start="0"></div>
            
        </div>
        <div class="loader_spinner">
            <div class="d-flex align-items-center justify-content-center p-5">
                <div class="spinner-grow text-danger" role="status"></div>
            </div>
        </div>
        <!-- ========== End Liked Items ========== -->
    </div>
</section>
<!-- ========== End User Activity Section ========== -->



 
@endsection
{{-- End of Body Section --}}



{{-- CSS with Custom Styles --}}

@section('css')
<style>
</style>
@endsection

{{-- Java Script --}}
@section('js')
<script>
    $(function () {
        var loadingData = false;
        var loadedAllData = false;
        var start = 0; // Initialize start value
    
       
        loadMorePoetry(function() {
            loadingData = false;
        });
 
        // Event listener for scroll events
        $(window).scroll(function() {
            const poetryContainer = $('#liked-items');
            const lastPoetry = poetryContainer.children('.loadMorePoetry').last();
    
            // Check if the last couplet is visible on the screen
            if (lastPoetry.length > 0) {
                const lastPoetryOffset = lastPoetry.offset().top + lastPoetry.outerHeight();
                const pageOffset = $(window).scrollTop() + $(window).height();
                const bottomOffset = 200; // Bottom margin to trigger the event
    
                if (pageOffset > lastPoetryOffset - bottomOffset && !loadingData && !loadedAllData) {
                    // Load more couplets
                    loadingData = true;
                    loadMorePoetry(function() {
                        loadingData = false;
                    });
                }
            }
        });
    
        function loadMorePoetry() {
             
    
            // Assign data to variables
            var bsurl = '{!! url('/') !!}';
            var site_lang = '{{ app()->getLocale() }}' // selected language
            var poetry_type = $('.loaded-items').data('item_type');
            var poet = {{ $profile->id }}; // $poet->id
            var page_category = '{{ $active_category }}' // $show_category
            var start_from = $('.loaded-items').last().data('start');
            var display_items =  $('.loaded-items').last().data('limit');
             
            loadingData = true;

            if(poetry_type == 'others')
            {
                var load_url = '/user/load-items/others';
            }else{
                var load_url = '/user/load-items/poetry';
            }
    
            $.ajax({
                url: bsurl+load_url,
                method: 'post',
                data:{
                    itemType: '{{ $active_category }}',
                    type:poetry_type, 
                    category:page_category, 
                    start:start_from, 
                    limit:display_items
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                beforeSend:function(){
                    $(".loader_spinner").show();
                    console.log('---------------- params --------------');
                    console.log('poetry_type = '+poetry_type);
                    console.log('category = '+page_category);
                    console.log('start = '+start_from);
                    console.log('limit = '+display_items);
                },
                success:function(result){
                    if(result.type === 'success'){
                        loadingData = false;
                        $('#liked-items').append(result.html)
                    }
                    $(".loader_spinner").hide();
                    loadingData = false;
    
                    if(result.code == 404){
                        loadedAllData = true;
                    }
                },
                error:function(xhr, status, errorThrown){
                    loadingData = false;
                    $(".loader_spinner").hide();
                    console.log('error called =--- ' + xhr.responseText)
                }
            });
        }
     
    });

    function openUrl(item)
    {
        window.location.href=$(item).data('url')
    }
    
    </script>
@endsection