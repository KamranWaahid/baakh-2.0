@extends('adminlte::page')

@section('title', 'Edit Slider')

@section('content_header')
    <h1 class="m-0 text-dark">Edit Sliders</h1>
@endsection

@section('plugins.bootstrapSwitch', true)



@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <form action="{{ route('admin.sliders.update', $slider->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <div class="card-header">
                        <h3 class="card-title">Web Sliders</h3>
                    </div>
                    <div class="card-body">

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-6">
                                    <label for="title">Slider Title</label>
                                    <input type="text" class="form-control  @error('title') is-invalid @enderror" value="{{ old('title', $slider->title) }}"  name="title" id="title" placeholder="Enter Slider's heading">
                                    
                                    @error('title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                


                            
                                <div class="form-group col-6">
                                <label for="link_url">Link URL</label>
                                <input type="text" name="link_url" placeholder="URL of button"  value="{{ old('link_url', $slider->link_url) }}"  class="form-control @error('link_url') is-invalid @enderror">
                                
                                @error('link_url')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div>

                            </div> {{-- /.row1 --}}

                            {{-- row2 --}}
                            <div class="row">
                                <div class="form-group col-6">
                                    <p class="float-right"><a href="{{ url($slider->image) }}" target="_blank">Click here to see current slider</a></p>
                                    <x-adminlte-input-file name="image" label="Upload file" placeholder="Choose a file..." disable-feedback/>
                                </div>
                                <div class="form-group col-3">
                                    @php
                                    $config = [
                                        "placeholder" => "Select language...",
                                        "allowClear" => true,
                                    ];
                                    @endphp
                                    <x-adminlte-select2 id="lang" name="lang" label="Language"
                                    label-class="text-info" igroup-size="sm" :config="$config">
                                        <x-slot name="prependSlot">
                                            <div class="input-group-text bg-gradient-blue">
                                                <i class="fas fa-language"></i>
                                            </div>
                                        </x-slot>
                                        
                                        @foreach ($languages as $item)
                                            <option value="{{ $item->lang_code }}" @if (old('lang', $slider->lang) == $item->lang_code) 
                                             selected 
                                         @endif>{{ $item->lang_title }}</option>
                                        @endforeach
                                    
                                    </x-adminlte-select2>
                                </div>
                                
                                <div class="col-3 switch">                
                                    {{-- With colors using data-* config --}}
                                    <label for="visibility">Visible on main slider?</label>
                                    <x-adminlte-input-switch name="visibility" data-label="Visiblity" data-on-color="success" data-off-color="danger" data-on-text="YES" data-off-text="NO" checked/>
                                </div>



                            </div>
                            {{-- /.row2 --}}
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('admin.sliders.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
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



@section('js')
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
<script>
    $(function () {
      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });
    });
  </script>
@endsection