@extends('adminlte::page')

@section('title', 'Edit Doodle')

@section('content_header')
    <h1 class="m-0 text-dark">Edit Doodle</h1>
@endsection


@section('plugins.bootstrapSwitch', true)



@section('content')
    <div class="row">
        {{ $doodle->start_date }}
        <div class="col-8 m-auto">
            <div class="card">
                <form action="{{ route('admin.doodles.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">baakh doodles</h3>
                        <div class="float-right">
                            <a href="{{ route('admin.doodles.trash') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-trash"></i> View Trashed</a>
                            <a href="{{ route('admin.doodles.index') }}" class="btn btn-sm btn-info mr-1" ><i class="fa fa-list mr-1"></i> Doodle lists</a>
                        </div>
                    </div>
                    <div class="card-body">

                            {{-- row #row1 --}}
                            <div class="row">
                                {{-- Name field --}}
                                <div class="form-group col-6">
                                    <label for="title">Doodle Title</label>
                                    <input type="text" class="form-control  @error('title') is-invalid @enderror" value="{{ old('title', $doodle->title) }}"  name="title" id="title" placeholder="Enter Slider's heading">
                                    
                                    @error('title')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                


                            
                                <div class="form-group col-6">
                                <label for="link_url">Link URL</label>
                                <input type="text" name="link_url" placeholder="URL of button"  value="{{ old('link_url', $doodle->link_url) }}"  class="form-control @error('link_url') is-invalid @enderror">
                                
                                @error('link_url')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div>

                            </div> {{-- /.row1 --}}

                            <div class="row" id="row2">
                                <!--= start[Start Date] =-->
                                <div class="form-group col-6">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" name="start_date" class="form-control input-start_date @error('start_date') is-invalid @enderror" value="{{ old('start_date',  \Carbon\Carbon::parse($doodle->start_date)->format('Y-m-d')) }}"  placeholder="Insert Start Date">
                                
                                    @error('start_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <!--= end[Start Date] =-->

                                <!--= start[End Date] =-->
                                <div class="form-group col-6">
                                    <label for="end_date">End Date</label>
                                    <input type="date" name="end_date" class="form-control input-end_date @error('end_date') is-invalid @enderror" value="{{ old('end_date', \Carbon\Carbon::parse($doodle->end_date))->format('Y-m-d') }}"  placeholder="Insert End Date">
                                
                                    @error('end_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <!--= end[End Date] =-->
                            </div>

                            {{-- row2 --}}
                            <div class="row">
                                <div class="form-group col-6">
                                    <x-adminlte-input-file name="image" label="Upload file" placeholder="Choose a file..." disable-feedback/>
                                    <a href="{{ asset($doodle->image) }}" target="_blank">View old image</a>
                                </div>
                                
                                <!--= start[Poets] =-->
                                <div class="form-group col-6">
                                    <label for="poet_id">Poets</label>
                                        <select name="poet_id" id="poet_id" class="form-control select2 @error('poet_id') is-invalid @enderror">
                                            <option value="">Select Poet</option>
                                            @foreach ($poets as $item)
                                                <option value="{{ $item->id }}" @selected($doodle->reference_id == $item->id) >{{ $item->poet_laqab }}</option>
                                            @endforeach
                                        </select>
                                
                                    @error('poet_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <!--= end[Poets] =-->
                            </div>
                            {{-- /.row2 --}}
                    </div>
 
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        <button type="button" onclick="window.location.href='{{ route('admin.doodles.index') }}'" class="btn btn-warning"><i class="fa fa-arrow-left"></i> Back</button>
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
<script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>

@endsection