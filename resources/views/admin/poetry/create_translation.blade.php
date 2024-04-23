@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Add Translation of poetry</h1>
        <a href="{{ route('admin.poetry.index') }}" class="btn btn-sm btn-warning"><i class="fa fa-list mr-1"></i>View Poetry</a>
    </div>
@stop

@section('content')
    
    <div class="row" id="mainInformation">
        <!--= start[Original Content COl] =-->
        <div class="col-6" id="originalContent">
            <div class="card" style="background: #4caf4f62">
                <div class="card-header">Main Information {Default Language}</div>
                <div class="card-body">
                    {{-- Title --}}
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" disabled class="form-control blog-title @error('title') is-invalid @enderror" value="{{ $info->title }}" id="sindhi_title"  placeholder="Insert Title here">
                    
                        @error('title')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Source --}}
                    <div class="form-group">
                        <label for="source">Source</label>
                        <textarea disabled class="form-control blog-title @error('source') is-invalid @enderror" placeholder="Insert Source here" rows="5">{{ $info->source }}</textarea>
                        
                        @error('source')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    {{-- Information --}}
                    <div class="form-group">
                        <label for="info">Information</label>
                        <textarea disabled class="form-control blog-title @error('info') is-invalid @enderror" placeholder="Insert Information here" rows="5">{{ $info->info }}</textarea>
                    
                        @error('info')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <a href="#" class="btn btn-sm btn-secondary"><i class="fa fa-edit mr-2"></i>Edit Default Language's Information</a>
                </div>
            </div>
        </div>
        <!--= end[Original Content COl] =-->
        
        <!--= start[Translateable Content Col] =-->
        <div class="col-6 overlay-wrapper" id="EnTranslationContent">
            <form action="{{ route('admin.poetry.translation.store-info', ['id' => $info->poetry_id, 'language' => $for_language]) }}" id="mainInformationForm" method="post">
                <input type="hidden" name="lang" value="{{ $for_language }}">
                <input type="hidden" name="poetry_id" value="{{ $info->poetry_id }}">
                <div class="card overlay-wrapper">
                    <!--= start[Main Information Card Overly] =-->
                        <div class="overlay info-card-overly" style="display:none;">
                            <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                            <div class="text-bold pt-2 sr-only">Loading...</div>
                        </div>
                    <!--= end[Main Information Card Overly] =-->
                    <div class="card-header">Main Information <strong>{{ $for_language }}</strong> Language</div>
                    <div class="card-body">
                        {{-- Title --}}
                        <div class="form-group">
                            <label for="title">Title</label>
                            <button type="button" onclick="convertText(this)" data-is-slug="0" data-sindhi-field="sindhi_title" data-roman-field="title" data-field-id="title" class="btn btn-xs btn-warning float-right mr-2">
                                <i class="fas fa-sync-alt mr-1"></i> Generate Slug
                            </button>
                            <input type="text" name="title" id="title" class="form-control poetry-title @error('title') is-invalid @enderror" value="{{ old('title') }}"  placeholder="Insert Title here">
                        
                            @error('title')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Source --}}
                        <div class="form-group">
                            <label for="source">Source</label>
                            <textarea name="source" class="form-control blog-title @error('source') is-invalid @enderror" placeholder="Insert Source here" rows="5">{{ old('source') }}</textarea>
                            
                            @error('source')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        
                        {{-- Information --}}
                        <div class="form-group">
                            <label for="info">Information</label>
                            <textarea name="info" class="form-control blog-title @error('info') is-invalid @enderror" placeholder="Insert Information here" rows="5">{{ old('info') }}</textarea>
                        
                            @error('info')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-sm btn-success btn-save-main-information" onclick="saveInformation()"><i class="fa fa-save mr-2"></i>Update information</button>
                    </div>
                </div>
            </form>
        </div>
        <!--= end[Translateable Content Col] =-->
    </div>

    <hr>
    <div class="section d-flex justify-content-center my-3" style="background:#D9D9D9">
        <div class="section-title py-3"><i style="font-size: 1.2rem;">Add More Couplets</i></div>
    </div>

    <form id="storeCoupletsTranslationForm" action="{{ route('admin.poetry.translation.store-couplets', ['id' => $info->poetry_id, 'language' => $for_language]) }}" method="post">

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
                                <input type="hidden" name="poet_id" value="{{ $item->poet_id }}">
                                
                                <input type="hidden" name="couplet_slug[]" value="{{ $item->couplet_slug }}" class="form-control slugs-en-{{ $i }}"   placeholder="Insert Slug here">
                                
                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" disabled value="{{ $item->couplet_slug }}" class="form-control slugs-en-{{ $i }}"   placeholder="Insert Slug here">
                                </div>
                                {{-- Information --}}
                                <div class="form-group">
                                    <label for="information_sd_1">Information</label>
                                    @php
                                        $content = $item->couplet_text;
                                    @endphp
                                    <textarea dir="rtl" disabled class="form-control informations-sd-{{ $i }}" rows="5">{{ old('information', $content) }}</textarea>
                                 
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
                <div class="card overlay-wrapper">
                    <!--= start[Main Information Card Overly] =-->
                        <div class="overlay couplets-overlay-card" style="display:none;">
                            <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                            <div class="text-bold pt-2 sr-only">Loading...</div>
                        </div>
                    <!--= end[Main Information Card Overly] =-->
                    <div class="card-header">Main Information {English Language}</div>
                    <div class="card-body">
                        @for ($i = 0; $i < count($couplets); $i++)
                            @if ($i > 0 && $i < count($couplets))
                                <hr style="background:#4caf4fa7;height:5px;">
                            @endif
                            <div class="couplet-en-content">
                                <!--= start[Couplet Tags] =-->
                                  
                                <div class="form-group">
                                    {{-- Tags field --}}
                                        @php
                                        $config_tags = [
                                            "placeholder" => "Select multiple options...",
                                            "allowClear" => true,
                                        ];
                                        @endphp
                                        <x-adminlte-select2 id="couplet_tags_{{ $i }}"  name="couplet_tags[]" label="Tags"
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

                                <!--= end[Couplet Tags] =-->
                                {{-- Information --}}
                                <div class="form-group pt-1">
                                    <label for="information_{{ $for_language }}_1">Information</label>
                                    <button type="button" onclick="convertText(this)" data-is-slug="0" data-sindhi-field="informations-{{ $for_language }}-{{ $i }}" data-roman-field="informations-{{ $for_language }}-{{ $i }}" data-field-id="informations-{{ $for_language }}-{{ $i }}" class="btn btn-xs btn-warning float-right mr-2">
                                        <i class="fas fa-sync-alt mr-1"></i> Generate Roman
                                    </button>
                                    <textarea name="couplet_text[]" autocomplete="off" spellcheck="off" style="font-size: 1.2rem;" id="informations-{{ $for_language }}-{{ $i }}" class="form-control informations-{{ $for_language }}-{{ $i }} @error('couplet_tex[]t') is-invalid @enderror" placeholder="Enter translation" rows="5">{{ old('couplet_text[]') }}</textarea>

                                    @error('couplet_text[]')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                        @endfor
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-sm btn-success" id="saveCoupletsOfPoetryBtn"><i class="fa fa-save mr-1"></i>Save</button>
                    </div>
                </div>
            </div>
            <!--= end[Translateable Content Col] =-->
        </div>
        <!---=======end[ Row 2 for Couplets ]=======---->
    </form>
@stop



@section('plugins.jqueryValidation', true)
@section('plugins.paceProgress', true)
@section('plugins.Select2', true)

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
                    //event.preventDefault();
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
                type: 'post',
                data: formData,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                beforeSend: function () {
                    button.attr('disabled', true);
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
        $('#storeCoupletsTranslationForm').validate({
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
        $('#storeCoupletsTranslationForm').submit(function (e) {
            var formData = $(this).serialize();
            var saveBtn = $(this).find('button[type="submit"]');
            $('.couplets-overlay-card').show();
            saveBtn.attr('disabled', true)
            e.preventDefault();
            /// Ajax Request for Add Couplets
            $.ajax({
                url:$(this).attr('action'),
                type:'post',
                data:formData,
                headers: {
                    'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function (){
                    console.log('beforeSend called on ajax request of Add Couplets');
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
                    console.error('error called on ajax request of Add Couplets')
                    console.error(xhr.status)
                    console.error(thrownError)
                    saveBtn.attr('disabled', false)
                }
            });
        })

    </script>
@stop