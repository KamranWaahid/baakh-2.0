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
                </div>
                <div class="card-body">
                     
                </div>
            </div>
        </div>

        {{-- create form --}}
        <div class="col-5">
            <div class="card">
                <form action="{{ route('admin.categories.update', $category->id) }}" method="post">
                    <div class="card-header">
                        <h3 class="card-title">Create New Category</h3>
                    </div>
                    <div class="card-body">
                        @csrf
                        @method('put')

                        {{-- Name field --}}
                        <div class="form-group">
                            <label for="slug">Category Slug / URL</label>
                            <input type="text" class="form-control  @error('slug') is-invalid @enderror" value="{{ old('slug' , $category->slug) }}"  name="slug" id="slug" placeholder="Enter Category Slug">
                            
                            @error('slug')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="form-group col-8">
                                <label for="content_style">Content Style</label>
                                <select name="content_style" id="content_style" class="form-control">
                                    <option>Select Style</option>                                    
                                    @foreach ($content_styles as $item)
                                        <option value="{{ $item }}" @if ($category->content_style == $item)
                                            selected
                                        @endif>{{ Str::ucfirst($item) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-4">
                                <label for="is_featured">Is Featured?</label>
                                @php
                                    $switchConfig = [
                                        'state' => $category->is_featured == 1 ? true : false
                                    ];
                                @endphp
                                <x-adminlte-input-switch
                                    name="is_featured"
                                    data-on-color="success"
                                    data-on-text="YES"
                                    data-off-text="NO"
                                    data-off-color="danger"
                                    :config="$switchConfig"
                                />
                            </div>
                        </div>
                        
                        <!-- ========== Start Edit Form ========== -->
                        
   
                           <div class="dynamic_data" id="dynamic_data">
                            @php
                                $details = $category->details;
                            @endphp
                               @foreach ($languages as $i => $lang)
                                   <div class="row mt-1 rounded p-2" id="catrow_{{ $i }}" style="background: #9d9ddc2d">
                                       {{-- Name field --}}
                                       <div class="form-group col-12">
                                           <label for="cat_name">Name</label>
                                           <span class="float-right"><strong>{{ $lang->lang_title }}</strong></span>
                                           
                                           <input type="text" 
                                                class="form-control  @error('cat_name') is-invalid @enderror" 
                                                name="cat_name[]" 
                                                id="cat_name_{{ $i }}" 
                                                placeholder="Enter Category Name">
                                           
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
                                           <x-adminlte-textarea name="cat_detail[]" placeholder="Insert description...">
                                           </x-adminlte-textarea>
                                           
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
                        <!-- ========== End Edit Form ========== -->

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
    });
  </script>
@endsection