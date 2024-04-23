@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Update Translation of poetry</h1>
        <a href="{{ route('admin.poetry.index') }}" class="btn btn-sm btn-warning"><i class="fa fa-list mr-1"></i>View Poetry</a>
    </div>
@stop

@section('content')
    
    <div class="row" id="mainInformation">
        <!--= start[Original Content COl] =-->
        <div class="col-6" id="originalContent">
            @include('admin.poetry.partials.info_edit_default')
        </div>
        <!--= end[Original Content COl] =-->
        
        <!--= start[Translateable Content Col] =-->
        <div class="col-6 overlay-wrapper" id="EnTranslationContent">
            @include('admin.poetry.partials.info_edit_for_lang')
        </div>
        <!--= end[Translateable Content Col] =-->
    </div>

    <hr>
    <div class="section d-flex justify-content-center my-3" style="background:#D9D9D9">
        <div class="section-title py-3"><i style="font-size: 1.2rem;">Add More Couplets</i></div>
    </div>

    <form id="updateCoupletsTranslationForm" action="{{ route('admin.poetry.translation.update-couplets', ['id' => $info->poetry_id, 'language' => $for_language]) }}" method="post">
        @method('put')
        @csrf
        <input type="hidden" name="lang" class="selected-language" value="{{ $for_language }}">
        <input type="hidden" name="poetry_id" value="{{ $info->poetry_id }}">
        <!---=======start[ Row 2 for Couplets ]=======---->
        <div class="row" id="coupletsInformaiton">
            <!--= start[Original Content COl] =-->
            <div class="col-6" id="originalContent">
                <div class="card" style="background: #4caf4f62">
                    <div class="card-header">Main Information {Default Language}</div>
                    <div class="card-body" >
                        @foreach ($couplets as $i => $item)
                            @if ($i > 0 && $i < count($couplets))
                                <hr style="background:#4caf4fa7;height:5px;">
                            @endif
                            <div class="couplet-original-content" >
                                <input type="hidden" name="couplet_tags[]" value="{{ $item->couplet_tags }}" />
                                <input type="hidden"  name="couplet_slug[]" value="{{ $item->couplet_slug }}" class="form-control slugs-en-{{ $i }}">
                                
                                {{-- Information --}}
                                <div class="form-group">
                                    <label for="information_sd_1">Information</label>
                                    @php
                                        $content = $item->couplet_text;
                                    @endphp
                                    <textarea dir="rtl" disabled class="form-control informations-sd-{{ $i }}" placeholder="Insert Information here" rows="5">{{ old('information', $content) }}</textarea>
                                
                                    @error('information[]')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                        
                    
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-sm btn-warning copy-couplets"><i class="fa fa-copy mr-1"></i>Copy Original to Translation</button>
                    </div>
                </div>
            </div>
            <!--= end[Original Content COl] =-->
            
            <!--= start[Translateable Content Col] =-->
            <div class="col-6" id="EnTranslationContent">
                <div class="card">
                    <div class="card-header">Main Information {English Language}</div>
                    <div class="card-body">
                        
                        @foreach ($couplets_tr as $i => $item)
                            <div class="couplet-en-content">
                                <input type="hidden" name="couplet_ids[]" value="{{ $item->id }}">
                                {{-- Information --}}
                                <div class="form-group">
                                    <label for="information_{{ $for_language }}_1">Information</label>
                                    <button type="button" onclick="convertText(this)" data-is-slug="0" data-sindhi-field="informations-{{ $for_language }}-{{ $i }}" data-roman-field="informations-{{ $for_language }}-{{ $i }}" data-field-id="informations-{{ $for_language }}-{{ $i }}" class="btn btn-xs btn-warning float-right mr-2">
                                        <i class="fas fa-sync-alt mr-1"></i> Generate Roman
                                    </button>
                                    <textarea name="couplet_text[]" autocomplete="off" spellcheck="off" style="font-size: 1.2rem;" id="informations-{{ $for_language }}-{{ $i }}" class="form-control informations-{{ $for_language }}-{{ $i }} @error('couplet_text') is-invalid @enderror" placeholder="Insert Information here" rows="5">{{ old('couplet_text[]', $item->couplet_text) }}</textarea>

                                    @error('couplet_text[]')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-save mr-1"></i>Save</button>
                    </div>
                </div>
            </div>
            <!--= end[Translateable Content Col] =-->
        </div>
        <!---=======end[ Row 2 for Couplets ]=======---->
    </form>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('plugins.jqueryValidation', true)
