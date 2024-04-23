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
<form action="{{ route('admin.poetry.store') }}" id="poetry_create_form" method="post" enctype="multipart/form-data">
    @csrf
    <div class="row">
        
        <div class="col-lg-9 col-12 overlay-wrapper" id="main_col_9">
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
                        {{-- .poetry_title --}}
                        <div class="form-group poetry_title">
                            <label for="poetry_title">Poetry Title</label>
                            <input type="text" class="form-control  @error('poetry_title') is-invalid @enderror" value="{{ old('poetry_title') }}"  name="poetry_title" id="poetry_title" placeholder="Enter Poetry Title for Sindhi language">
                                
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
                            <input type="text" class="form-control  @error('poetry_slug') is-invalid @enderror" value="{{ old('poetry_slug') }}"  name="poetry_slug" id="poetry_slug" placeholder="Enter Poetry's Slug">
                            
                            @error('poetry_slug')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>{{-- .poetry_slug --}}
 
                        <!--Poetry Source Stats-->
                        <div class="form-group">
                            <label for="poetry_info">Detail</label>
                            <x-adminlte-textarea id="poetry_info_sd" name="poetry_info" class="textarea-main" value="{{ old('poetry_info') }}" placeholder="Insert description..."/>
                            
                            @error('poetry_info')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="source">Source</label>
                            <x-adminlte-textarea name="source" class="textarea-main" value="{{ old('source') }}" placeholder="Insert description..."/>
                            
                            @error('source')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="button" class="btn btn-info open-div-button" data-open-div="lang_card_for_sd">Add Sindhi Couplets<i class="fa fa-chevron-right ml-2"></i></button>
                    </div>
                
            </div>

            
            <!---start[Card for Sindhi Translation]--->
            <div class="card" id="lang_card_for_sd" style="display: none;">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h5>Sindhi Couplets</h5>
                        <button type="button" class="btn btn-secondary addMoreRow" onclick="addRow()"><i class="fa fa-plus"></i> Add Sindhi Couplets</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="dynamic_details mt-2" id="dynamic_details">
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-primary open-div-button" data-open-div="main_info_div"><i class="fa fa-chevron-left mr-2"></i>Back to Poetry Information</button>
                        <button type="button" class="btn btn-warning validate-form-button">Validate Form<i class="fa fa-check ml-2"></i></button>
                    </div>
                    
                </div>
            </div>
            <!---end[Card for Sindhi Translation]--->
 
        </div>

        <!---start[Poetry Settings Sidebar]--->
        <div class="settings-sidebar col-lg-3 col-12">
           
            
            <!---start[status card]--->
            <div class="card">
                <div class="card-header">Poetry Settings</div>
                <div class="card-body">
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
                        <x-adminlte-select2 id="poet_id" name="poet_id" class="select2-poets" data-ajax-path="{{ route('admin.poets.by-name') }}" data-option-value="id" data-option-text="poet_laqab" label="Poets" igroup-size="sm">
                            
                        </x-adminlte-select2>
                    </div>
                    <!---end[Select Poet]--->

                    <!---start[Select Category]--->
                    <div class="mt-2">
                        <x-adminlte-select2 id="category_id" name="category_id" label="Categories" igroup-size="sm">
                            <option value="">Select Category</option>                                    
                            @foreach ($categories as $item)
                                <option value="{{ $item->id }}" data-cstyle="{{ $item->content_style }}">{{ $item->detail->cat_name }}</option>
                            @endforeach
                        </x-adminlte-select2>
                    </div>
                    <!---end[Select Category]--->

                    <!---start[Content Style]--->
                    <div class="mt-2">
                        <label for="content_style">Content Style</label>
                        <select name="content_style" id="content_style" class="form-control">
                            <option value="">Select Style</option>                                    
                            @foreach ($content_styles as $item)
                                <option value="{{ $item }}">{{ Str::ucfirst($item) }}</option>
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
                            
                            @foreach ($tags as $item)
                                <option value="{{ $item->slug }}">{{ $item->tag }}</option>
                            @endforeach
                            
                        </x-adminlte-select2>
                    </div>
                    <!---end[Select Tags]--->

                    
                </div>
            </div>
            <!---end[status card]--->
        </div>
        <!---end[Poetry Settings Sidebar]--->
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
<script>
    var final_roman_dict_file = "{{ asset('vendor/roman-converter/all_words.dic') }}";
