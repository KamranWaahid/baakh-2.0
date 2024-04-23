@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <h1 class="m-0 text-dark">Couplets</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Couplets</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.couplets.index') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-list"></i> View All Available</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="coupletsTable" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th width="10%">Sr #</th>
                           <th width="40%">Text</th>
                           <th>Information</th>
                           <th>Tags</th>
                           <th width="15%">Languages</th>
                           <th width="10%">Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Text</th>
                            <th>Information</th>
                            <th>Tags</th>
                            <th>Languages</th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                            @foreach ($couplets as $key => $data)
            
                            <tr id="{{ $key+1 }}">
                                <td><?php echo $key+1; ?></td>
                                
                                <td class="text-center" dir="{{ $data->language->lang_dir }}">
                                {!! nl2br($data->couplet_text) !!}
                                </td>
                                <td>
                                <span class="badge bg-success p-1 rounded" data-toggle="tooltip" data-placement="top" title="Poet Name"><i class="fa fa-user mr-1"></i>{{ $data->poet->details->poet_laqab }}</span>
                                @if ($data->poetry_id != '0')
                                    <span class="badge bg-secondary p-1 rounded"  data-toggle="tooltip" data-placement="top" title="Linked with Main Poetry" ><i class="fas fa-link"></i></span>
                                @endif
                                </td>
                                <td>{{ $data->couplet_tags }}</td>
                                <td>
                                <span class="badge bg-warning p-1 rounded" data-toggle="tooltip" data-placement="top" title="Available in Language"><i class="fa fa-globe mr-1"></i>{{ $data->language->lang_title }}</span>
                                </td>
                                
                                
                                <td width="12%" class="text-center">
                                    <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.couplets.restore', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Rollback Couplets" class="btn btn-xs btn-info btn-rollback-couplet"><i class="fa fa-undo"></i></button>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.couplets.hard-delete', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Couplets" class="btn btn-xs btn-danger btn-delete-couplet"><i class="fa fa-trash"></i></button>
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
        $('[data-toggle="tooltip"]').tooltip()

      /**
       * Ajax Methods to delete and Show Hide Couplets Main
       * @b_url = base url of the domain with admin
      */
      _delete('couplet', 'Couplet', true);
      _restore('couplet', 'Couplet');
 

      $("#coupletsTable").DataTable({
        "responsive": true,
        "autoWidth": false,
      }); 

      $('[data-toggle="tooltip"]').tooltip()
    });
  </script>
@endsection