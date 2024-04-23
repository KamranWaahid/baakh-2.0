@extends('adminlte::page')

@section('title', 'Tags')

@section('content_header')
    <div class="row d-flex justify-content-between">
        <h1 class="m-0 text-dark">Tags</h1>
        <div class="form-group">
            <select name="language" class="form-control" id="language">
                @foreach ($languages as $item)
                    <option value="{{ $item->lang_code }}">{{ $item->lang_title }}</option>
                @endforeach
            </select>
        </div>
    </div>
@stop
 
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Tags</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.tags.create') }}" class="btn btn-sm btn-success"><i class="fa fa-plus mr-2"></i> Add New Tags</a>
                        <a href="{{ route('admin.tags.trashed') }}" class="btn btn-sm btn-warning"><i class="fa fa-trash mr-2"></i> View Trashed Tags</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tagsTable" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                            <th>Sr #</th>
                            <th>Tag</th>
                            <th>Slug</th>
                            <th>Type</th>
                            <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                            <th>Sr #</th>
                            <th>Tag</th>
                            <th>Slug</th>
                            <th>Type</th>
                            <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody id="tags-body">
                           
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
       
      _delete('tag', 'Tag');
      loadDataTables('sd', '{{ route('admin.tags.data-table') }}');

      $('#language').change(function () {
        var lang = $(this).find(':selected').val();
        loadDataTables(lang, '{{ route('admin.tags.data-table') }}')
      })
});


function loadDataTables(lang, routeUrl)
{
    if ($.fn.DataTable.isDataTable('#tagsTable')) 
    {
          $('#tagsTable').DataTable().destroy();
    }
    $('#tagsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: routeUrl,
            data: {
                lang: lang
            }
        },
        columns: [
            { data: "id", searchable: false},
            { data: 'tag', orderable: true, searchable: true },
            { data: 'slug',  orderable: true, searchable: true },
            { data: 'type',  orderable: true, searchable: true },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        createdRow: function(row, data, dataIndex) {
            $(row).attr('id', data.id);
            
        }
    });
}

  </script>
@endsection