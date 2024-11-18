@extends('adminlte::page')

@section('title', 'SQLite Settings')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Settings</h1>
        <div class="breadcrumbs">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">SQLite Settings</li>
            </ol>
        </div>
    </div>
@stop

@section('css')
    <style>
        .setting-module .icon-container{
            width: 100%;
            height: 50px;
            justify-content: center;
            align-content: center;
            align-items: center;
            display: flex;
            font-size: 1.5rem;
            border-radius: 5px;
            background: #f6f8fb;
            color: #929fb1;
        }

        .setting-module .title {
            font-weight: 700;
        }
        .setting-module .desc {
            color: #929fb1;
        }

        /* Full-page overlay */
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(114, 110, 110, 0.5); /* Semi-transparent black */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Make sure it's on top of everything else */
        }

      

    </style>
@endsection

@section('content')
<!-- Overlay with Spinner -->
<div id="overlay" class="overlay d-none">
    <div class="spinner-border text-default" role="status">
      <span class="sr-only">Loading...</span>
    </div>
</div>
  
    <div class="card">
        <div class="card-header">Common</div>
        <div class="card-body">
            <div class="row">
                {{-- Sync DB --}}
                <div class="setting-module col-md-4 col-12">
                    <div class="row mb-2">
                        <div class="col-2">
                            <div class="icon-container"><i class="fa fa-sync icon"></i></div>
                        </div>
                        <div class="col-10">
                            <a href="avascript:void(0)" data-method="get" data-route="{{ route('admin.sqlite.sync') }}" class="title btn-sqlite-table">Sync Database</a>
                            <p class="desc">This will watch new records and Sync to sqlite, can take upto 6 minutes</p>
                        </div>
                    </div>
                </div>

                

            </div>
        </div>
    </div>

    <div class="card re-create">
        <div class="card-header">Recreating Database Tables</div>
        <div class="card-body">
            <div class="row">
                {{-- Recreate Poetry --}}
                <div class="setting-module col-md-4 col-12">
                    <div class="row mb-2">
                        <div class="col-2">
                            <div class="icon-container"><i class="fa fa-align-justify icon"></i></div>
                        </div>
                        <div class="col-10">
                            <a href="avascript:void(0)" data-method="get" data-route="{{ route('admin.sqlite.gen-table', 'poetry') }}" class="title btn-sqlite-table">Re-create Poetry</a>
                            <p class="desc">Recreating will drop previous table in SQLite and re-create new with fresh data</p>
                        </div>
                    </div>
                </div>

                {{-- Recreate Couplets --}}
                <div class="setting-module col-md-4 col-12">
                    <div class="row mb-2">
                        <div class="col-2">
                            <div class="icon-container"><i class="fa fa-indent icon"></i></div>
                        </div>
                        <div class="col-10">
                            <a href="avascript:void(0)" data-method="get" data-route="{{ route('admin.sqlite.gen-table', 'couplets') }}" class="title btn-sqlite-table">Re-create Couplets</a>
                            <p class="desc">Recreating will drop previous table in SQLite and re-create new with fresh data</p>
                        </div>
                    </div>
                </div>

                {{-- Recreate Poets --}}
                <div class="setting-module col-md-4 col-12">
                    <div class="row mb-2">
                        <div class="col-2">
                            <div class="icon-container"><i class="fa fa-users icon"></i></div>
                        </div>
                        <div class="col-10">
                            <a href="avascript:void(0)" data-method="get" data-route="{{ route('admin.sqlite.gen-table', 'poets') }}" class="title btn-sqlite-table">Re-create Poets</a>
                            <p class="desc">Recreating will drop previous table in SQLite and re-create new with fresh data</p>
                        </div>
                    </div>
                </div>

                {{-- Recreate Tags --}}
                <div class="setting-module col-md-4 col-12">
                    <div class="row mb-2">
                        <div class="col-2">
                            <div class="icon-container"><i class="fa fa-tags icon"></i></div>
                        </div>
                        <div class="col-10">
                            <a href="avascript:void(0)" data-method="get" data-route="{{ route('admin.sqlite.gen-table', 'tags') }}" class="title btn-sqlite-table">Re-create Tags</a>
                            <p class="desc">Recreating will drop previous table in SQLite and re-create new with fresh data</p>
                        </div>
                    </div>
                </div>

                {{-- Recreate Categories --}}
                <div class="setting-module col-md-4 col-12">
                    <div class="row mb-2">
                        <div class="col-2">
                            <div class="icon-container"><i class="fa fa-folder icon"></i></div>
                        </div>
                        <div class="col-10">
                            <a href="avascript:void(0)" data-method="get" data-route="{{ route('admin.sqlite.gen-table', 'categories') }}" class="title btn-sqlite-table">Re-create Categories</a>
                            <p class="desc">Recreating will drop previous table in SQLite and re-create new with fresh data</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
 
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script>
        $(function() {
            $(document).on('click', '.btn-sqlite-table', function () {
                var button = $(this);
                var route = $(this).data('route');
                var method = $(this).data('method');
                var _data = $(this).data('form-data') ?? '';
                $('#overlay').removeClass('d-none');
                button.attr('diabled', true);
                /// Ajax Request for SQLite
                $.ajax({
                    url:route,
                    type:method,
                    data:{_data},
                    headers: {
                        'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function (){
                        
                        console.log('beforeSend called on ajax request of SQLite');
                    },
                    success: function (res){
                        console.log('Success called on ajax request of SQLite');

                        if(res.error === true) {
                            toastr.error(res.message)
                        }else{
                            toastr.success(res.message)
                        }
                        button.attr('diabled', false);
                    },
                    error: function (xhr, ajaxOptions, thrownError){
                        console.error('error called on ajax request of SQLite')
                        console.error(xhr.status)
                        console.error(thrownError)
                        button.attr('diabled', false);
                    },
                    complete: function () {
                        $('#overlay').addClass('d-none');
                    }
                });
            })       
        })
    </script>
@stop