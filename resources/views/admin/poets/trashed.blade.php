@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Poets</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Web Poets</h3>
                    <div class="float-right">
                      <a href="{{ route('admin.poets.index') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-eye"></i> View Available</a>
                        <a href="{{ route('admin.poets.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Poet</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Full Name</th>
                           <th>Laqab</th>
                           <th>Information</th>
                           <th>Picture</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                            <th>Sr #</th>
                            <th>Full Name</th>
                            <th>Laqab</th>
                            <th>Information</th>
                            <th>Picture</th>
                            <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                             @foreach ($poets as $key => $data)
                                 
                           <tr style="background:#ff000033">
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->details->poet_name }}</td>
                               <td>{{ $data->details->poet_laqab }}</td>
                               <td>
                                <span class="p-1 rounded bg-info" data-toggle="tooltip" data-placement="top" title="Birth Date"><i class="fa fa-birthday-cake mr-1"></i>{{ date('d-M-Y', strtotime($data->date_of_birth)) }}</span>
                                @if ($data->date_of_death)
                                  <span class="p-1 rounded bg-danger" data-toggle="tooltip" data-placement="top" title="Died on"><i class="fa fa-birthday-cake mr-1"></i>{{ date('d-M-Y', strtotime($data->date_of_death)) }}</span>
                                @endif
                               </td>
                              
                               <td><img src="{{ asset($data->poet_pic) }}" width="100px" alt=""></td>
                               <td width="12%" class="text-center">
                                <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.poets.restore', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Rollback Poet" class="btn btn-xs btn-info btn-rollback-poet"><i class="fa fa-undo"></i></button>
                                <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.poets.hard-delete', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Permanent Delete Poet" class="btn btn-xs btn-danger btn-delete-poet"><i class="fa fa-trash"></i></button>
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
  $('[data-toggle="tooltip"]').tooltip();

      _delete('poet', 'Poet', true);
      _restore('poet', 'Poet');
 

});
  </script>
@endsection