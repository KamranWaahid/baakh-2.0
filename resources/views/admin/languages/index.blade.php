@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Languages</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Languages</h3>
                    <div class="float-right">
                        <a href="{{ route('languages.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Language</a>
                    </div>
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
                             @foreach ($languages as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->lang_title }}</td>
                              
                               <td>
                                <span class="p-1 bg-info rounded"><i class="fa fa-folder"></i> {{ $data->lang_folder }}</span>
                                <span class="p-1 bg-warning rounded"><i class="fas fa-directions"></i> {{ $data->lang_dir }}</span>
                                <span class="p-1 bg-success rounded"><i class="fa fa-code"></i> {{ $data->lang_code }}</span>
                               </td>
                               <td width="12%" class="text-center">
                                   <a href="{{ route('admin.languages.edit', $data->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                   <a href="{{ route('admin.languages.destroy', $data) }}" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></a>
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