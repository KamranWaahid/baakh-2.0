@extends('adminlte::page')

@section('title', 'Deleted Sliders')

@section('content_header')
    <h1 class="m-0 text-dark">Sliders</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Web Sliders</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.sliders.index') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-list"></i> View Available</a>
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
                             @foreach ($sliders as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->title }}</td>
                               <td>
                                <span class="badge badge-primary">{{ $data->language->lang_title }}</span>
                               </td>
                              
                               <td><img src="{{ asset($data->image) }}" width="100px" alt=""></td>
                               <td width="12%" class="text-center">

                                <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.sliders.restore', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Rollback Slider" class="btn btn-xs btn-info btn-rollback-sliders"><i class="fa fa-undo"></i></button>
                                <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.sliders.hard-delete', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Permanently Delete Slider" class="btn btn-xs btn-danger btn-delete-sliders"><i class="fa fa-trash"></i></button>

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

      _delete('sliders', 'Sliders', true);
      _restore('sliders', 'Sliders');
      

    });
  </script>
@endsection