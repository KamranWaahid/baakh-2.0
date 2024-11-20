@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Categories</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Categories</h3>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-list"></i> View Available</a>
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
                             <th>Languages</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                            @foreach ($categories as $data)
                                    
                            <tr>
                                <td>{{ $data->id }}</td>
                                
                                <td>{{ $data->category_name }}</td>
                                <td>
                                    <span class="badge @if ($data->is_featured) bg-warning @else bg-info @endif rounded"><i class="fa fa-star"></i></span>
                                    <span class="badge bg-primary p-1 rounded"><i class="fa fa-align-justify mr-1"></i>{{ $data->content_style }}</span>
                                </td>
                                    
                                <td>
                                    @foreach ($data->details as $item)
                                        @if (in_array($item->lang, array_keys($languages)))
                                            <span class="badge bg-success p-1 rounded"><i class="fa fa-globe mr-1"></i>{{ $languages[$item->lang] }}</span>
                                        @endif
                                        
                                    @endforeach
                                </td>
                                
                                <td width="12%" class="text-center">
                                    <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.categories.restore', $data->id) }}" data-toggle="tooltip" data-placement="top" title="Restore Category" class="btn btn-xs btn-primary btn-rollback-category"><i class="fa fa-undo"></i></button>
                                    <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.categories.destroy', $data->id) }}" data-toggle="tooltip" data-placement="top" title="Delete Category" class="btn btn-xs btn-danger btn-delete-category"><i class="fa fa-trash"></i></button>
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
<script src="{{ asset('vendor/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
<script>
    $(function () {
      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });
      _delete('category', 'Category', true);
      _restore('category', 'Category');
    });

    function removeRow(id) {
        $('#catrow_'+id).remove();
    }

  </script>
@endsection