</script>

<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/roman-converter/sindhi_to_roman.js') }}" charset="UTF-8"></script>
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(function () {
        var b_url = "{{ url('/admin/') }}";
        
        $(function(){
            $("[name='is_visible']").bootstrapSwitch();
            $("[name='is_featured']").bootstrapSwitch();
        })

        /**
         * Dynamic Select2 for Poets and Tags 
        **/
        _select2Dynamic('select2-poets') // poets
        
        // change category and set poetry style
        $(document).on('change', '#category_id', function () {
            var cat_style = $(this).find(':selected').data('cstyle');
            $('#content_style option[value="' + cat_style + '"]').prop('selected', true);
        });

        $(function() {
            // get height of main_col_9 div
            const height = $('#main_col_9').find('.card').first().outerHeight(true);
            // Set the height for all cards
            $('#main_col_9').find('.card').css('min-height', height);

        })

        $(document).on('click', '.open-div-button', function() {
            var isValid = validateForm();
            if (isValid) {
                if($(document).find('.couplet-dyn-row').length === 'undefined' || $(document).find('.couplet-dyn-row').length == 0) {
                    addRow();
                }
                // Proceed to the next step
                var div_id = $(this).data('open-div');
                $('#' + div_id).show();
                $(this).parent().closest('div.card').hide();
            }
        });

         // Example of using validateForm function before submitting the form
        $('.validate-form-button').click(function () {
            var isValid = validateForm();
            if (isValid) {
                //$('#poetry_create_form').submit();
                ajaxSubmitForm();
            }
        });
        
        
    });

    function new_row(number, lang, dir) {
        var html = '<div class="row p-2 rounded couplet-dyn-row mt-3" data-cusid="'+number+'" id="dyn_row'+number+'" style="background:#d1e9e4;">'+
                        '<div class="form-group col-12">'+
                        '<label for="couplet_slug_'+number+'">Couplet Slug</label>'+
                        '<button type="button" onclick="convertText(this)" data-is-slug="1" data-sindhi-field="couplet_text_'+number+'" data-roman-field="couplet_slug_'+number+'" data-field-id="couplet_slug_'+number+'" class="btn btn-xs btn-warning float-right mr-2" id="generate_slug_button_'+number+'"><i class="fas fa-sync-alt mr-1"></i> Generate Slug</button>'+
                        '<input type="text" class="form-control"  name="couplet_slug[]" id="couplet_slug_'+number+'" placeholder="Couplet Slug">'+
                        '</div>'+
                        '<div class="form-group col-12"><label for="couplet_text_'+number+'">Details</label>'+
                        '<textarea class="textarea form-control '+lang+'"  dir="'+dir+'" name="couplet_text[]" rows="5" id="couplet_text_'+number+'" placeholder="Insert description..."></textarea>'+
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
        //var sel_lang = $('select#lang').find(':selected').val();
        var lang = 'sd text-right';
        var dir = 'rtl';
        
        if(typeof last_id === 'undefined' || isNaN(last_id) || last_id === null){
            $(divId).append(new_row(1, lang, dir))
            
        }else{
            var new_id = last_id + 1;
            $(divId).append(new_row(new_id, lang, dir))
        }
    }

     

    function ajaxSubmitForm() {
        var formData = $('#poetry_create_form').serialize();
        var validateButtonId = $('#poetry_create_form').find('.validate-form-button');
        var overlayId = $('#poetry_create_form').find('.main-row-overly')
        console.log('========== formData =======');
        console.log(formData);
        /// Ajax Request for Submit Form
        $.ajax({
            url:'{{ route('admin.poetry.store') }}',
            type:'post',
            data:formData,
            headers: {
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function (){
                validateButtonId.attr('disabled', true)
                overlayId.show();
                console.log('beforeSend called on ajax request of Submit Form');
            },
            success: function (res){
                
                if(res.type === 'success') {
                    toastr.success(res.message)
                    // redirect after 3 seconds
                    setTimeout(function() {
                        window.location.href = res.route;
                    }, 1000); // 3000 milliseconds = 3 seconds
                }else{
                    toastr.error(res.message)
                }
                validateButtonId.attr('disabled', false)
                overlayId.hide();
                
            },
            error: function (xhr, ajaxOptions, thrownError){
                console.error('error called on ajax request of Submit Form')
                console.error(xhr.status)
                console.error(thrownError)
                validateButtonId.attr('disabled', false)
                overlayId.hide();
                
                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.length > 0) {
                    var errorMessages = xhr.responseJSON.errors;
                    errorMessages.forEach(function(errorMessage) {
                     toastr.error(errorMessage);
                    });
                } else {
                    toastr.error('An error occurred while checking slugs.');
                }

            }
        });
    }
