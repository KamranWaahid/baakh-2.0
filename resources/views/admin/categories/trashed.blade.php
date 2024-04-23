@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Categories</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Categories</h3>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-list"></i> View Available</a>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Name</th>
                           <th>Information</th>
                           <th>Detail</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Name</th>
                             <th>Information</th>
                             <th>Languages</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                            @foreach ($categories as $data)
                                    
                            <tr>
                                <td>{{ $data->id }}</td>
                                
                                <td>{{ $data->cat_name }}</td>
                                <td><span class="badge @if ($data->is_featured) bg-warning @else bg-info @endif rounded"><i class="fa fa-star"></i></span></td>
                                    
                                <td>
                                    @foreach ($data->languages as $langCode => $langTitle)
                                        <span class="badge bg-success p-1 rounded"><i class="fa fa-globe mr-1"></i>{{ $langTitle }}</span>
                                    @endforeach
                                </td>
                                
                                <td width="12%" class="text-center">
                                    <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.categories.restore', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Rollback Category" class="btn btn-xs btn-info btn-rollback-category"><i class="fa fa-undo"></i></button>
                                    <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.categories.hard-delete', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Category" class="btn btn-xs btn-danger btn-delete-category"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                           @endforeach
                         </tbody>
                     </table>
                </div>
            </div>
        </div>

        {{-- create form --}}
        <div class="col-5">
            <div class="card">
                <form action="{{ route('admin.categories.store') }}" method="post">
                    <div class="card-header">
                        <h3 class="card-title">Create New Category</h3>
                    </div>
                    <div class="card-body">
                        @csrf
                        
                       <div class="row" id="rowForBasicInfo">
                         {{-- Name field --}}
                         <div class="form-group col-9">
                            <label for="slug">Category Slug / URL</label>
                            <input type="text" class="form-control  @error('slug') is-invalid @enderror" value="{{ old('slug') }}"  name="slug" id="slug" placeholder="Enter Category Slug">
                            
                            @error('slug')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group col-3">
                            <label for="is_featured">Is Featured?</label>
                            <x-adminlte-input-switch name="is_featured" data-on-color="success" data-on-text="YES" data-off-text="NO" data-off-color="danger"/>
                        </div>

                       </div>
                     
                        <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

                        <div class="dynamic_data" id="dynamic_data">
                            
                            @foreach ($languages as $i => $lang)
                                <div class="row mt-1 rounded p-2" id="catrow_{{ $i }}" style="background: #9d9ddc2d">
                                    {{-- Name field --}}
                                    <div class="form-group col-12">
                                        <label for="cat_name">Name</label>
                                        <span class="float-right"><strong>{{ $lang->lang_title }}</strong></span>
                                        <input type="text" class="form-control  @error('cat_name') is-invalid @enderror" value="{{ old('cat_name') }}"  name="cat_name[]" id="cat_name_{{ $i }}" placeholder="Enter Category Name">
                                        
                                        @error('cat_name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
 
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
                        



                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('plugins.Datatables', true)

@section('js')
<script src="{{ asset('vendor/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
<script>
    $(function () {
      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });
      _delete('category', 'Category', true);
      _restore('category', 'Category');
    });

    function removeRow(id) {
        $('#catrow_'+id).remove();
    }

  </script>
@endsection