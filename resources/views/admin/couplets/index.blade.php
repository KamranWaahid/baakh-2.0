@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
<div class="row d-flex justify-content-between">
  <h1 class="m-0 text-dark col-6">Couplets</h1>
  <div class="form-group">
    <select id="lang" class="form-control">
      @foreach ($languages as $item)
        <option value="{{ $item->lang_code }}">{{ $item->lang_title }}</option>
      @endforeach
    </select>
  </div>
</div>
@stop

@section('content')
<!-- ========== Start Error Display ========== -->
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<!-- ========== End Error Display ========== -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Couplets</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.couplets.trash') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-trash"></i> View Trashed</a>
                        <a href="{{ route('admin.couplets.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Couplets</a>
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
      _delete('couplet', 'Couplet');
      


     $(document).on('click', '.btn-visible-poetry', function (e) {
        e.preventDefault();
        var button = $(this);
        var row = button.closest('tr');
        var itemId = button.data('id');
        var itemUrl = button.data('url');
        /// Ajax Request for Delete Couplets
        $.ajax({
          url: itemUrl,
          type:'PUT',
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
          },
          beforeSend: function (){
            /// do domthing
            button.attr('disabled', true)
            
          },
          success: function (response){
            button.attr('disabled', false)

            // change icon and data 
            if(response.visibility === 1){
              button.attr('title', 'Hide Couplets')
              button.html('<i class="fa fa-eye"></i>')
            }else{
              button.attr('title', 'Show Couplets')
              button.html('<i class="fa fa-eye-slash"></i>')
            }
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Delete Couplets')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Delete Couplets')
          }
        });
     }) 

     loadCouplets('sd')
     $(document).on('change', '#lang', function(){
          var l = $(this).find(':selected').val()    
          loadCouplets(l)
      })

      $('[data-toggle="tooltip"]').tooltip()
    });

    function loadCouplets(lang)
    {
      if ($.fn.DataTable.isDataTable('#coupletsTable')) {
          $('#coupletsTable').DataTable().destroy();
      }
      $('#coupletsTable').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
            url: '{{ route('admin.couplets.dataTableCouplets') }}',
              data: {
                  lang: lang,
              }
          },
          
          search: {regex: true},
          columns: [
              { data: 'id', name: 'id' }, // Replace 'id' with your actual column name
              { data: 'couplet_text', name: 'couplet_text' },
              { data: null, render: function (data, type, row) {
                  var information = '<span class="badge bg-success p-1 rounded" data-toggle="tooltip" data-placement="top" title="Poet Name"><i class="fa fa-user mr-1"></i>' + row.poet.details.poet_laqab + '</span>';
                  if (row.poetry_id != '0') {
                      information += ' <span class="badge bg-secondary p-1 rounded" data-toggle="tooltip" data-placement="top" title="Linked with Main Poetry"><i class="fas fa-link"></i></span>';
                  }
                  return information;
              }},
              { data: 'couplet_tags', name: 'couplet_tags' },
              { data: null, render: function (data, type, row) {
                  return '<span class="badge bg-warning p-1 rounded" data-toggle="tooltip" data-placement="top" title="Available in Language"><i class="fa fa-globe mr-1"></i>' + row.language.lang_title + '</span>';
              }},
              { data: 'actions', name: 'actions', orderable: false, searchable: false }
          ],
        columnDefs: [ { orderable: false, targets: [1,2,3,4,5] }],
        order: [[0, 'desc']],
        createdRow: function(row, data, dataIndex) {
            $(row).attr('id', data.id);
        }
      });
    }
  </script>
@endsection