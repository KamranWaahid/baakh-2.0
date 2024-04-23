@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Bundles</h1>
@endsection

@section('plugins.bootstrapSwitch', true)


@section('content')

@php
$config = [
    "placeholder" => "Select multiple options...",
    "allowClear" => true,
];
@endphp

<!---= START Form Section =--->
<section class="mt-2 mb-2 pb-3 pt-3" style="background-color:#d4e2ef;">
    <div class="container">
          <div class="row d-flex align-items-center">
            <div class="form-group col-10">
              <label for="choose_couplet_id">Choose Couplet to Add into bundle </label>
              <select name="choose_couplet_id" id="choose_couplet_id" data-url="{{ route('admin.bundle.couplets') }}" class="form-control chooseCouplet select2" require>
                <option value=""></option>
              </select>
              <small>Please, do not leave empty</small>
            </div>
            <div class="form-group col-2 d-flex align-items-center">
              <button type="button" class="btn btn-success btn_add_item btn-block"><i class="fa fa-save"></i></button>
              <button type="button" class="btn btn-success btn_spinner_item disabled btn-block" style="display:none;" ><div class="spinner-border spinner-border-sm text-light" role="status"> <span class="sr-only">Loading...</span></div> Saving</button>
            </div>
          </div>
    </div>
  </section>
  <!---= END Form Section =--->

  
    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('admin.bundle.update', ['id' => $bundle->id]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="card-header">
                        <h3 class="card-title">Create New Bundle</h3>
                    </div>
                    <div class="card-body">

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-5">
                                    <label for="title">Bundle Title</label>
                                    <input type="text" class="form-control  @error('title') is-invalid @enderror" value="{{ old('title') ?: $bundle->title }}"  name="title" id="title" placeholder="Enter Bundle's heading">
                                    
                                    @error('title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            
                                <div class="form-group col-5">
                                <label for="link_url">Bundle Slug</label>
                                <button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="title" data-roman-field="slug" class="btn btn-xs btn-warning btn-roman-convert float-right"  id="btn-roman-convert"><i class="fa fa-plus mr-1"></i> Generate Slug</button>
                                <input type="text" name="slug" id="slug" placeholder="URL of Bundle"  value="{{ old('slug') ?: $bundle->slug }}"  class="form-control @error('slug') is-invalid @enderror">
                                
                                @error('slug')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div>

                                <div class="form-group col-2">
                                    @php
                                        $typesArray = ['couplet' => 'Couplets', 'ghazal' => 'Ghazal', 'nazam' => 'Nazam'];
                                    @endphp
                                     {{-- With multiple slots, and plugin config parameter --}}
                                    <x-adminlte-select2 id="bundle_type" name="bundle_type" label="Bundle Type"
                                    label-class="text-info" igroup-size="sm" :config="$config">
                                        @foreach ($typesArray as $key => $item)
                                            <option value="{{ $key }}" @if (old('bundle_type', $bundle->bundle_type) == $key) 
                                                selected 
                                            @endif>{{ $item }}</option>
                                        @endforeach                                    
                                    </x-adminlte-select2>
                                </div>

                            </div> {{-- /.row1 --}}

                            <div class="mb-3">
                              <label for="bundle_info" class="form-label">Bundle Detail</label>
                              <textarea class="form-control" name="bundle_info" id="bundle_info" rows="3">{{ old('bundle_info') ?: $bundle->bundle_info }}</textarea>
                            </div>

                            {{-- row2 --}}
                            <div class="row">
                                <div class="form-group col-4">
                                    <a href="{{ url($bundle->bundle_thumbnail) }}" class="btn btn-xs btn-warning float-right" target="_blank"><i class="fa fa-image"></i></a>
                                    <x-adminlte-input-file name="bundle_thumbnail" label="Bundle Thumbnail" placeholder="Choose a file..." disable-feedback/>
                                </div>
                                <div class="form-group col-6">
                                    @if ($bundle->bundle_cover != null)
                                        <a href="{{ url($bundle->bundle_cover) }}" class="btn btn-xs btn-warning float-right" target="_blank"><i class="fa fa-image"></i></a>    
                                    @endif
                                    <x-adminlte-input-file name="bundle_cover" label="Bundle Cover" placeholder="Choose a file..." disable-feedback/>
                                </div>

                                <div class="form-group col-2">
                                    {{-- With multiple slots, and plugin config parameter --}}
                                   <x-adminlte-select2 id="lang" name="lang" label="Language"
                                   label-class="text-info" igroup-size="sm" :config="$config">
                                       <x-slot name="prependSlot">
                                           <div class="input-group-text bg-gradient-blue">
                                               <i class="fas fa-language"></i>
                                           </div>
                                       </x-slot>
                                       
                                       @foreach ($languages as $item)
                                           <option value="{{ $item->lang_code }}" @if (old('lang', $bundle->lang) == $item->lang_code) 
                                            selected 
                                        @endif>{{ $item->lang_title }}</option>
                                       @endforeach
                                   
                                   </x-adminlte-select2>
                               </div>
                                
                               
                            </div>
                            {{-- /.row2 --}}

                            {{-- Bundle Items --}}
                           

                            <div class="dyn-bundle-items" id="dyn-bundle-items" >
                            
                                @foreach ($bundle->items as $item)
                                <div class="couplet text-center rounded p-2 mt-3" id="couplet_{{ $item->couplet->id }}" style="background-color:#d4e2ef;">
                                    <div class="poetry text-center" dir="rtl">
                                        <p>{!! nl2br($item->couplet->couplet_text) !!}</p>
                                    </div>
                                    <div class="buttons">
                                        <input type="hidden" value="{{ $item->couplet->id }}" name="couplet_id[]">
                                        <button type="button" data-cid="couplet_{{ $item->couplet->id }}" class="btn btn_remove_couplet btn-sm btn-danger">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                    </div>
                    

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('admin.bundle.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
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

@section('plugins.summernote', true)


@section('js')
<script>
    var final_roman_dict_file = "{{ asset('vendor/roman-converter/all_words.dic') }}";
</script>
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('vendor/roman-converter/sindhi_to_roman.js') }}"></script>
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(function () {
        
        // Summernote
        $('textarea').summernote()
        bsCustomFileInput.init();

        $(document).on('change', '.chooseCouplet', function() {
            var selectedValue = $(this).val();
            console.log('Selected Value: ' + selectedValue);
            // Perform any actions you want to do with the selected value
        });

        $('.chooseCouplet').select2({
            placeholder: 'Select a Couplet',
            ajax : {
                url : $('.chooseCouplet').data('url'),
                type: 'post',
                dataType: 'json',
                delay: 250,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                data : function (params) {
                console.log("Searched for > " + params.term);
                return {
                    term: params.term, // Search term entered by the user
                    page: params.page || 1
                };
                },
                processResults: function (data) { 
                return {
                    results: data
                };
                },
                cache: true
            },
            minimumInputLength: 2 // Set the minimum input length required
        });

        /**
         * btn_add_item
         * 
        */

        $(document).on('click', '.btn_remove_couplet', function (e) {
            e.preventDefault();
            $('#'+$(this).data('cid')).remove();
        })
        
        $(document).on('click', '.btn_add_item', function (e) {
            e.preventDefault();
            var item = $('#choose_couplet_id').find(':selected');

            var html = '<div class="couplet text-center rounded p-2 mt-3" id="couplet_'+item.val()+'" style="background-color:#d4e2ef;">'+
                        '<div class="poetry text-center">'+
                            '<p>'+nl2br(item.text())+'</p>'+
                        '</div>'+
                        '<div class="buttons">'+
                        '<input type="hidden" value="'+item.val()+'" name="couplet_id[]">'+
                        '<button type="button" data-cid="couplet_'+item.val()+'" class="btn btn_remove_couplet btn-sm btn-danger"><i class="fa fa-trash"></i></button>'+
                        '</div>'+
                        '</div>';
            $('#dyn-bundle-items').append(html)
        })
    });

    function nl2br(str) {
        return str.replace(/(?:\r\n|\r|\n)/g, '<br>');
    }
  </script>
@endsection