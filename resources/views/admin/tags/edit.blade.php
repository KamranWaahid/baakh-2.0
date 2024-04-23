@extends('adminlte::page')

@section('title', 'Update Tags')

@section('content_header')
    <h1 class="m-0 text-dark">
        <a href="{{ route('admin.tags.index') }}" class="btn"><i class="fa fa-chevron-left"></i></a>
        Update Tag <i>{{ $data['0']->tag }}</i>
    </h1>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@stop

@section('content')
    <div class="row">
        <div class="col-8 m-auto">
            <div class="card">
                <div class="card-body">
                    <!-- ========== Start Create Form ========== -->
                    <form action="{{ route('admin.tags.update') }}" method="post">
                    @csrf
                    @method('put')
                        <div class="row">                                               
                            <div class="form-group col-6">
                                <label for="type">Tag Type</label>
                                <select name="type" id="type" class="form-control">
                                    @foreach ($types as $item)
                                        <option value="{{ $item }}" @if ($data[0]->type == $item)
                                            selected
                                        @endif>{{ Str::ucfirst($item); }}</option>
                                    @endforeach
                                </select>
                            </div>
                
                
                            <div class="form-group col-6">
                                <label for="tag_slug">Tag Slug</label>
                                <input type="text" name="slug" value="{{ old('slug', $data[0]->slug) }}" class="form-control" autocomplete="off">
                                @error('slug')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
 
                            
                        </div>
                        
                        @foreach ($data as $k => $item)
                        <div class="dynamic_data row">
                            <input type="hidden" name="id[]" value="{{ $item->id }}">
                            <div class="form-group col-10">
                                <label for="TagTitle">Tag Title</label>
                                <input type="text" name="tag[]" value="{{ $item->tag }}" class="form-control" autocomplete="off">
                                @error('tag')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                
                
                            
                            <div class="form-group col-2">
                                <label for="lang">Language</label>
                                <input type="text" disabled id="lang_title{{ $item->lang }}" class="form-control" value="{{ $item->language->lang_title }}">
                                <input type="hidden" name="lang[]" id="lang_{{ $item->lang }}" value="{{ $item->lang }}">
                            </div>
                        </div>
                        @endforeach

                        <div class="col-12">
                            <button type="submit" class="btn btn-block btn-primary" name="submit"><i class="fa fa-save"></i></button>
                        </div>
                    </form>
                    <!-- ========== End Create Form ========== -->
                </div>
            </div>
        </div>
    </div>
@stop

@section('plugins.toastr', true)

@section('js')
<script>
    $(function () {
       
    })
</script>
@endsection