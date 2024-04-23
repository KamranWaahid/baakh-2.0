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
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Name</th>
                             <th>Information</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                             @foreach ($provinces as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->province_name }}</td>
                               
                              
                               <td>
                                <span class="badge bg-success rounded">{{ $data->country->countryName }}</span>
                                <span class="badge bg-info rounded"><i class="fas fa-language"></i> {{ $data->lang }}</span>
                               </td>

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
                <form action="{{ route('admin.provinces.store') }}" method="post">
                    <div class="card-header">
                        <h3 class="card-title">Create New Country</h3>
                    </div>
                    <div class="card-body">
                        @csrf
                        
                         {{-- Name field --}}
                         <div class="form-group">
                            <label for="province_name">Province Name</label>
                            <input type="text" class="form-control  @error('province_name') is-invalid @enderror" value="{{ old('province_name') }}"  name="province_name" id="province_name" placeholder="Province Name">
                            
                            @error('province_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- language --}}
                        <div class="form-group">
                            <label for="country_id">Country</label>
                            <select name="country_id" id="country_id" class="form-control select2 @error('country_id') is-invalid @enderror">
                                @foreach ($countries as $item)
                                    <option value="{{ $item->id }}">{{ $item->countryName. ' - ' .$item->lang }}</option>
                                @endforeach
                            </select>
                            
                            @error('lang')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- language --}}
                        <div class="form-group">
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
                    
                        
                        <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">




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