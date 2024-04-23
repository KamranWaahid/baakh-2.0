@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Couplets</h1>
@endsection

@section('plugins.bootstrapSwitch', true)


@section('content')

@php
$config = [
    "placeholder" => "Select Poet",
    "allowClear" => true,
];
@endphp


    <div class="row">
        <div class="col-8 m-auto">
            <div class="card">
                <form action="{{ route('admin.couplets.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="card-header">
                        <h3 class="card-title">Create New Couplet</h3>
                    </div>
                    <div class="card-body">
                        {{-- Row --}}
                        <div class="rosw">
                            {{-- col-6 for Information --}}
                            <div class="couplet-information-dynamic">
                                {{-- Name field --}}
                                <div class="form-group">
                                    <label for="link_url">Couplet Slug</label>
                                    <button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="couplet_text_sd" data-roman-field="couplet_slug" class="btn btn-xs btn-warning btn-roman-convert float-right"  id="btn-roman-convert"><i class="fa fa-plus mr-1"></i> Generate Slug</button>
                                    <input type="text" name="couplet_slug" id="couplet_slug" placeholder="URL of Couplet"  value="{{ old('couplet_slug') }}"  class="form-control @error('couplet_slug') is-invalid @enderror">
                                    
                                    @error('slug')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>


                                <div class="row">
                                    <div class="form-group col-8">
                                        {{-- Tags field --}}
                                        {{-- With multiple slots, and plugin config parameter --}}
                                        @php
                                        $config_tags = [
                                            "placeholder" => "Select multiple options...",
                                            "allowClear" => true,
                                        ];
                                        @endphp
                                        <x-adminlte-select2 id="couplet_tags"  name="couplet_tags[]" label="Tags"
                                        label-class="text-danger" igroup-size="sm" :config="$config_tags" multiple>
                                        <x-slot name="prependSlot">
                                            <div class="input-group-text bg-gradient-red">
                                                <i class="fas fa-tag"></i>
                                            </div>
                                        </x-slot>
                                            
                                            @foreach ($tags as $item)
                                                <option value="{{ $item->slug }}">{{ $item->tag }}</option>
                                            @endforeach
                                            
                                        </x-adminlte-select2>
                                   </div>
    
                                   <div class="form-group col-4">
                                    {{-- With multiple slots, and plugin config parameter --}}
                                        <x-adminlte-select2 id="choose_poet_id" name="poet_id" label="Poet"
                                        label-class="text-info" igroup-size="sm" :config="$config">
                                            @foreach ($poets as $poet)
                                                <option value="{{ $poet->poet_id }}">{{ $poet->poet_laqab }}</option>
                                            @endforeach
                                        </x-adminlte-select2>
                                    </div>
                                </div>

                                

                            </div>{{-- ./col-6 for Information --}}

                            @foreach ($languages as $lang)
                            {{-- col-6 for textarea --}}
                            <div class="couplet-text-dynamic form-group">
                                <label for="couplet_text_{{ $lang->lang_code }}" class="form-label">Couplet in <i>{{ $lang->lang_title }}</i></label>
                                @if ($lang->lang_code !='sd')
                                    <button type="button" onclick="convertText(this)" data-is-slug="0" data-sindhi-field="couplet_text_{{ $lang->lang_code }}" data-roman-field="couplet_text_{{ $lang->lang_code }}" data-field-id="couplet_text_{{ $lang->lang_code }}" class="btn btn-xs btn-warning float-right mr-2">
                                        <i class="fas fa-sync-alt mr-1"></i> Generate Roman
                                    </button>
                                @endif
                                <input type="hidden" name="lang[]" value="{{ $lang->lang_code }}">
                                <textarea class="form-control" dir="{{ $lang->lang_dir }}" name="couplet_text[]" id="couplet_text_{{ $lang->lang_code }}" rows="9"></textarea>
                            </div>
                            @endforeach
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
 
@endsection