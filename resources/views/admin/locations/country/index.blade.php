@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Countries</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Countries</h3>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Name</th>
                           <th>Information</th>
                           <th>Detail</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Name</th>
                             <th>Information</th>
                             <th>Detail</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                             @foreach ($countries as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->countryName }}</td>
                               
                              
                               <td>
                                <span class="badge bg-success rounded">{{ $data->Abbreviation }}</span>
                                <span class="badge bg-info rounded"><i class="fas fa-language"></i> {{ $data->lang }}</span>
                                <span class="badge bg-warning rounded"><i class="fa fa-globe mr-1"></i>{{ $data->Continent }}</span>
                               </td>
                               <td>{{ $data->countryDesc }}</td>
                               <td width="12%" class="text-center">
                                   <a href="{{ route('admin.countries.edit', $data->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                   <a href="{{ route('admin.countries.destroy', $data->id) }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></a>
                               </td>
                           </tr>
                           @endforeach
                         </tbody>
                     </table>
                </div>
            </div>
        </div>

        {{-- create form --}}
        <div class="col-5">
            <div class="card">
                <form action="{{ route('admin.countries.store') }}" method="post">
                    <div class="card-header">
                        <h3 class="card-title">Create New Country</h3>
                    </div>
                    <div class="card-body">
                        @csrf
                        <div class="row">
                            
                            {{-- Name field --}}
                            <div class="form-group col-8">
                                <label for="countryName">Country Name</label>
                                <input type="text" class="form-control  @error('countryName') is-invalid @enderror" value="{{ old('countryName') }}"  name="countryName" id="countryName" placeholder="Enter country Name">
                                
                                @error('countryName')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Name field --}}
                            <div class="form-group col-4">
                                <label for="cat_slug">Abbreviation</label>
                                <input type="text" class="form-control  @error('Abbreviation') is-invalid @enderror" value="{{ old('Abbreviation') }}"  name="Abbreviation" id="Abbreviation" placeholder="pk, uae">
                                
                                @error('Abbreviation')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    
                        <div class="row">
                            {{-- language --}}
                            <div class="form-group col-8">
                                <label for="lang">Language</label>
                                <select name="lang" id="lang" class="form-control select2 @error('lang') is-invalid @enderror">
                                    @foreach ($languages as $item)
                                        <option value="{{ $item->lang_code }}">{{ $item->lang_title }}</option>
                                    @endforeach
                                </select>
                                
                                @error('lang')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Continent field --}}
                            <div class="form-group col-4">
                                <label for="cat_slug">Continent</label>
                                <input type="text" class="form-control  @error('Continent') is-invalid @enderror" value="{{ old('Continent') }}"  name="Continent" id="Continent" placeholder="Continent of Country">
                                
                                @error('Continent')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                        </div>
                        <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

                        {{-- countryDesc --}}
                        <div class="form-group">
                            <label for="countryDesc">Country Desc</label>
                            <x-adminlte-textarea name="countryDesc" placeholder="Insert description..."/>
                            
                            @error('countryDesc')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>



                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('plugins.Datatables', true)

@section('js')
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