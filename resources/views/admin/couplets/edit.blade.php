@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Couplets</h1>
@endsection

@section('plugins.bootstrapSwitch', true)


@section('content')

@php
$config = [
    "placeholder" => "Select Item",
    "allowClear" => true,
];
@endphp


    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('admin.couplets.update', $couplet->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="card-header">
                        <h3 class="card-title">Create New Couplet</h3>
                    </div>
                    <div class="card-body">
                        {{-- Row --}}
                        <div class="row">
                            {{-- col-6 for Information --}}
                            <div class="col-6">
                                {{-- Name field --}}
                                <div class="form-group">
                                    <label for="link_url">Couplet Slug</label>
                                    <button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="couplet_text" data-roman-field="couplet_slug" class="btn btn-xs btn-warning btn-roman-convert float-right"  id="btn-roman-convert"><i class="fa fa-plus mr-1"></i> Generate Slug</button>
                                    <input type="text" name="couplet_slug" id="couplet_slug" placeholder="URL of Couplet"  value="{{ old('couplet_slug', $couplet->couplet_slug) }}"  class="form-control @error('couplet_slug') is-invalid @enderror">
                                    
                                    @error('couplet_slug')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        {{-- With multiple slots, and plugin config parameter --}}
                                       <x-adminlte-select2 id="lang_id" data-url="{{ route('admin.poets.index') }}" name="lang" label="Language"
                                       label-class="text-info" igroup-size="sm" :config="$config">
                                           <x-slot name="prependSlot">
                                               <div class="input-group-text bg-gradient-blue">
                                                   <i class="fas fa-language"></i>
                                               </div>
                                           </x-slot>
                                           <option value="">Choose Language</option>
                                           
                                           @foreach ($languages as $item)
                                               <option value="{{ $item->lang_code }}" data-dir="{{ $item->lang_dir }}" @if (old('lang', $couplet->lang) == $item->lang_code) 
                                                    selected 
                                                @endif>{{ $item->lang_title }}</option>
                                           @endforeach
                                       
                                       </x-adminlte-select2>
                                   </div>
    
                                   <div class="form-group col-6">
                                    {{-- With multiple slots, and plugin config parameter --}}
                                        <x-adminlte-select2 id="choose_poet_id" name="poet_id"  label="Poets" igroup-size="sm">
                                            <option value="">Select Poet</option>                                    
                                            @foreach ($poets as $poet)
                                                <option value="{{ $poet->poet_id }}" @if (old('poet_id', $couplet->poet_id) == $poet->poet_id) 
                                                    selected 
                                                @endif>{{ $poet->poet_laqab }}</option>
                                            @endforeach
                                        </x-adminlte-select2>
                                    </div>
                                </div>

                                {{-- Tags field --}}
                                {{-- With multiple slots, and plugin config parameter --}}
                                @php
                                $config_tags = [
                                    "placeholder" => "Select multiple options...",
                                    "allowClear" => true,
                                ];
                                $selectedTags = old('couplet_tags', json_decode($couplet->couplet_tags) ?: []);
                                @endphp
                                <x-adminlte-select2 id="couplet_tags"  name="couplet_tags[]" label="Tags"
                                label-class="text-danger" igroup-size="sm" :config="$config_tags" multiple>
                                <x-slot name="prependSlot">
                                    <div class="input-group-text bg-gradient-red">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                </x-slot>
                                    
                                    @foreach ($tags as $item)
                                        <option value="{{ $item->slug }}" @if (in_array($item->slug, $selectedTags)) 
                                            selected 
                                        @endif>{{ $item->tag }}</option>
                                    @endforeach
                                    
                                </x-adminlte-select2>

                            </div>{{-- ./col-6 for Information --}}

                            {{-- col-6 for textarea --}}
                            <div class="col-6">
                                <label for="couplet_text" class="form-label">Couplet Detail</label>
                                <textarea class="form-control" name="couplet_text" id="couplet_text" rows="9">{{ $couplet->couplet_text }}</textarea>
                            </div>
                        </div>
                        {{-- /.Row --}}
                    </div>
                    

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('admin.couplets.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
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

@section('plugins.summernote', true)


@section('js')
<script>
    var final_roman_dict_file = "{{ asset('vendor/roman-converter/all_words.dic') }}";
</script>
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/roman-converter/sindhi_to_roman.js') }}" charset="UTF-8"></script>
<script>
$(function () {
    $('#lang_id').on('change', function() {
        var langCode = $(this).val();
        var poetSelect = $('#choose_poet_id');
        var url = $(this).data('url')
        var dir = $(this).find(":selected").data('dir')
        $('#couplet_text').attr('dir', dir);
        
        poetSelect.empty().append($('<option>', {
            value: '',
            text: 'Select Province'
        }));

        if (langCode) {
            $.get(url +'/poets-by-language/' + langCode, function(data) {
                $.each(data, function(key, value) {
                    poetSelect.append($('<option>', {
                        value: value.poet_id,
                        text: value.poet_laqab
                    }));
                });
            });
        }
    });
});
  </script>
@endsection