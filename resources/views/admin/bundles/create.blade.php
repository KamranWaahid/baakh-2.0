@extends('adminlte::page')

@section('title', 'Add New Bundle')

@section('content_header')
    
    <div class="d-flex justify-content-between">
        <h1 class="m-0 text-dark">Add Bundle</h1>
        <button type="button" onclick="window.location.href='{{ route('admin.bundle.index') }}'" class="btn btn-warning">Back to Bundles<i class="fa fa-arrow-right ml-1"></i></button>
    </div>
@endsection

@section('plugins.bootstrapSwitch', true)


@section('content')

@php
$config = [
    "placeholder" => "Select multiple options...",
    "allowClear" => true,
];

$poetry_types = ['ghazal', 'couplets'];
@endphp
 

<form action="{{ route('admin.bundle.store') }}" method="post" enctype="multipart/form-data">
    @csrf
    @method('put')

    <div class="row">
        <div class="col-lg-9 col-md-8 col-sm-12">
             
            <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Create New Bundle</h3>
                    </div>
                    <div class="card-body">

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-6">
                                    <label for="title">Bundle Title</label>
                                    <input type="text" dir="rtl" class="form-control  sd @error('title') is-invalid @enderror" value="{{ old('title') }}"  name="title" id="title" placeholder="Enter Bundle's heading">
                                    
                                    @error('title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            
                                <div class="form-group col-6">
                                <label for="link_url">Bundle Slug</label>
                                <button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="title" data-roman-field="slug" class="btn btn-xs btn-warning btn-roman-convert float-right"  id="btn-roman-convert"><i class="fa fa-plus mr-1"></i> Generate Slug</button>
                                <input type="text" name="slug" id="slug" placeholder="URL of Bundle"  value="{{ old('slug') }}"  class="form-control @error('slug') is-invalid @enderror">
                                
                                @error('slug')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div> 
                            </div> {{-- /.row1 --}}

                            <div class="mb-3">
                              <label for="description" class="form-label">Bundle Detail</label>
                              <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- row2 for Poetry selector --}}
                            <div class="row d-flex align-items-center rounded pt-2" id="poetry-selector" style="background-color:#d4e2ef;">
                                <div class="form-group col-3">
                                    <label for="choose_poet_id">Poet?</label>
                                    <select name="choose_poet_id" id="choose_poet_id" data-url="{{ route('admin.poets.by-name') }}" class="form-control choosePoets select2" require>
                                        <option value=""></option>
                                    </select>
                                    <small>Please, do not leave empty</small>
                                </div>

                                <div class="form-group col-8">
                                    <label for="choose_couplet_id">Choose Couplet to Add into bundle </label>
                                    <select name="choose_couplet_id" id="choose_couplet_id" data-url="{{ route('admin.bundle.couplets') }}" class="form-control chooseCouplet select2" require>
                                        <option value=""></option>
                                    </select>
                                    <small>Please, do not leave empty</small>
                                </div>
                                <div class="form-group col-1 d-flex align-items-center">
                                    <button type="button" class="btn btn-success btn_add_item btn-block"><i class="fa fa-plus"></i></button>
                                    <button type="button" class="btn btn-success btn_spinner_item disabled btn-block" style="display:none;" ><div class="spinner-border spinner-border-sm text-light" role="status"> <span class="sr-only">Loading...</span></div> Saving</button>
                                </div>
                            </div>
                            {{-- /.row2 for Poetry selector --}}

                            {{-- Bundle Items --}}
                            <div class="dyn-bundle-items" style="border-top:2px dotted solid black;font-family: 'MB Lateefi SK 2.0'; " id="dyn-bundle-items" >
                                @if (old('reference_id'))
                                    @foreach (old('reference_id') as $key => $item)
                                    <div class="couplet-container rounded p-2 mt-3 position-relative" id="item_{{ $item }}" style="background-color:#f4f4f4; border: 1px solid #ccc; padding: 10px;">
                                        <button type="button" data-cid="item_'{{ $item }}" class="btn btn-sm btn-danger  btn_remove_couplet position-absolute top-0 m-2" style="right: 0;"><i class="fa fa-trash"></i></button>
                                        <div class="poetry text-center" style="font-size:1.3rem">
                                           <p>{{ old('reference_text')[$key] }}</p>
                                        </div>
                                        <input type="hidden" value="{{ $item }}" name="reference_id[]">
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                    </div>
            </div>
        </div>

        <!---start[Bundle Settings]--->
        <div class="col-lg-3 col-md-4 col-sm-12">
            <!---start[card for save]--->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-info btn-block"><i class="fa fa-save"></i> Save</button>
                </div>
            </div>
            <!---end[card for save]--->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bundle Settings</h3>
                </div>
                <div class="card-body">
                    <!---start[Status of Bundle]--->
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
                    <!---end[Status of Bundle]--->

                    <!---start[Bundle Content Type]--->
                    <div class="form-group">
                        {{-- With multiple slots, and plugin config parameter --}}
                       <x-adminlte-select2 id="bundle_type" name="bundle_type" label="Bundle Content Type" label-class="text-info" igroup-size="sm" :config="$config">
                            @foreach ($poetry_types as $item)
                                <option value="{{ $item }}" @if (old('bundle_type') == $item)
                                    selected
                                @endif>{{ Str::ucfirst($item) }}</option>
                            @endforeach
                       
                       </x-adminlte-select2>
                       @error('bundle_type')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                   </div>
                    <!---end[Bundle Content Type]--->

                    <div class="form-group">
                        <label for="language">Language</label>
                        <input type="text" class="form-control" value="Sindhi (Default)" disabled>
                   </div>

                </div>
            </div>

            <!---start[Card for Thumbnail]--->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bundle Thumbnail</h3>
                </div>
                <div class="card-body">
                    <x-adminlte-input-file name="bundle_thumbnail"  placeholder="Choose a file..." disable-feedback/>
                    <div class="thumbnail-preview"></div>
                    @error('bundle_thumbnail')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <!---end[Card for Thumbnail]--->
            
            <!---start[Card for Cover Image]--->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bundle Cover Image</h3>
                </div>
                <div class="card-body">
                    <x-adminlte-input-file name="bundle_cover" placeholder="Choose a file..." disable-feedback/>
                    <div class="cover-preview"></div>
                </div>
            </div>
            <!---end[Card for  Cover Image]--->

        </div>
        <!---end[Bundle Settings]--->
    </div>

