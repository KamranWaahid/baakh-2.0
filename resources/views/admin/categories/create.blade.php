@extends('adminlte::page')

@section('title', 'Add Category')

@section('content_header')
    <h1 class="m-0 text-dark">Add Category</h1>
@endsection


@section('plugins.bootstrapSwitch', true)



@section('content')
    <div class="row">
        <div class="col-8 m-auto">
            <div class="card">
                <form action="{{ route('admin.categories.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">Category</h3>
                        <div class="float-right">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-info mr-1" ><i class="fa fa-list mr-1"></i> Categories lists</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="slug">Category Slug / URL</label>
                            <input type="text" class="form-control  @error('slug') is-invalid @enderror" value="{{ old('slug') }}"  name="slug" id="slug" placeholder="Enter Category Slug">
                            
                            @error('slug')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="form-group col-6">
                                <label for="content_style">Content Style</label>
                                <select name="content_style" id="content_style" class="form-control @error('content_style') is-invalid @enderror">
                                    <option value="">Select Style</option>                                    
                                    @foreach ($content_styles as $item)
                                        <option value="{{ $item }}">{{ Str::ucfirst($item) }}</option>
                                    @endforeach
                                </select>
                                @error('content_style')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <!--= start[Gender] =-->
                            <div class="form-group col-4">
                                <label for="gender">Gender</label>
                                    <select name="gender" id="gender" class="form-control select2 @error('gender') is-invalid @enderror">
                                        <option value="masculine">Masculine / مذڪر</option>
                                        <option value="feminine">Feminine / مونث</option>
                                    </select>
                            
                                @error('gender')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <!--= end[Gender] =-->

                            <div class="form-group col-2">
                                <label for="is_featured">Is Featured?</label>
                                <x-adminlte-input-switch name="is_featured" data-on-color="success" data-on-text="YES" data-off-text="NO" data-off-color="danger"/>
                            </div>
                        </div>

                        <!---start[Dynamic Data]--->
                        <div class="dynamic_data" id="dynamic_data">
                            
                            @foreach ($languages as $i => $lang)
                                <div class="row mt-1 rounded p-2" id="catrow_{{ $i }}" style="background: #9d9ddc2d">
                                    {{-- Name field --}}
                                    <div class="form-group col-12">
                                        <label for="cat_name">Name</label>
                                        <span class="float-right"><strong>{{ $lang->lang_title }}</strong></span>
                                        <input type="text" class="form-control  @error('cat_name' . $i) is-invalid @enderror" value="{{ old('cat_name')[$i] ?? ''}}"  name="cat_name[]" id="cat_name_{{ $i }}" placeholder="Enter Category Name">
                                        
                                        @error('cat_name' . $i)
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <!--= start[Category Plural] =-->
                                    <div class="form-group col-12">
                                        <label for="cat_name_plural_{{ $i }}">Category Plural</label>
                                        <input type="text" name="cat_name_plural[]" class="form-control @error('cat_name_plural' . $i) is-invalid @enderror" id="cat_name_plural_{{ $i }}" value="{{ old('cat_name_plural')[$i] ?? '' }}"  placeholder="Insert Category Plural">
                                    
                                        @error('cat_name_plural' . $i)
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <!--= end[Category Plural] =-->
 
                                    <input type="hidden" name="lang[]" class="form-control" value="{{ $lang->lang_code }}">

                                    {{-- details --}}
                                    <div class="form-group col-12">
                                        <label for="cat_detail">Details</label>
                                        <x-adminlte-textarea name="cat_detail[]" placeholder="Insert description..."/>
                                        
                                        @error('cat_detail')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <button type="button" class="btn btn-block btn-sm btn-danger mr-3 ml-3" onclick="removeRow({{ $i }})" ><i class="fa fa-trash mr-2"></i>Delete this row</button>
                                </div>
                            @endforeach {{-- $foreach languages to display divs --}}
                        </div>
                        <!---end[Dynamic Data]--->


                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('admin.doodles.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection



@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection



@section('js')
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>

@endsection