</script>

<script>
$(function () {
     

    $('#poetry_create_form').validate({
        ignore: [],
        rules: {
            poetry_title: {
                required: true,
                minlength: 4,
            },
            
            poetry_slug: {
                required: true,
                minlength: 3
            },
            poet_id: {
                required: true
            },
            content_style: {
                required: true
            },
            category_id: {
                required: true
            },
            'couplet_slug[]': {
                required: true,
                minlength: 3,
            },
            'couplet_text[]': {
                required: true,
                minlength: 3
            },
            
        },
        messages: {
            poetry_title: {
                required: "Please Enter Poetry title for Sindhi language",
                minlength: "Poetry couplet text must be at least 3 characters long"
            },
            poetry_slug: {
                required: "Please generate a slug for this poetry",
                minlength: "Slug must be at least 5 characters long"
            },
            'couplet_slug[]': {
                required: "PlPlease fill all fields of couplet slug",
                minlength: "Poetry couplet text must be at least 3 characters long"
            },
            'couplet_text[]': {
                required: "Please fill all fields of couplet text",
                minlength: "Poetry couplet text must be at least 3 characters long"
            },
            category_id: {
                required: "Please select category"
            },
            content_style: {
                required: "Please choose content style"
            },
            poet_id: {
                required: "Please select poet"
            },
            
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        }
    });

})

function validateForm() {
    var isValid = $('#poetry_create_form').valid();
    return isValid;
}
</script>


<script>
$(document).on('focusout', 'input[name^="couplet_slug"]', function() {
    validateCoupletSlugs();
});



// Update provinces select option based on selected country
$(document).on('focusout', 'input[name="poetry_slug"]', function() {
    var poetrySlug = $(this).val();
    /// Ajax Request for check slug with language
    $.ajax({
        url:'{{ route('admin.poetry.check-slug') }}',
        type:'post',
        data:{slug: poetrySlug},
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


function validateCoupletSlugs() {
    var allSlugs = $('input[name^="couplet_slug"]').map(function() {
        return $(this).val();
    }).get();

    /// Ajax Request for Check Slugs
    $.ajax({
        url:'{{ route('check.unique.slug.couplets') }}',
        type:'post',
        data:{slug: allSlugs},
        headers: {
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function (){
            console.log('beforeSend called on ajax request of Check Slugs');
        },
        success: function (res){
            if (response.valid) {
                // Slugs are unique, no error message needed
                console.log('Slugs are unique.');
            }
        },
        error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Check Slugs')
            console.error(xhr.status) // 422 error code 
            console.error(thrownError)
            if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.length > 0) {
                var errorMessages = xhr.responseJSON.errors;
                errorMessages.forEach(function(errorMessage) {
                   
                    // Extract the index mentioned in the error message
                    var indexMatch = errorMessage.match(/slug\.(\d+)/);
                    if (indexMatch) {
                        var index = parseInt(indexMatch[1]);

                        // Highlight the input field with the corresponding index
                        var inputField = $('input[name="couplet_slug[]"]').eq(index);
                        inputField.addClass('is-invalid');

                        // Show the error message next to the input field
                        inputField.closest('.form-group').find('.invalid-feedback').remove(); // Remove previous error messages
                        inputField.closest('.form-group').append('<span class="invalid-feedback" role="alert">' + errorMessage + '</span>');
                    }
                });
            } else {
                toastr.error('An error occurred while checking slugs.');
            }
        }
    });
}

</script>


@endsection