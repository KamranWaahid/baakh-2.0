@extends('adminlte::page')

@section('title', 'Add New Admin')

@section('content_header')
    <h1 class="m-0 text-dark">
        <a href="{{ route('admin.admins') }}" class="btn">
            <i class="fa fa-chevron-left"></i>
        </a>
        <span>Add New Admin</span>
    </h1>
@stop

@section('content')
    <div class="row">
        <div class="col-6 m-auto">
            <div class="card">
                <form action="{{ route('admin.users.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        {{-- Image Field --}}
                        <div class="row d-flex flex-column justify-content-center align-items-center align-content-center">
                            <div class="image">
                                <img src="{{ asset('assets/img/placeholder290x293.jpg') }}" class="rounded-circle" id="displayImage" width="200px" alt=""> 
                            </div>
                            <div class="image-select mt-2">
                                <x-adminlte-input-file name="image"  placeholder="Choose a file..." disable-feedback/>
                                @error('image')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        {{-- Role field --}}
                        <div class="col-6 m-auto" id="user_role">
                            <label for="roles">Role</label>
                            <select name="roles" class="form-control" id="roles">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" data-role_name="{{ Str::lower($role->name) }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="roles_name" id="roles_name" value="admin">
                        </div>

                        {{-- Name field --}}
                        <div class="row mt-4">
                            <div class="col-12" data-for="name">
                                <div class="input-group mb-3">
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" placeholder="{{ __('adminlte::adminlte.full_name') }}" autofocus>
        
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                            <span class="fas fa-user {{ config('adminlte.classes_auth_icon', '') }}"></span>
                                        </div>
                                    </div>
        
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12" data-for="name_sd">
                                <div class="input-group mb-3">
                                    <input type="text" name="name_sd" class="form-control @error('name_sd') is-invalid @enderror"
                                        value="{{ old('name_sd') }}" placeholder="Full Name in Sindhi">
        
                                    <div class="input-group-append">
                                        <div class="input-group-text">
                                            <span class="fas fa-language"></span>
                                        </div>
                                    </div>
        
                                    @error('name_sd')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Email field --}}
                        <div class="input-group mb-3">
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" placeholder="{{ __('adminlte::adminlte.email') }}">

                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                                </div>
                            </div>

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Password field --}}
                        <div class="input-group mb-3">
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                placeholder="{{ __('adminlte::adminlte.password') }}">

                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                                </div>
                            </div>

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Confirm password field --}}
                        <div class="input-group mb-3">
                            <input type="password" name="password_confirmation"
                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                placeholder="{{ __('adminlte::adminlte.retype_password') }}">

                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                                </div>
                            </div>

                            @error('password_confirmation')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- WhatsApp field --}}
                        <div class="input-group mb-3">
                            <input type="number" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror"
                                value="{{ old('whatsapp') }}" placeholder="923150133533">

                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fab fa-whatsapp"></span>
                                </div>
                            </div>

                            @error('whatsapp')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success btn-block"><i class="fa fa-save"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop


@section('js')
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(document).ready(function () {
        // Get a reference to the file input and image elements
        var fileInput = $('#image');
        var displayImage = $('#displayImage');

        // Add an event listener to the file input to listen for changes
        fileInput.change(function () {
            // Check if a file has been selected
            if (fileInput[0].files && fileInput[0].files[0]) {
                // Create a FileReader object to read the selected file
                var reader = new FileReader();

                // Set up an event listener for when the FileReader has finished loading the file
                reader.onload = function (e) {
                    // Update the src attribute of the image element with the data URL of the selected image
                    displayImage.attr('src', e.target.result);
                };

                // Read the selected file as a data URL
                reader.readAsDataURL(fileInput[0].files[0]);
            } else {
                // If no file is selected, clear the src attribute of the image
                displayImage.attr('src', '');
            }
        });
    });
</script>

<script>
    $(function () {
        $('#roles').change(function () {
            var selectedRole = $(this).find(':selected').data('role_name');
            $('#roles_name').val(selectedRole)
        })
    })
</script>
@endsection