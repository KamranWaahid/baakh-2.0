@extends('adminlte::page')

@section('title', 'Update Bundle')

@section('content_header')
    
    <div class="d-flex justify-content-between">
        <h1 class="m-0 text-dark">Update Bundle</h1>
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

$poetry_types = ['poetry', 'couplets'];
@endphp
 

<form action="{{ route('admin.bundle.edit-translation', $bundle->id) }}" method="post" enctype="multipart/form-data">
    @csrf
    @method('put')

    <div class="row">
        <div class="col-lg-9 col-md-8 col-sm-12">
             
            <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><p style="font-family: 'Arial'; font-size:1rem;">You are editing {{ $languages->lang_title }} Version of <span class="sd">{{ $bundle->title }}</span></p></h3>
                    </div>
                    <div class="card-body">

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-6">
                                    <label for="title">Bundle Title</label>
                                    @php
                                        $old_title = (!is_null($translations)) ? $translations->title : '';
                                        $old_desc = (!is_null($translations)) ? $translations->description : '';
                                    @endphp
                                    <input type="text" class="form-control  @error('title') is-invalid @enderror" value="{{ old('title', $old_title) }}"  name="title" id="title" placeholder="Enter Bundle's heading">
                                    
                                    @error('title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            
                                <div class="form-group col-6">
                                <label for="link_url">Bundle Slug</label>
                                <input type="text" disabled placeholder="URL of Bundle"  value="{{ $bundle->slug }}"  class="form-control">
                                 
                                </div> 
                            </div> {{-- /.row1 --}}

                            <div class="mb-3">
                              <label for="description" class="form-label">Bundle Detail</label>
                              <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="3">{{ old('description', $old_desc) }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
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
                      

                    <div class="form-group">
                        <label for="language">Language</label>
                        <input type="text" class="form-control" value="{{ $languages->lang_title }}" disabled>
                        <input type="text" class="d-none" name="lang" value="{{ $languages->lang_code }}">
                   </div>

                </div>
            </div>

             

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
                    lang: 'sd',
                    page: params.page || 1
                };
                },
                processResults: function (data) { 
                    return {
                        results: data.map(function (item) {
                            return {
                                id: item.id,
                                text: item.text,
                                model: item.model,
                                'data-model_name': item.model  // Add data-model_name attribute
                            };
                        })
                    };
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
                '    <input type="text" value="'+model_name+'" name="reference_type[]">'+
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