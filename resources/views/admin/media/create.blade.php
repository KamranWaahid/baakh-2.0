@extends('adminlte::page')

@section('title', 'Add Media - '.$poetry->poetry_title)

@section('content_header')
    <h1 class="m-0 text-dark">
        <a href="{{ route('admin.poetry.index') }}" class="btn"><i class="fa fa-chevron-left"></i></a>
        Add Media - {{ $poetry->poetry_title }}
    </h1>
@stop

@section('content')
    <div class="row">
        <!-- ========== Start Add Media File Section ========== -->
        <div class="col-5">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add New Media</h3>
                </div>
                <form action="{{ route('admin.media.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="card-body">
                        <input type="hidden" name="poetry_id" value="{{ $poetry->id }}">
                        <div class="form-group">
                            <label for="type">Media Type</label>
                            <select name="media_type" class="select2 form-control" id="media_type">
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                            </select>
                        </div>

                        @foreach ($languages as $item)
                            <div class="form-group" id="for_{{ $item->lang_code }}">
                                <label for="title">Media Title <i>{{ $item->lang_title }}</i></label>
                                <input type="hidden" name="lang[]" value="{{ $item->lang_code }}">
                                <input type="text" class="form-control @error('media_title') is-invalid @enderror" value="{{ old('media_title') }}" name="media_title[]" placeholder="Media Title / Singer Name">
                                @error('media_title')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        @endforeach
                        
                        <div class="form-group" id="media_url_yt">
                            <label for="url">Media Link</label>
                            <input type="text" class="form-control" name="media_url" placeholder="Media URL (youtube's video ID)">
                        </div>

                        <div class="form-group" style="display:none;" id="media_url_audio">
                            <x-adminlte-input-file name="audio" label="Upload file" placeholder="Choose a file..." />
                            @error('audio')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>


                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-success"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- ========== End Add Media File Section ========== -->

        <!-- ========== Start Media Files List ========== -->
        <div class="col-7">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Poetry's Media</h3>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>URL</th>
                                <th>Information</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                         
                            @foreach ($media as $item)
                            <tr>
                                <th>{{ $item->id }}</th>
                                <th>{{ $item->media_title }}</th>
                                <th>
                                    @if ($item->media_type == 'video')
                                        <a href="http://youtube.com/watch?v={{ $item->media_url }}" target="_blank" rel="noopener noreferrer">{{ $item->media_url }}</a>
                                    @else
                                        <a href="{{ url($item->media_url) }}" target="_blank" rel="noopener noreferrer"><i class="fa fa-file"></i></a>
                                    @endif
                                </th>
                                <th>
                                    
                                </th>
                                <th>
                                    <a href="{{ route('admin.media.edit', $item->poetry_id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                    <button type="button" data-id="{{ $item->id }}" data-url="{{ route('admin.media.delete', ['id' => $item->id]) }}" data-toggle="tooltip" data-placement="top" title="Permanent Delete Media" class="btn btn-xs btn-danger btn-delete-media"><i class="fa fa-trash"></i></button>
                                </th>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- ========== End Media Files List ========== -->
    </div>
@stop




@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection
@section('plugins.toastr', true)

@section('js')
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(function () {
        $('#media_type').on('change', function () {
            $('#media_url_yt').toggle();
            $('#media_url_audio').toggle();
        })

        
  $('[data-toggle="tooltip"]').tooltip();

    _delete('media', 'Media', true);
    })
</script>
@endsection


