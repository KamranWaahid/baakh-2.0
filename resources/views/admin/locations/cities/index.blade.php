@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Cities</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cities</h3>
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
                             @foreach ($cities as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->city_name }}</td>
                               
                              
                               <td>
                                <span class="badge bg-success rounded">{{ $data->province->province_name }}</span>
                                <span class="badge bg-info rounded"><i class="fas fa-language"></i> {{ $data->lang }}</span>
                               </td>
                               <td width="12%" class="text-center">
                                   <a href="{{ route('admin.cities.edit', $data->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.cities.destroy', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete City" class="btn btn-xs btn-danger btn-delete-city"><i class="fa fa-trash"></i></button>
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
                <form action="{{ route('admin.cities.store') }}" method="post">
                    <div class="card-header">
                        <h3 class="card-title">Create New City</h3>
                    </div>
                    <div class="card-body">
                        @csrf

                        {{-- Country --}}
                        <div class="form-group">
                            <label for="country_id">Country</label>
                            <select name="country_id" id="country_id" class="form-control select2 @error('country_id') is-invalid @enderror">
                                <option value="">Select Country</option>
                                @foreach ($countries as $item)
                                    <option value="{{ $item->id }}">{{ $item->countryName. ' - ' .$item->lang }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Province --}}
                        <div class="form-group">
                            <label for="province_id">Province</label>
                            <select name="province_id" id="province_id" class="form-control select2 @error('province_id') is-invalid @enderror">
                                <option value="">Select Province</option>
                            </select>
                            
                            @error('province_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>


                         {{-- City field --}}
                         <div class="form-group">
                            <label for="city_name">City Name</label>
                            <input type="text" class="form-control  @error('city_name') is-invalid @enderror" value="{{ old('city_name') }}"  name="city_name" id="city_name" placeholder="Enter City Name">
                            
                            @error('city_name')
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
      var b_url = "{{ url('/admin/') }}";

      _delete('city', 'City');

       // ajax for province
       // Update provinces select option based on selected country
        $('#country_id').on('change', function() {
            var countryId = $(this).val();
            var provinceSelect = $('#province_id');
            
            provinceSelect.empty().append($('<option>', {
                value: '',
                text: 'Select Province'
            }));

            if (countryId) {
                $.get(b_url +'/getProvinces/' + countryId, function(data) {
                    $.each(data, function(key, value) {
                        provinceSelect.append($('<option>', {
                            value: value.id,
                            text: value.province_name + '-' + value.lang
                        }));
                    });
                });
            }
        });
    });
  </script>
@endsection