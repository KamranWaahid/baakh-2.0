@extends('adminlte::page')

@section('title', 'Add New Poetry')

@section('content_header')
    <h1 class="m-0 text-dark">Poetry</h1>
@endsection


@section('plugins.select2', true)


@section('content')
<!-- ========== Start except main body ========== -->
<div class="col-4 d-none" id="all_tags_div">
     
</div>
  {{-- With multiple slots, and plugin config parameter --}}
  @php
  $config = [
      "placeholder" => "Select multiple options...",
      "allowClear" => true,
  ];
  @endphp
<!-- ========== End except main body ========== -->
<form action="{{ route('admin.poetry.update', $poetry) }}" id="poetry_update_form" method="post" enctype="multipart/form-data">
    @csrf
    @method('put')
    <div class="row">
        
        <div class="col-lg-8 col-12 m-auto overlay-wrapper" id="main_col_9">
            <!--= start[Main Information Card Overly] =-->
                <div class="overlay main-row-overly" style="display: none;">
                    <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                    <div class="text-bold pt-2 sr-only">Loading...</div>
                </div>
            <!--= end[Main Information Card Overly] =-->
            <div class="card" id="main_info_div">
                    <div class="card-header">
                        <h3 class="card-title">Poetry</h3>
                    </div>
                    <div class="card-body">
                        

                        {{-- .poetry_slug --}}
                        <div class="form-group poetry_slug">
                            <label for="poetry_slug">Poetry Slug</label>
                            <input type="text" data-prev-id="{{ $poetry->id }}" class="form-control  @error('poetry_slug') is-invalid @enderror" value="{{ old('poetry_slug', $poetry->poetry_slug) }}"  name="poetry_slug" id="poetry_slug" placeholder="Enter Poetry's Slug">
                            
                            @error('poetry_slug')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>{{-- .poetry_slug --}}
 
                        <!---start[Status of Poetry]--->
                    <div class="form-group d-flex justify-content-between">
                        <input type="checkbox" name="is_featured" id="statusSwitchis_featured" data-bootstrap-switch data-on-text="Featured" data-off-text="unFeatured" data-on-color="success" data-off-color="warning" value="1" checked>
                        <input type="checkbox" name="is_visible" id="statusSwitchis_visible" data-bootstrap-switch data-on-text="Online" data-off-text="Offline" data-on-color="success" data-off-color="danger" value="1" checked>
                        @error('is_featured')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        @error('is_visible')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <!---end[Status of Poetry]--->

                    <!---start[Select Poet]--->
                    <div class="mt-2">
                  
                        <!--= start[Poets] =-->
                        <div class="form-group">
                            <label for="poet_id">Poets</label>
                                <select name="poet_id" id="poet_id" class="form-control select2 @error('poet_id') is-invalid @enderror">
                                    <option value="0">Select Poets</option>
                                    @foreach ($poets as $item)
                                        <option value="{{ $item->id }}" @selected($item->id == $poetry->poet_id)>{{ $item->shortDetail->poet_laqab }}</option>
                                    @endforeach
                                </select>
                        
                            @error('poet_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <!--= end[Poets] =-->
                    </div>
                    <!---end[Select Poet]--->

                    <!---start[Select Category]--->
                    <div class="mt-2">
                     
                        <!--= start[Category] =-->
                        <div class="form-group">
                            <label for="category_id">Category</label>
                                <select name="category_id" id="category_id" class="form-control select2 @error('category_id') is-invalid @enderror" required>
                                    <option value="">Select Category</option>
                                    @foreach ($categories as $item)
                                        <option value="{{ $item->id }}" @selected($item->id == $poetry->category_id) data-cstyle="{{ $item->content_style }}">{{ $item->shortDetail->cat_name }}</option>
                                    @endforeach
                                </select>
                        
                            @error('category_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <!--= end[Category] =-->
                    </div>
                    <!---end[Select Category]--->

                    <!---start[Content Style]--->
                    <div class="mt-2">
                        <label for="content_style">Content Style</label>
                        <select name="content_style" id="content_style" class="form-control">
                            <option value="">Select Style</option>                   
                            @foreach ($content_styles as $item)
                                <option value="{{ $item }}" @selected($item == $poetry->content_style)>{{ Str::ucfirst($item) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!---end[Content Style]--->

                    

                    <!---start[Select Tags]--->
                    <div class="mt-2">
                        <x-adminlte-select2 id="tags"  name="poetry_tags[]" label="Tags"  label-class="text-danger" igroup-size="sm" :config="$config" multiple>
                            <x-slot name="prependSlot">
                                <div class="input-group-text bg-gradient-red">
                                    <i class="fas fa-tag"></i>
                                </div>
                            </x-slot>
                            @php
                                $old_tags = json_decode($poetry->poetry_tags, JSON_UNESCAPED_UNICODE);
                            @endphp    
                            @foreach ($tags as $item)
                                <option value="{{ $item['slug'] }}" @selected($old_tags && in_array($item['slug'], $old_tags))>{{ $item['tag'] }}</option>
                            @endforeach
                            
                        </x-adminlte-select2>
                    </div>
                    <!---end[Select Tags]--->

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">Update<i class="fa fa-save ml-2"></i></button>
                    </div>
                
            </div>
        </div>

        
    </div>
</form>
@endsection


@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-bs4.min.css') }}">
@endsection

@section('plugins.bootstrapSwitch', true)
@section('plugins.jqueryValidation', true)

@section('js')
 

<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>

<script>
    $(function () {
        var b_url = "{{ url('/admin/') }}";
        
        $(function(){
            $("[name='is_visible']").bootstrapSwitch();
            $("[name='is_featured']").bootstrapSwitch();
            $('#category_id').select2();
            $('#poet_id').select2();

        
        })

        /**
         * Dynamic Select2 for Poets and Tags 
        **/
        // _select2Dynamic('select2-poets') // poets
        
        // change category and set poetry style
        $(document).on('change', '#category_id', function () {
            var cat_style = $(this).find(':selected').data('cstyle');
            $('#content_style option[value="' + cat_style + '"]').prop('selected', true);
        });
 
        
        
    });

</script>
 


<script>
 


// Update provinces select option based on selected country
$(document).on('focusout', 'input[name="poetry_slug"]', function() {
    var poetrySlug = $(this).val();
    var prevId = $(this).data('prev-id');
    /// Ajax Request for check slug with language
    $.ajax({
        url:'{{ route('admin.poetry.check-slug') }}',
        type:'post',
        data: {
            slug: poetrySlug,
            previous_id: prevId
        },
        headers: {
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        },
        success: function (response){
            console.log('success called on ajax request of check slug with language')
            
            if(response.type == 'success'){
                // set form invalid
                toastr.success(response.message)

            }else{
                toastr.error(response.message)
                $(this).trigger('invalid');
            }
        },
        error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of check slug with language')
            console.error(xhr.status)
            console.error(thrownError)
        }
    });
});
 
</script>


@endsection