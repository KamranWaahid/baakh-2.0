@extends('adminlte::page')

@section('title', 'Trashed Tags')

@section('content_header')
    <div class="row d-flex justify-content-between">
        <h1 class="m-0 text-dark">Trashed Tags</h1>
        <div class="buttons">
            <a href="{{ route('admin.tags.create') }}" class="btn btn-sm btn-success mr-1"><i class="fa fa-plus mr-2"></i> Add New Tag</a>
        </div>
    </div>
@stop
<style>
    .bg-editable{
        background-color:#D9F5FB;
        color:#000000;
    }
</style>
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Trashed Tags</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.tags.index') }}" class="btn btn-sm btn-warning"><i class="fa fa-list mr-2"></i> View Available Tags</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="wordTable" class="table table-bordered table-striped">
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
                            @foreach ($tags as $tag)
                                <tr>
                                  <td>{{ $tag->id }}</td>
                                  <td>{{ $tag->tag }}</td>
                                  <td>{{ $tag->slug }}</td>
                                  <td>{{ $tag->type }}</td>
                                  <td>
                                    <button type="button" data-id="{{ $tag->id }}" data-url="{{ route('admin.tags.restore', $tag->id) }}" data-toggle="tooltip" data-placement="top" title="Restore Tag" class="btn btn-sm btn-info btn-rollback-tag mr-2"><i class="fa fa-undo"></i></button>
                                    <button type="button" data-id="{{ $tag->id }}" data-url="{{ route('admin.tags.hard-delete', $tag->id) }}" data-toggle="tooltip" data-placement="top" title="Delete Tag" class="btn btn-sm btn-danger btn-delete-tag"><i class="fa fa-trash"></i></button>
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

       

    /**
     * Restore Tag
    */
    $(document).on('click', '.btn-rollback-tag', function () {
        var button = $(this);
        var row = button.closest('tr');
        var itemUrl = button.data('url')
        /// Ajax Request for Rollback Word
        $.ajax({
          url:itemUrl,
          type:'PUT',
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
          },
          beforeSend: function (){
            /// do domthing
            return confirm("Are you sure to Restore this?");
            button.attr('disabled', true)
          },
          success: function (response){
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            row.remove()
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Rollback Tag')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error(ajaxOptions.message + '<br>' + xhr.status)
          }
        });
    })

    /**
     * Hard Delete
    */
    $(document).on('click', '.btn-delete-tag', function (e) {
       e.preventDefault();
        var button = $(this);
        var row = button.closest('tr');
        var itemId = button.data('id');
        var itemUrl = button.data('url');
        /// Ajax Request for Delete Poetry
        $.ajax({
          url: itemUrl,
          type:'DELETE',
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
          },
          beforeSend: function (){
            /// do domthing
            return confirm("Are you sure to Delete this?");
            button.attr('disabled', true)
            
          },
          success: function (response){
            row.remove();
            
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Delete Tag')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Delete Tag')
          }
        });
    })
     
});

  </script>
@endsection