@section('plugins.paceProgress', true)


@section('js')
<script>
    var final_roman_dict_file = "{{ asset('vendor/roman-converter/all_words.dic') }}";
</script>
<script src="{{ asset('vendor/roman-converter/sindhi_to_roman.js') }}" charset="UTF-8"></script>

    <script>
        $('.sidebar-mini').toggleClass('sidebar-collapse');
        $('.content').addClass('pb-5')

        $(function () {
            const selectedLanguage = $('.selected-language').val()
            if(selectedLanguage.length == 0) {
                $('button[type="submit"]').attr('disabled', true)
                $('button.btn-save-main-information').attr('disabled', true)
            }

            $('.copy-couplets').click(function() {
                $('.couplet-original-content').each(function(index, element) {
                    var originalContent = $(element).find('.informations-sd-' + (index)).val();
                    $('.couplet-'+selectedLanguage+'-content .informations-'+selectedLanguage+'-' + (index)).val(originalContent);
                });
            });
        })

        // on update mainInformationForm 
        function saveInformation() {
            console.log('called save information button to validate');
            // #mainInformationForm validate form then call ajaxUpdateContentMainInformation() if validated and submit form data
            $('#mainInformationForm').validate({
                rules: {
                    // Define rules for required fields using class selectors
                    title: {
                        required: true,
                        minlength: 4,
                    },
                },
                messages: {
                    // Define custom error messages for each required field
                    title: {
                        required: "Title is required."
                    },
                },
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    error.insertAfter(element);
                },
                submitHandler: function(form) {
                    console.log('all fields are validate');
                    // Call ajaxUpdateContentMainInformation if form is validated
                    // disable form.submit() default and call ajax update method
                    event.preventDefault();
                    ajaxUpdateContentMainInformation();
                }
            });
        }


        function ajaxUpdateContentMainInformation() {
            var form = $('#mainInformationForm');
            var button = $('.btn-save-main-information');
            $('.info-card-overly').show();
            var formData = form.serialize();
            // Ajax Request for Save Main Information
            $.ajax({
                url: form.attr('action'),
                type: 'put',
                data: formData,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                beforeSend: function () {
                    console.log('beforeSend called on ajax request of Save Main Information');
                },
                success: function (response) {
                    console.log('success called on ajax request of Save Main Information');
                    if(response.type === 'error') {
                        button.attr('disabled', false);
                        toastr.error(response.message)
                    }else{
                        toastr.success(response.message);
                    }
                    
                    $('.info-card-overly').hide();
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.error('error called on ajax request of Save Main Information');
                    console.error(xhr.status);
                    button.attr('disabled', false);
                    toastr.error(xhr.responseJSON.message);
                    $('.info-card-overly').hide();
                }
            });
        }

        /** Add Couplets */
        $('#updateCoupletsTranslationForm').validate({
            rules: {
                // Define rules for required fields using class selectors
                'couplet_text[]': {
                    required: true,
                    minlength: 4,
                },
            },
            messages: {
                // Define custom error messages for each required field
                'couplet_text[]': {
                    required: "All Fields are required."
                },
            },
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                error.insertAfter(element);
            },
            submitHandler: function(form) {
                console.log('all fields are validate');
                // Call ajaxUpdateContentMainInformation if form is validated
                // disable form.submit() default and call ajax update method
                return false;
            }
        });

        /** Submit Form Ajax Couplets */ 
        $('#updateCoupletsTranslationForm').submit(function (e) {
            var formData = $(this).serialize();
            var saveBtn = $(this).find('button[type="submit"]');
            $('.couplets-overlay-card').show();
            saveBtn.attr('disabled', true)
            e.preventDefault();
            /// Ajax Request for Add Couplets
            $.ajax({
                url: $(this).attr('action'),
                type:'put',
                data:formData,
                headers: {
                    'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function (){
                    console.log('beforeSend called on ajax request of Update Couplets');
                },
                success: function (res){
                    console.log(res);
                    $('.couplets-overlay-card').hide();
                    if(res.type === 'success') {
                        toastr.success(res.message)
                        setInterval(() => {
                            window.location.href= res.route
                        }, 3000);
                    }else {
                        toastr.error(res.message)
                    }
                    saveBtn.attr('disabled', false)
                },
                error: function (xhr, ajaxOptions, thrownError){
                    console.error('error called on ajax request of Update Couplets')
                    console.error(xhr.status)
                    console.error(thrownError)
                    saveBtn.attr('disabled', false)
                }
            });
        })
    </script>
@stop