</form>
@endsection


@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-bs4.min.css') }}">
@endsection

@section('plugins.summernote', true)
@section('plugins.bootstrapSwitch', true)

@section('js')
<script>
    var final_roman_dict_file = "{{ asset('vendor/roman-converter/all_words.dic') }}";
</script>
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('vendor/roman-converter/sindhi_to_roman.js') }}" charset="UTF-8"></script>
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(function(){
        $("[name='is_visible']").bootstrapSwitch();
        $("[name='is_featured']").bootstrapSwitch();
    })
    $(function () {
        
        // Summernote
        $('textarea').summernote()
        bsCustomFileInput.init();
        
        $('#choose_poet_id').select2({
            placeholder: 'Select a Poet',
            ajax : {
                url : $('#choose_poet_id').data('url'),
                type: 'post',
                dataType: 'json',
                delay: 250,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                data : function (params) {
                    return {
                        term: params.term, // Search term entered by the user
                        page: params.page || 1
                    };
                },
                processResults: function (data) { 
                    if(data.message)
                    {
                        alert(data.message)
                    }else{

                        return {
                            results: data
                        };
                    }
                },
                cache: true,
            },
            minimumInputLength: 2,
        });
 

        // here is my JS code
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
                    type: $('#bundle_type').find(':selected').val(),
                    poet: $('#choose_poet_id').find(':selected').val(),
                    lang: 'sd',
                    page: params.page || 1
                };
                },
                processResults: function (data) { 
                    if (data && data.length > 0) {
                        return {
                            results: data.map(function (item) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    model: item.model,
                                    'data-model_name': item.model
                                };
                            })
                        };
                    } else {
                        return {
                            results: [{
                                id: '',
                                text: 'No data available', // Message for no data
                                disabled: true // Disable this option
                            }]
                        };
                    }
                },
                cache: true,
            },
            minimumInputLength: 2, // Set the minimum input length required
            templateSelection: function (data, container) {
                // Add custom attributes to the <option> tag for the selected option
                $(data.element).attr('data-model_name', data.model);
                return data.text;
            }
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
            var container = $('#dyn-bundle-items');
            var item = $('#choose_couplet_id').find(':selected');

            if(container.find('#item_' + item.val()).length === 0)
            {
                var model_name = item.data('model_name')
                var html = ''+
                '<div class="couplet-container rounded p-2 mt-3 position-relative" id="item_'+item.val()+'" style="background-color:#f4f4f4; border: 1px solid #ccc; padding: 10px;">'+
                '    <button type="button" data-cid="item_'+item.val()+'" class="btn btn-sm btn-danger  btn_remove_couplet position-absolute top-0 m-2" style="right: 0;"><i class="fa fa-trash"></i></button>'+
                '    <div class="poetry text-center" style="font-size:1.3rem">'+
                '       <p>'+nl2br(item.text())+'</p>'+
                '    </div>'+
                '    <input type="hidden" value="'+item.val()+'" name="reference_id[]">'+
                '    <input type="hidden" value="'+nl2br(item.text())+'" name="reference_text[]">'+
                '    <input type="hidden" value="'+model_name+'" name="reference_type[]">'+
                '</div>';
                container.append(html)
            }else{
                toastr.error('ھي آئٽم اڳ ئي موجود آھي')
            }
        })
    });

    function nl2br(str) {
        return str.replace(/(?:\r\n|\r|\n)/g, '<br>');
    }
  </script>
@endsection