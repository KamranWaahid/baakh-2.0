@extends('adminlte::page')

@section('title', 'Add New Poet')

@section('content_header')
    <h1 class="m-0 text-dark">Add Poet</h1>
    <div class="row">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    </div>
@endsection


@section('plugins.select2', true)


@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('admin.poets.store') }}" method="post" id="addPoetForm" enctype="multipart/form-data">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">Poets</h3>
                    </div>
                    <div class="card-body">

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-6">
                                    <label for="title">Poet Slug</label>
                                    <input type="text" class="form-control  @error('poet_slug') is-invalid @enderror" value="{{ old('poet_slug') }}"  name="poet_slug" id="poet_slug" placeholder="Enter Poet's Slug or name without space">
                                    
                                    @error('poet_slug')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            
                                <div class="col-6">
                                    {{-- With multiple slots, and plugin config parameter --}}
                                    @php
                                    $config = [
                                        "placeholder" => "Select multiple options...",
                                        "allowClear" => true,
                                    ];
                                    @endphp
                                    <x-adminlte-select2 id="poet_tags" name="poet_tags[]" label="Tags"
                                    label-class="text-danger" igroup-size="sm" :config="$config" multiple>
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
                            </div> {{-- /.row1 --}}

                            {{-- row2 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-4">
                                    <label for="date_of_birth">Birth Date</label>
                                    <input type="date" class="form-control  @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}"  name="date_of_birth" id="date_of_birth">
                                    
                                    @error('date_of_birth')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                {{-- Name field --}}
                                <div class="form-group col-4">
                                    <label for="date_of_death">Death Date</label>
                                    <input type="date" class="form-control  @error('date_of_death') is-invalid @enderror" value="{{ old('date_of_death') }}"  name="date_of_death" id="date_of_death">
                                    
                                    @error('date_of_death')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group col-4">
                                    <x-adminlte-input-file name="image" label="Upload file" placeholder="Choose a file..." disable-feedback/>
                                    @error('image')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- ========== Start Other Section for Dynamic Values ========== -->
                            <div class="d-flex justify-content-between bg-secondary p-1 rounded">
                                <div class="info">
                                    <h3>Poet Details</h3>    
                                    <small>Fill data as per selected language</small>
                                </div>
                                <button type="button" class="btn btn-default addMoreRow" onclick="addRow()"><i class="fa fa-plus"></i></button>
                            </div>

                            <div class="dynamic_details mt-2" id="dynamic_details">
                                
                            </div>
                            
                            <!-- ========== End Other Section for Dynamic Values ========== -->
 
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('admin.poets.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
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
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script src="{{ asset('vendor/summernote/summernote-bs4.min.js') }}"></script>

<script>
    $(function () {
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


        /**
         * Validate Form
         * Before Submit check every required field is filled
        */
         
    });

    function new_row(number) {
        var html = '<div class="row p-2 rounded mt-3" data-cusid="'+number+'" id="dyn_row'+number+'" style="background:#d1e9e4;">'+
                        '<div class="form-group col-6">'+
                        '<label for="poet_name_'+number+'">Name</label>'+
                        '<input type="text" class="form-control"  name="poet_name[]" id="poet_name_'+number+'" placeholder="Enter Poet Name" required>'+
                        '</div>'+
                        '<div class="form-group col-6">'+
                        '<label for="poet_laqab_'+number+'">Laqab</label>'+
                        '<input type="text" class="form-control"  name="poet_laqab[]" id="poet_laqab_'+number+'" placeholder="Enter Laqab" reqiured>'+
                        '</div>'+
                        '<div class="form-group col-6">'+
                        '<label for="pen_name_'+number+'">Pen Name (Takhalus)</label>'+
                        '<small>Must be seprated with comma if more than 1</label>'+
                        '<input type="text" class="form-control"  name="pen_name[]" id="pen_name_'+number+'" placeholder="Enter Takhalus">'+
                        '</div>'+
                        '<div class="form-group col-6">'+
                        '<label for="tagline_'+number+'">Tagline</label>'+
                        '<input type="text" class="form-control"  name="tagline[]" id="tagline_'+number+'" placeholder="Enter Tagline">'+
                        '</div>'+
                        '<div class="form-group col-2">'+
                        '<label for="lang_'+number+'">Language</label>'+
                        '<select name="lang[]" id="lang_'+number+'" data-id="'+number+'" class="form-control changeLanguage" reqiured>'+
                        '<option value="">Select Language</option>'+
                        '<option value="sd">Sindhi</option>'+
                        '<option value="en">English</option>'+
                        '<option value="ro">Roman</option>'+
                        '<option value="ur">Urdu</option>'+
                        '</select>'+
                        '</div>'+
                        '<div class="form-group col-5">'+
                        '<label for="birth_place_'+number+'">Birth Place</label>'+
                        '<select name="birth_place[]" id="birth_place_'+number+'" class="form-control select2">'+
                        '<option value="">Choose City</option>'+
                        '</select>'+
                        '</div>'+
                        '<div class="form-group col-5">'+
                        '<label for="death_place_'+number+'">Death Place</label>'+
                        '<select name="death_place[]" id="death_place_'+number+'" class="form-control select2">'+
                        '<option value="">Choose City</option>'+
                        '</select></div>'+
                        '<div class="form-group col-12"><label for="poet_bio_'+number+'">Details</label>'+
                        '<textarea class="textarea form-control" name="poet_bio[]" id="poet_bio_'+number+'" placeholder="Insert description..."></textarea>'+
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