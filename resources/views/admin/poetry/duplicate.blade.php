@extends('adminlte::page')

@section('title', 'Duplicate Poetry')

@section('content_header')
    <h1 class="m-0 text-dark">Duplicate Poetry</h1>
@endsection


@section('plugins.select2', true)


@section('content')
<!-- ========== Start except main body ========== -->
{{-- With multiple slots, and plugin config parameter --}}
@php
$config_tags = [
    "placeholder" => "Select multiple options...",
    "allowClear" => true,
];
@endphp

<!-- ========== End except main body ========== -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('admin.poetry.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">Duplicate Poetry</h3>
                    </div>
                    <div class="card-body">
                        <div class="row" id="rowmain">

                            {{-- .poetry_title --}}
                            <div class="form-group col-6 poetry_title">
                                <label for="poetry_title">Poetry Title</label>
                                <input type="text" class="form-control  @error('poetry_title') is-invalid @enderror" value="{{ old('poetry_title') ?: $poetry->poetry_title }}"  name="poetry_title" id="poetry_title" placeholder="Enter Poetry Title">
                                    
                                    @error('poetry_title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                            </div>{{-- .poetry_title --}}

                            {{-- .poetry_slug --}}
                            <div class="form-group col-6 poetry_slug">
                                <label for="poetry_slug">Poetry Slug</label>
                                <input type="text" class="form-control" disabled value="{{ $poetry->poetry_slug }}"  name="poetry_slug" id="poetry_slug" placeholder="Enter Poetry's Slug">
                                <input type="hidden" class="form-control" value="{{ $poetry->poetry_slug }}"  name="poetry_slug" id="poetry_slug" placeholder="Enter Poetry's Slug">
                                
                                @error('poetry_slug')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>{{-- .poetry_slug --}}


                        </div>

                        {{-- row #row1 --}}
                        <div class="row">
                            <div class="col-3">
                                <label for="poet_id">Poet</label>
                                <input type="text" class="form-control" disabled value="{{ $poetry->poet->details->poet_laqab }}">
                                <input type="hidden" class="form-control" name="poet_id" value="{{ $poetry->poet_id }}">
                                
                            </div>

                            <div class="col-3">
                                <label for="category_id">Category</label>
                                <input type="text" class="form-control" disabled value="{{ $poetry->category->detail->cat_name }}">
                                <input type="hidden"  name="category_id" value="{{ $poetry->category_id }}" >
                                <input type="hidden"  name="content_style" value="{{ $poetry->content_style }}" >
                                 
                            </div>

                            {{-- Tags field --}}
                            <div class="col-4">
                                <x-adminlte-select2 id="tags"  name="poetry_tags[]" label="Tags"
                                label-class="text-danger" igroup-size="sm" :config="$config_tags" multiple>
                                <x-slot name="prependSlot">
                                    <div class="input-group-text bg-gradient-red">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                </x-slot>
                                @php
                                    $selectedTags = old('poetry_tags', json_decode($poetry->poetry_tags) ?: []);
                                    $config = [
                                        "placeholder" => "Select Item",
                                        "allowClear" => true,
                                    ];
                                @endphp
                                    @foreach ($tags as $item)
                                        <option value="{{ $item->slug }}" @if (in_array($item->slug, $selectedTags)) 
                                            selected 
                                        @endif>{{ $item->tag }}</option>
                                    @endforeach
                                    
                                </x-adminlte-select2>
                            </div>

                            <div class="col-2">
                               
                                {{-- With multiple slots, and plugin config parameter --}}
                                <x-adminlte-select2 id="lang" name="lang" label="lang"
                                label-class="text-info" igroup-size="sm" :config="$config">
                                    <x-slot name="prependSlot">
                                        <div class="input-group-text bg-gradient-blue">
                                            <i class="fas fa-language"></i>
                                        </div>
                                    </x-slot>
                                    <option value="">Choose</option>
                                    @foreach ($languages as $item)
                                        <option value="{{ $item->lang_code }}">{{ $item->lang_title }}</option>
                                    @endforeach
                                
                                </x-adminlte-select2>
                            </div>

                        </div> {{-- /.row1 --}}

                        <div class="row">
                            <div class="form-group col-6">
                                <label for="poetry_info">Detail</label>
                                <x-adminlte-textarea id="poetry_info" name="poetry_info" class="textarea-main" value="{{ old('poetry_info') }}" placeholder="Insert description...">
                                    {{ old('poetry_info') ?: $poetry->poetry_info }}
                                </x-adminlte-textarea>
                                
                                @error('poetry_info')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="form-group  col-6">
                                <label for="source">Source</label>
                                <x-adminlte-textarea name="source" class="textarea-main" value="{{ old('source') }}" placeholder="Insert description...">
                                    {{ old('source') ?: $poetry->source }}
                                </x-adminlte-textarea>
                                
                                @error('source')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <!-- ========== End left Row ========== -->

                             

                            <!-- ========== Start Other Section for Dynamic Values ========== -->
                            <div class="d-flex justify-content-between bg-secondary p-1 rounded">
                                <div class="info">
                                    <h3>Couplets</h3>    
                                    <small>Fill data as per selected language</small>
                                </div>
                                <button type="button" class="btn btn-default addMoreRow" onclick="addRow()"><i class="fa fa-plus"></i></button>
                            </div>
                            <div class="dynamic_details mt-2" id="dynamic_details">
                                {{-- loop existing couplets --}}
                                
                                @php
                                    $itemCounter = 1;
                                @endphp

                                @if ($poetry->all_couplets && $poetry->all_couplets->count() > 0)
                                    @foreach ($poetry->all_couplets as $couplet)
                                        <div class="row p-2 rounded mt-3" data-cusid="{{ $itemCounter }}" id="dyn_row{{ $itemCounter }}" style="background:#d1e9e4;">
                                            <div class="form-group col-12">
                                                <label for="couplet_slug_{{ $itemCounter }}">Couplet Slug</label>
                                                <button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="couplet_text_{{ $itemCounter }}" data-roman-field="couplet_slug_{{ $itemCounter }}" data-field-id="couplet_slug_{{ $itemCounter }}" class="btn btn-xs btn-warning float-right mr-2" id="generate_slug_button_{{ $itemCounter }}">
                                                    <i class="fas fa-sync-alt mr-1"></i> Generate Slug
                                                </button>
                                                <input type="text" class="form-control" value="{{ old('couplet_slug') ?: $couplet->couplet_slug }}" name="couplet_slug[]" id="couplet_slug_{{ $itemCounter }}" placeholder="Couplet Slug">
                                            </div>

                                            <div class="form-group col-12">
                                                <label for="couplet_text_{{ $itemCounter }}">Details</label>
                                                <button type="button" onclick="convertText(this)" data-is-slug="0" data-sindhi-field="couplet_text_{{ $itemCounter }}" data-roman-field="couplet_text_{{ $itemCounter }}" data-field-id="couplet_slug_{{ $itemCounter }}" class="btn btn-xs btn-warning float-right mr-2" id="generate_slug_button_{{ $itemCounter }}">
                                                    <i class="fas fa-sync-alt mr-1"></i> Generate Roman
                                                </button>
                                                <textarea class="textarea form-control" name="couplet_text[]" rows="5" id="couplet_text_{{ $itemCounter }}" placeholder="Insert description...">{{ old('couplet_text') ?: $couplet->couplet_text }}</textarea>
                                            </div>
                                            <button type="button" onclick="deleteRow({{ $itemCounter }})" data-row-id="{{ $itemCounter }}" class="btn btn-danger btn-block">
                                                <i class="fa fa-trash"></i> Delete This Information
                                            </button>
                                        </div>
                                        @php
                                            $itemCounter++;
                                        @endphp
                                    @endforeach
                                @endif
                            </div>
                            
                            <!-- ========== End Other Section for Dynamic Values ========== -->
 
                    </div>
                    <div class="p-3">
                        <button type="button" class="btn btn-secondary btn-block pt-3 pb-3 addMoreRow" onclick="addRow()"><i class="fa fa-plus"></i> Add More Couplets</button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" disabled class="btn btn-info btn-save-poetry"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('admin.poetry.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-bs4.min.css') }}">
@endsection

@section('plugins.toastr', true)

@section('js')
<script>
    var final_roman_dict_file = "{{ asset('vendor/roman-converter/all_words.dic') }}";
</script>
<script src="{{ asset('vendor/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/roman-converter/sindhi_to_roman.js') }}" charset="UTF-8"></script>
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>


<script>
    $(function () {
        $('.select2Tags').select2()
        $('.textarea-main').summernote()
        var b_url = "{{ url('/admin/') }}";
        // ajax for province
       // Update provinces select option based on selected country
        $(document).on('change', '#lang', function() {
            var lang = $(this).val();
            var slug = '{{ $poetry->poetry_slug }}';
            /// Ajax Request for check slug with language
            $.ajax({
                url:'{{ route('admin.poetry.check-slug') }}',
                type:'post',
                data:{lang: lang, slug: slug},
                headers: {
                    'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function (){
                    /// do domthing
                  $('button.btn-save-poetry').attr('disabled', true);  
                },
                success: function (response){
                    console.log('success called on ajax request of check slug with language')
                    
                    if(response.type == 'success'){
                        $('button.btn-save-poetry').attr('disabled', false);
                        toastr.success(response.message)

                    }else{
                        toastr.error(response.message)
                    }
                },
                error: function (xhr, ajaxOptions, thrownError){
                    console.error('error called on ajax request of check slug with language')
                    console.error(xhr.status)
                    console.error(thrownError)
                }
            });
        });
    });

    function new_row(number) {
        var html = '<div class="row p-2 rounded mt-3" data-cusid="'+number+'" id="dyn_row'+number+'" style="background:#d1e9e4;">'+
                        '<div class="form-group col-12">'+
                        '<label for="couplet_slug_'+number+'">Couplet Slug</label>'+
                        '<button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="couplet_text_'+number+'" data-roman-field="couplet_slug_'+number+'" data-field-id="couplet_slug_'+number+'" class="btn btn-xs btn-warning float-right mr-2" id="generate_slug_button_'+number+'"><i class="fas fa-sync-alt mr-1"></i> Generate Slug</button>'+
                        '<input type="text" class="form-control"  name="couplet_slug[]" id="couplet_slug_'+number+'" placeholder="Couplet Slug">'+
                        '</div>'+
                         
                        '<div class="form-group col-12"><label for="couplet_text_'+number+'">Details</label>'+
                        '<textarea class="textarea form-control" name="couplet_text[]" rows="5" id="couplet_text_'+number+'" placeholder="Insert description..."></textarea>'+
                        '</div><button type="button" onclick="deleteRow('+number+')" data-row-id="1" class="btn btn-danger btn-block"><i class="fa fa-trash"></i> Delete This Information</button></div>';

        return html;
        
    }

    function deleteRow(number) {
        var id = '#dyn_row'+number;
        console.log('removing id === ' + id);
        $(id).remove();
    }

    function addRow() {
        var divId = $('#dynamic_details');
        // append row
        var last_id = $('#dynamic_details > div:last-child').data('cusid');
        
        if(typeof last_id === 'undefined' || isNaN(last_id) || last_id === null){
            $(divId).append(new_row(1))
        }else{
            var new_id = last_id + 1;
            $(divId).append(new_row(new_id))
        }
    }
 
  </script>
@endsection