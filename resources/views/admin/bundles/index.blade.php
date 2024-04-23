@extends('adminlte::page')

@section('title', 'Bundles')

@section('content_header')
    <h1 class="m-0 text-dark">Bundles</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Web Bundles</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.bundle.trash') }}" class="btn btn-sm btn-warning float-right mr-1"><i class="fa fa-trash"></i> View Trashed</a>
                        <a href="{{ route('admin.bundle.create') }}" class="btn btn-sm btn-success mr-1" ><i class="fa fa-plus mr-1"></i> New Bundle</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                           <th>Sr #</th>
                           <th>Title</th>
                           <th>Image</th>
                           <th>
                            @foreach ($languages as $item)
                              | {{ $item->lang_title }} |
                            @endforeach
                           </th>
                           <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                             <th>Sr #</th>
                             <th>Title</th>
                             <th>Image</th>
                             <th>
                              @foreach ($languages as $item)
                                | {{ $item->lang_title }} |
                              @endforeach
                             </th>
                             <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody>
                          @foreach ($bundles as $key => $data)
                                 
                           <tr>
                               <td><?php echo $key+1; ?></td>
                              
                               <td>{{ $data->title }}</td>
                              
                               <td><img src="{{ asset($data->bundle_thumbnail) }}" width="100px" alt=""></td>
                               <td>
                                  @foreach($languages as $language)
                                    @php
                                        $hasTranslation = $data->translations->where('lang_code', $language->lang_code)->isNotEmpty();
                                        $editRoute = route('admin.bundle.edit', ['id' => $data->id, 'lang' => $language->lang_code]);
                                    @endphp
                                    @if ($language->is_default == '0')
                                        ||
                                        <a href="{{ $editRoute }}" class="btn btn-xs btn-warning">
                                            <i class="fa {{ $hasTranslation ? 'fa-edit' : 'fa-plus' }}"></i> {{ $language->lang_code }}
                                        </a>
                                    @else
                                        <a href="{{ route('admin.bundle.edit', ['id' => $data->id]) }}" class="btn btn-xs btn-primary">
                                          <i class="fa fa-check"></i> {{ $language->lang_code }}
                                      </a>
                                    @endif
                                  @endforeach              
                               </td>
                               <td width="12%" class="text-center">
                                   <a href="{{ route('admin.bundle.edit', $data->id) }}"  data-toggle="tooltip" data-placement="top" title="Update Poetry"  class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.bundle.toggle-visibility', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="{{ $data->is_visible == 1 ? 'Hide' : 'Show' }} Bundle" class="btn btn-xs btn-info btn-visible-bundle"><i class="fa fa-{{ $data->is_visible == 1 ? 'eye' : 'eye-slash' }}"></i></button>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.bundle.toggle-featured', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="{{ $data->is_featured == 1 ? 'Hide' : 'Show' }} From Featured" class="btn btn-xs btn-default btn-featured-bundle"><i class="{{ $data->is_featured == 1 ? 'fa fa-star text-warning' : 'fa fa-star' }}"></i></button>
                                   <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.bundle.destroy', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Bundle" class="btn btn-xs btn-danger btn-delete-bundle"><i class="fa fa-trash"></i></button>
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

      _delete('bundle', 'Bundle');
      
 

     $(document).on('click', '.btn-visible-bundle', function (e) {
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
              button.attr('title', 'Hide Bundle')
              button.html('<i class="fa fa-eye"></i>')
            }else{
              button.attr('title', 'Show Bundle')
              button.html('<i class="fa fa-eye-slash"></i>')
            }
            if(response.type === 'success'){
              toastr.success(response.message)
            }else{
              toastr.error(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Delete Bundle')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Delete Bundle')
          }
        });
     })

     $(document).on('click', '.btn-featured-bundle', function (e) {
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
            console.error('error called on ajax request of Featured Bundle')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Featured Bundle')
          }
        });
     })


    });
  </script>
@endsection