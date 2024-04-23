@extends('adminlte::page')

@section('title', 'Poets of Baakh')

@section('content_header')
    <h1 class="m-0 text-dark">Poets</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Web Poets</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.poets.trash') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-trash"></i> View Trashed</a>
                        <a href="{{ route('admin.poets.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Poet</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Full Name</th>
                           <th>Laqab</th>
                           <th>Information</th>
                           <th>Picture</th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                            <th>Sr #</th>
                            <th>Full Name</th>
                            <th>Laqab</th>
                            <th>Information</th>
                            <th>Picture</th>
                            <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                             @foreach ($poets as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->details->poet_name }}</td>
                               <td>{{ $data->details->poet_laqab }}</td>
                               <td>
                                <span class="p-1 rounded bg-info" data-toggle="tooltip" data-placement="top" title="Birth Date"><i class="fa fa-birthday-cake mr-1"></i>{{ date('d-M-Y', strtotime($data->date_of_birth)) }}</span>
                                @if ($data->date_of_death)
                                  <span class="p-1 rounded bg-danger" data-toggle="tooltip" data-placement="top" title="Died on"><i class="fa fa-birthday-cake mr-1"></i>{{ date('d-M-Y', strtotime($data->date_of_death)) }}</span>
                                @endif
                               </td>
                              
                               <td><img src="{{ asset($data->poet_pic) }}" width="80px" class="rounded-circle"></td>
                               <td width="12%" class="text-center">
                                   <a href="{{ route('admin.poets.edit', $data->id) }}"  data-toggle="tooltip" data-placement="top" title="Update Poet's Information"  class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.poets.toggle-visibility', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="{{ $data->visibility == 1 ? 'Hide' : 'Show' }} Poet" class="btn btn-xs btn-info btn-visible-poet"><i class="fa fa-{{ $data->visibility == 1 ? 'eye' : 'eye-slash' }}"></i></button>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.poets.toggle-featured', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="{{ $data->is_featured == 1 ? 'Hide' : 'Show' }} From Featured" class="btn btn-xs btn-default btn-featured-poet"><i class="{{ $data->is_featured == 1 ? 'fa fa-star text-warning' : 'fa fa-star' }}"></i></button>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.poets.destroy', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Poet" class="btn btn-xs btn-danger btn-delete-poet"><i class="fa fa-trash"></i></button>
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
      $('[data-toggle="tooltip"]').tooltip();

      
     /*
      * Delete function called
      * @param1 = last name of button class, like [sliders] of .btn-delete-sliders
      * @oaram2 = Title to display in the notification
      */
      _delete('poet', 'Poet')


     $(document).on('click', '.btn-visible-poet', function (e) {
        e.preventDefault();
        var button = $(this);
        var row = button.closest('tr');
        var itemId = button.data('id');
        var itemUrl = button.data('url');
        /// Ajax Request for Delete poet
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
              button.attr('title', 'Hide Poet')
              button.html('<i class="fa fa-eye"></i>')
            }else{
              button.attr('title', 'Show Poet')
              button.html('<i class="fa fa-eye-slash"></i>')
            }
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Delete Poet')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Delete Poet')
          }
        });
     })

     // featured
     $(document).on('click', '.btn-featured-poet', function (e) {
        e.preventDefault();
        var button = $(this);
        var row = button.closest('tr');
        var itemId = button.data('id');
        var itemUrl = button.data('url');
        /// Ajax Request for Favorite Poet
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
            if(response.featured === 1){
              button.attr('title', 'Hide From Featured')
              button.html('<i class="fa fa-star text-warning"></i>')
            }else{
              button.attr('title', 'Show From Featured')
              button.html('<i class="fa fa-star"></i>')
            }
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Featured Poet')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Featured Poet')
          }
        });
     })

});
  </script>
@endsection