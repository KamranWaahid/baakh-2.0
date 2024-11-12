@extends('adminlte::page')

@section('title', 'Doodles')

@section('content_header')
    <h1 class="m-0 text-dark">Doodles</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Baakh Doodles</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.doodles.trash') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-trash"></i> View Trashed</a>
                        <a href="{{ route('admin.doodles.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Slider</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Title</th>
                           <th>Information</th>
                           <th>Image</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Title</th>
                             <th>Information</th>
                             <th>Image</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                             @foreach ($doodles as $data)
                              <tr>
                                  <td>{{ $loop->index }}</td>
                                  <td>{{ $data->title }}</td>
                                  <td>
                                    Visibity: {{ $data->start_date }} to {{ $data->end_date }}
                                  </td>
                                  <td><img src="{{ asset($data->image) }}" width="100px" alt=""></td>
                                  <td width="12%" class="text-center">
                                      <a href="{{ route('admin.doodles.edit', $data->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                      <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.doodles.destroy', ['doodle' => $data]) }}" data-toggle="tooltip" data-placement="top" title="Delete Doodle" class="btn btn-xs btn-danger btn-delete-doodles"><i class="fa fa-trash"></i></button>
                                  </td>
                              </tr>
                           @endforeach
                         </tbody>
                     </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('plugins.Datatables', true)
@section('plugins.toastr', true)

 
@section('js')
<script>
    $(function () {
      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });


      /*
      * Delete function called
      * @param1 = last name of button class, like [sliders] of .btn-delete-sliders
      * @oaram2 = Title to display in the notification
      */
      _delete('doodles', 'Doodles')
      
    

    });
  </script>
@endsection