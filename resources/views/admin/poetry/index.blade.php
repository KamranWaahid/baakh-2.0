@extends('adminlte::page')

@section('title', 'Poetry')

@section('content_header')
    <div class="row d-flex justify-content-between">
      <h1 class="m-0 text-dark col-6">Poetry</h1>
      <div class="row langButton col-6">
        <div class="col-4">
          <select id="cat_id" class="form-control">
            <option value="0">Select Category</option>
            @foreach ($categories as $item)
              <option value="{{ $item->cat_id }}">{{ $item->cat_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-8">
          <select id="poets" class="form-control select2">
            <option value="0">Select Poet</option>
            @foreach ($poets as $item)
                <option value="{{ $item->id }}">{{ $item->details->poet_laqab }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Poetry</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.poetry.trash') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-trash"></i> View Trashed</a>
                        <a href="{{ route('admin.poetry.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Poetry</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>ID</th>
                           <th>Uploaded By</th>
                           <th>Title</th>
                           <th>Information</th>
                           <th>Poets</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>ID</th>
                             <th>Uploaded By</th>
                             <th>Title</th>
                            <th>Information</th>
                            <th>Poets</th>
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

@section('css')
  <style>
    .text-linked {
      color: #000000be;
    }
  </style>
@endsection

@section('js')

<script>
    $(function () {
       
      _delete('poetry', 'Poetry');
      _featured('poetry', 'Poetry');
      


     $(document).on('click', '.btn-visible-poetry', function (e) {
        e.preventDefault();
        var button = $(this);
        var row = button.closest('tr');
        var itemId = button.data('id');
        var itemUrl = button.data('url');
        /// Ajax Request for Delete Poetry
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
              button.attr('title', 'Hide Poetry')
              button.html('<i class="fa fa-eye"></i>')
            }else{
              button.attr('title', 'Show Poetry')
              button.html('<i class="fa fa-eye-slash"></i>')
            }
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Delete Poetry')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Delete Poetry')
          }
        });
     })

      $('[data-toggle="tooltip"]').tooltip()
    });
  </script>

<script>
  $(document).ready(function() {
        loadDataTable('0', '0')
        $(document).on('change', '#cat_id', function(){
          var l = $(this).find(':selected').val();
          var p = $('#poets').find(':selected').val()
          loadDataTable(l,p);
        })

        $(document).on('change', '#poets', function(){
          var l = $('#cat_id').find(':selected').val();
          var p = $(this).find(':selected').val()
          loadDataTable(l,p);
        });
    });


    function loadDataTable(cat, poet)
    {
      if ($.fn.DataTable.isDataTable('#example1')) {
          $('#example1').DataTable().destroy();
      } 

      $('#example1').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('admin.poetry.datatable') }}',
                data: {
                    cat_id: cat,
                    poet_id: poet
                }
            },
            columns: [
                { "data": "id", searchable: false},
                { data: 'user_info', name: 'Uploaded By', searchable: false, orderable: false}, // Uploaded By is table's th
                { data: 'poetry_title', name: 'poetry_title', searchable: false, orderable: false},
                { data: 'information', name: 'information', searchable: false, orderable: false},
                { data: 'poets', name: 'poets', searchable: false, orderable: false},
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
            ],
            order: [[0, 'desc']]
      });
    }

    
</script>
@endsection