@extends('adminlte::page')

@section('title', 'Edit Poetry')

@section('content_header')
    <h1 class="m-0 text-dark">Poetry</h1>
@endsection


@section('plugins.select2', true)


@section('content')
<!-- ========== Start except main body ========== -->
{{-- With multiple slots, and plugin config parameter --}}
@php
$config = [
    "placeholder" => "Select multiple options...",
    "allowClear" => true,
];
@endphp
 

<!-- ========== End except main body ========== -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('admin.poetry.update', $poetry->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-header">
                        <h3 class="card-title">Edit Poetry</h3>
                    </div>
                    <div class="card-body">

                        <!-- ========== Start new settings ========== -->
                        <div class="row" id="rowmain">
                            <!-- ========== Start right row ========== -->
                            <div class="col-6" id="right_side_row">

                                {{-- .poetry_title --}}
                                <div class="form-group poetry_title">
                                    <label for="poetry_title">Poetry Title</label>
                                    <input type="text" class="form-control  @error('poetry_title') is-invalid @enderror" value="{{ old('poetry_title') ?? $poetry->poetry_title }}"  name="poetry_title" id="poetry_title" placeholder="Enter Poetry Title">
                                        
                                        @error('poetry_title')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                </div>{{-- .poetry_title --}}

                                {{-- .poetry_slug --}}
                                <div class="form-group poetry_slug">
                                    <label for="poetry_slug">Poetry Slug</label>
                                    <button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="poetry_title" data-roman-field="poetry_slug" class="btn btn-xs btn-warning btn-roman-convert float-right"  id="btn-roman-convert"><i class="fa fa-plus mr-1"></i> Generate Roman</button>
                                    <input type="text" class="form-control  @error('poetry_slug') is-invalid @enderror" value="{{ old('poetry_slug') ?? $poetry->poetry_slug  }}"  name="poetry_slug" id="poetry_slug" placeholder="Enter Poetry's Slug">
                                    
                                    @error('poetry_slug')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>{{-- .poetry_slug --}}

                                <div class="row" id="PoetIdRow">
                                    <div class="col-6" id="poetId">
                                        <x-adminlte-select2 id="poet_id" name="poet_id"  label="Poets" igroup-size="sm">
                                            @foreach ($poets as $item)
                                                <option value="{{ $item->id }}" @if (old('poet_id', $poetry->poet_id) == $item->id) 
                                                    selected 
                                                @endif>{{ $item->details->poet_laqab }}</option>
                                            @endforeach
                                        </x-adminlte-select2>
                                    </div>
    
                                    <div class="col-6" id="SelectLanguages">
                                        {{-- With multiple slots, and plugin config parameter --}}
                                        <x-adminlte-select2 id="lang" name="lang" label="lang"
                                        label-class="text-info" igroup-size="sm" :config="$config">
                                            <x-slot name="prependSlot">
                                                <div class="input-group-text bg-gradient-blue">
                                                    <i class="fas fa-language"></i>
                                                </div>
                                            </x-slot>
                                            
                                            @foreach ($languages as $item)
                                            <option value="{{ $item->lang_code }}" @if (old('lang', $poetry->lang) == $item->lang_code) 
                                                selected 
                                            @endif>{{ $item->lang_title }}</option>
                                        @endforeach
                                        
                                        </x-adminlte-select2>
                                    </div>
                                </div>

                                <div class="row" id="categoryRow">
                                    
                                    <div class="col-6">
                                        <x-adminlte-select2 id="category_id" name="category_id" label="Categories" igroup-size="sm">
                                            <option value="">Select Category</option>                                    
                                            @foreach ($categories as $item)
                                                <option value="{{ $item->id }}" @if (old('category_id', $poetry->category_id) == $item->id) 
                                                    selected 
                                                @endif>{{ $item->detail->cat_name }}</option>
                                            @endforeach
                                        </x-adminlte-select2>
                                    </div>

                                    <div class="col-6">
                                        <label for="content_style">Content Style</label>
                                        <select name="content_style" id="content_style" class="form-control">
                                            <option>Select Style</option>                                    
                                            @foreach ($content_styles as $item)
                                                <option value="{{ $item }}" @if (old('content_style', $poetry->content_style) == $item) 
                                                    selected 
                                                @endif>{{ Str::ucfirst($item) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                </div>
  
                                {{-- Tags field --}}
                                <x-adminlte-select2 id="tags"  name="poetry_tags[]" label="Tags" 
                                label-class="text-danger" igroup-size="sm" :config="$config" multiple>
                                <x-slot name="prependSlot">
                                    <div class="input-group-text bg-gradient-red">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                </x-slot>
                                @php
                                    $selectedTags = old('poetry_tags', json_decode($poetry->poetry_tags) ?: []);
                                @endphp
                                    @foreach ($tags as $item)
                                        <option value="{{ $item->slug }}" @if (in_array($item->slug, $selectedTags)) 
                                            selected 
                                        @endif>{{ $item->tag }}</option>
                                    @endforeach
                                    
                                </x-adminlte-select2>

                                
                            </div>
                            <!-- ========== End right row ========== -->

                            <!-- ========== Start left Row ========== -->
                            <div class="col-6" id="left_side_row">
                                <div class="form-group ">
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
                                <div class="form-group">
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
                        </div>
                        <!-- ========== End new settings ========== -->
 
                            

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
                <input type="text" class="form-control" value="{{ old('couplet_slug.' . $loop->index) ?? $couplet->couplet_slug }}" name="couplet_slug[]" id="couplet_slug_{{ $itemCounter }}" placeholder="Couplet Slug">
            </div>

            <div class="form-group col-12">
                <label for="couplet_text_{{ $itemCounter }}">Details</label>
                <textarea class="textarea form-control" name="couplet_text[]" rows="5" id="couplet_text_{{ $itemCounter }}" placeholder="Insert description...">{{ old('couplet_text.' . $loop->index) ?: $couplet->couplet_text }}</textarea>
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
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
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
        $(document).on('change', '.changeLanguage', function() {
            var lang = $(this).val();
            var rowId = $(this).data('id');
            var deathPlace = $('#death_place_'+rowId);
            var birthPlace = $('#birth_place_'+rowId)
            
            birthPlace.empty().append($('<option>', {
                value: '',
                text: 'Select City'
            }));
            deathPlace.empty().append($('<option>', {
                value: '',
                text: 'Select City'
            }));

            if (lang) {
                $.get(b_url +'/getCitiesByLang/' + lang, function(data) {
                    $.each(data, function(key, value) {
                        deathPlace.append($('<option>', {
                            value: value.id,
                            text: value.city_name
                        }));
                    });
                    $.each(data, function(key, value) {
                        birthPlace.append($('<option>', {
                            value: value.id,
                            text: value.city_name
                        }));
                    });
                });
            }
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