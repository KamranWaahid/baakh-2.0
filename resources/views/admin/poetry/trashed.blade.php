@extends('adminlte::page')

@section('title', 'Deleted Poetry')

@section('content_header')
    <h1 class="m-0 text-dark">Deleted Poetry</h1>
@stop


@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Poetry</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.poetry.index') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-eye"></i> View Available</a>
                        <a href="{{ route('admin.poetry.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Poetry</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Title</th>
                           <th>Information</th>
                           <th>Poet</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Title</th>
                            <th>Information</th>
                            <th>Poet</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                             @foreach ($poetry as $key => $data)
                                 
                           <tr id="{{ $key+1 }}" style="background:#ff000033">
                               <td><?php echo $data->id; ?></td>
                              
                               <td>{{ $data->poetry_title }}</td>
                               <td>
                                <span class="badge bg-info p-1 rounded"><i class="fa fa-folder mr-1"></i>{{ $data->category->cat_name }}</span>
                                <span class="badge bg-success p-1 rounded"><i class="fa fa-user mr-1"></i>{{  $data->poet->details->poet_name ?? '' }}</span>
                                <span class="badge bg-warning p-1 rounded"><i class="fa fa-globe mr-1"></i>{{ $data->lang }}</span>
                               </td>
                               <td>{{  $data->poet->details->poet_name ?? '' }}</td>
                              
                               
                               <td width="12%" class="text-center">
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.poetry.restore', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Rollback Poetry" class="btn btn-xs btn-info btn-rollback-poetry"><i class="fa fa-undo"></i></button>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.poetry.hard-delete', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Poetry" class="btn btn-xs btn-danger btn-delete-poetry"><i class="fa fa-trash"></i></button>
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
       
      

      /**
       * Ajax Methods to delete and Show Hide Poetry Main
       * @b_url = base url of the domain with admin
      */
      _delete('poetry', 'Poetry', true);
      _restore('poetry', 'Poetry');
 

      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });
      
      $('[data-toggle="tooltip"]').tooltip()
    });
  </script>
@endsection