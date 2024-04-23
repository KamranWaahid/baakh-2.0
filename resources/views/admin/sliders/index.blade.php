@extends('adminlte::page')

@section('title', 'Sliders')

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
                        <a href="{{ route('admin.sliders.trash') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-trash"></i> View Trashed</a>
                        <a href="{{ route('admin.sliders.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Slider</a>
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
                                   <a href="{{ route('admin.sliders.edit', $data->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.sliders.toggle-visibility', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="{{ $data->visibility == 1 ? 'Hide' : 'Show' }} Slider" class="btn btn-xs btn-info btn-visible-sliders"><i class="fa fa-{{ $data->visibility == 1 ? 'eye' : 'eye-slash' }}"></i></button>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.sliders.destroy', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Slider" class="btn btn-xs btn-danger btn-delete-sliders"><i class="fa fa-trash"></i></button>
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


      /*
      * Delete function called
      * @param1 = last name of button class, like [sliders] of .btn-delete-sliders
      * @oaram2 = Title to display in the notification
      */
      _delete('sliders', 'Sliders')
      
      

     $(document).on('click', '.btn-visible-sliders', function (e) {
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
              button.attr('title', 'Hide slider')
              button.html('<i class="fa fa-eye"></i>')
            }else{
              button.attr('title', 'Show slider')
              button.html('<i class="fa fa-eye-slash"></i>')
            }
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Delete slider')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Delete slider')
          }
        });
     })


    });
  </script>
@endsection