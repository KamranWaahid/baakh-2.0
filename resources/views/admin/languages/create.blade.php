@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Languages</h1>
@endsection

@section('plugins.bootstrapSwitch', true)



@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('languages.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">Languages</h3>
                    </div>
                    <div class="card-body">

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-6">
                                    <label for="lang_title">Name</label>
                                    <input type="text" class="form-control  @error('lang_title') is-invalid @enderror" value="{{ old('lang_title') }}"  name="lang_title" id="lang_title" placeholder="Enter Slider's heading">
                                    
                                    @error('lang_title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            
                                <div class="form-group col-6">
                                <label for="lang_code">Code</label>
                                <input type="text" name="lang_code" placeholder="Language Code [sd, en, ar]"  value="{{ old('lang_code') }}"  class="form-control @error('lang_code') is-invalid @enderror">
                                
                                @error('lang_code')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div>

                            </div> {{-- /.row1 --}}

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-6">
                                    <label for="lang_dir">Direction</label>
                                    <input type="text" class="form-control  @error('lang_dir') is-invalid @enderror" value="{{ old('lang_dir') }}"  name="lang_dir" id="lang_dir" placeholder="Layout direction [rtl, ltr]">
                                    
                                    @error('lang_dir')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            
                                <div class="form-group col-6">
                                <label for="lang_folder">Folder</label>
                                <input type="text" name="lang_folder" placeholder="Language Folder name"  value="{{ old('lang_folder') }}"  class="form-control @error('lang_folder') is-invalid @enderror">
                                
                                @error('lang_folder')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div>

                            </div> {{-- /.row1 --}}

                            
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('languages.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@section('js')
<script>
    $(function () {
      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });
    });
  </script>
@endsection