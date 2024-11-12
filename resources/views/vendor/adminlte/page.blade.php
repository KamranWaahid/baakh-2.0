@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('adminlte_css')
    @stack('css')
    @yield('css')
    <link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
    <style>
        .sd{
            font-family: 'MB Lateefi SK 2.0';
            font-size: 1.2rem;
        }
    </style>
@stop

@section('classes_body', $layoutHelper->makeBodyClasses())

@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    <div class="wrapper">

        {{-- Preloader Animation (fullscreen mode) --}}
        @if($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        {{-- Top Navbar --}}
        @if($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        {{-- Left Main Sidebar --}}
        @if(!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        {{-- Content Wrapper --}}
        @empty($iFrameEnabled)
            @include('adminlte::partials.cwrapper.cwrapper-default')
        @else
            @include('adminlte::partials.cwrapper.cwrapper-iframe')
        @endempty

        {{-- Footer --}}
        @hasSection('footer')
            @include('adminlte::partials.footer.footer')
        @endif

        {{-- Right Control Sidebar --}}
        @if(config('adminlte.right_sidebar'))
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
    <script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
    
    @if (session()->has('message'))
       <script>
        $(function () {
            toastr.info('{{ session('message') }}')
        })
       </script>
    @endif

    @if (session()->has('error'))
       <script>
        $(function () {
            toastr.error('{{ session('error') }}')
        })
       </script>
    @endif

    @if (session()->has('success'))
       <script>
        $(function () {
            toastr.success('{{ session('success') }}')
        })
       </script>
    @endif
    <script>
        function _delete(buttonClass, itemName, confirmFirst = false) 
        {
            console.log("buttonClass, itemName, confirmFirst" + buttonClass + itemName + confirmFirst);
            
            $(document).on('click', '.btn-delete-'+buttonClass, function (e) {
                e.preventDefault();
                var button = $(this);
                var row = button.closest('tr');
                var itemId = button.data('id');
                var itemUrl = button.data('url');
                /// Ajax Request for Delete Slider
                $.ajax({
                    url: itemUrl,
                    type:'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                    },
                    beforeSend: function (){
                        button.attr('disabled', true)
                        if(confirmFirst == true){
                            return confirm("Are you sure? all the related data to the "+itemName+" will be permanently deleted");
                        }
                    },
                    success: function (response){
                        row.remove();
                        if(response.type === 'success'){
                            toastr.success(response.message)
                        }else{
                            toastr.error(response.message)
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError){
                        console.error('error called on ajax request of Delete ' + itemName)
                        console.error(xhr.status)
                        button.attr('disabled', false)
                        toastr.error(xhr.responseJSON.message)
                    }
                });
            })
        }

        function _restore(buttonClass, itemName, confirmFirst = false)
        {
            $(document).on('click', '.btn-rollback-'+buttonClass, function () {
                var button = $(this);
                var row = button.closest('tr');
                var itemUrl = button.data('url')
                /// Ajax Request for Rollback Poetry
                $.ajax({
                url:itemUrl,
                type:'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                beforeSend: function (){
                    /// do domthing
                    button.attr('disabled', true)
                    if(confirmFirst == true){
                        return confirm("Are you sure? all the related data to the "+itemName+" will be restored");
                    }
                },
                success: function (response){
                    if(response.type === 'success'){
                    toastr.success(response.message)
                    }else{
                    toastr.error(response.message)
                    }
                    row.remove()
                },
                error: function (xhr, ajaxOptions, thrownError){
                    button.attr('disabled', false)
                    console.error('error called on ajax request of Rollback '+itemName)
                    console.error(xhr.status)
                    console.error(thrownError)
                    toastr.error(ajaxOptions.message + '<br>' + xhr.status)
                }
                });
            })
        }

        // change visibility
        function _featured(buttonClass, itemName, confirmFirst = false) {
            $(document).on('click', '.btn-featured-'+buttonClass, function () {
                var button = $(this);
                var row = button.closest('tr');
                var itemId = button.data('id');
                var itemUrl = button.data('url');

                /// Ajax Request for Rollback Poetry
                $.ajax({
                    url : itemUrl,
                    type: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                    },
                    beforeSend: function (){
                        /// do domthing
                        if(confirmFirst == true){
                            return confirm("Are you sure? all the related data to the "+itemName+" will be Featured");
                        }
                        button.attr('disabled', true)
                        
                    },
                    success: function (response){
                        if(response.featured === 1){
                            button.attr('title', 'Hide From Featured')
                            button.html('<i class="fa fa-star text-warning"></i>')
                        }else{
                            button.attr('title', 'Show From Featured')
                            button.html('<i class="fa fa-star"></i>')
                        }
                        if(response.type === 'success'){
                            toastr.success(response.message)
                        }else{
                            toastr.error(response.message)
                        }
                        button.attr('disabled', false)
                    },
                    error: function (xhr, ajaxOptions, thrownError){
                        button.attr('disabled', false)
                        console.error('error called on ajax request of Featured '+itemName)
                        console.error(xhr.status)
                        console.error(thrownError)
                        toastr.error(ajaxOptions.message + '<br>' + xhr.status)
                    }
                });
            })
        }

        var Sindhi = ['ا','ب','ٻ','پ','ڀ','ت','ٺ','ٽ','ث','ٿ','ف','ڦ','گ','ڳ','ڱ','ک','ي','د','ذ','ڌ','ڏ','ڊ','ڍ','ح','ج','ڄ','ڃ','چ','ڇ','خ','ع','غ','ر','ڙ','م','ن','ل','س','ش','و','ق','ص','ض','ڻ','ط','ظ','ھ','جھ','گھ','ڪ','ء','ه','آ'];

        for(var index=0;index<Sindhi.length;index++)
        {
            $('p:contains('+Sindhi[index]+')').addClass("sd");
            $('h1:contains('+Sindhi[index]+')').addClass("sd");
            $('h2:contains('+Sindhi[index]+')').addClass("sd");
            $('h3:contains('+Sindhi[index]+')').addClass("sd");
            $('h4:contains('+Sindhi[index]+')').addClass("sd");
            $('span:contains('+Sindhi[index]+')').addClass("sd");
            $('a:contains('+Sindhi[index]+')').addClass( "sd" );
            $('li:contains('+Sindhi[index]+')').addClass( "sd" );
            $('legend:contains('+Sindhi[index]+')').addClass("sd");
            $('label:contains('+Sindhi[index]+')').addClass("sd");
            $('button:contains('+Sindhi[index]+')').addClass("sd");
            $( "input[placeholder*='"+Sindhi[index]+"']" ).addClass("sd text-right");
            $( "input[value*='"+Sindhi[index]+"']" ).addClass("sd text-right");
            $('select:contains('+Sindhi[index]+')').addClass("sd");
            $('textarea:contains('+Sindhi[index]+')').addClass( "sd text-right" );
            $('td:contains('+Sindhi[index]+')').addClass("sd");
            $('strong:contains('+Sindhi[index]+')').addClass("sd");			
        }

        // select2 dynamic
        function _select2Dynamic(inputFieldClass) {
            const select2Route = $('.'+inputFieldClass).data('ajax-path');
            const _idAttribute = $('.'+inputFieldClass).data('option-value');
            const _textAttribute = $('.'+inputFieldClass).data('option-text');

            
            $("."+inputFieldClass).select2({
                placeholder: 'Select a Poet',
                ajax : {
                    url : select2Route,
                    type: 'post',
                    dataType: 'json',
                    delay: 250,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                    },
                    data : function (params) {
                        return {
                            term: params.term, // Search term entered by the user
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) { 
                        if(data.message)
                        {
                            alert(data.message)
                        }else{

                            return {
                                results: data
                            };
                        }
                    },
                    cache: true,
                },
                minimumInputLength: 2,
            });
        }
    </script>
@stop
