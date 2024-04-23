@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Deleted Bundles</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Deleted Bundles</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.bundle.index') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-list mr-2"></i> View Available</a>
                        <a href="{{ route('admin.bundle.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Bundle</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Title</th>
                           <th>Image</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Title</th>
                             <th>Image</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                             @foreach ($bundles as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->title }}</td>
                              
                               <td><img src="{{ asset($data->bundle_thumbnail) }}" width="100px" alt=""></td>
                               <td width="12%" class="text-center">
                                <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.bundle.restore', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Rollback Bundle" class="btn btn-xs btn-info btn-rollback-bundle"><i class="fa fa-undo"></i></button>
                                <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.bundle.hard-delete', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Bundle" class="btn btn-xs btn-danger btn-delete-bundle"><i class="fa fa-trash"></i></button>
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


@section('css')
  <link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
@endsection

@section('js')
<script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
<script>
    $(function () {
      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });

      _delete('bundle', 'Bundle');
      _restore('bundle', 'Bundle');

    });
  </script>
@endsection