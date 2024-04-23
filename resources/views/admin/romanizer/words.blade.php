@extends('adminlte::page')

@section('title', 'AdminLTE')

@section('content_header')
    <div class="row d-flex justify-content-between">
        <h1 class="m-0 text-dark">Romanizer</h1>
        <div class="buttons">
            <a href="{{ route('admin.romanizer.index') }}" class="btn btn-sm btn-success mr-1"><i class="fa fa-language mr-2"></i> Roman Converter</a>
            <a href="{{ route('admin.romanizer.refresh-dictionary') }}" class="btn btn-sm btn-info mr-1"><i class="fa fa-undo mr-2"></i> Refresh Dictionary</a>
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
                    <h3 class="card-title">Romanizer Words</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.romanizer.words-trashed') }}" class="btn btn-sm btn-warning"><i class="fa fa-trash mr-2"></i> View Trashed</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="wordTable" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                            <th>Sr #</th>
                            <th>Word</th>
                            <th>Roman</th>
                            <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                            <th>Sr #</th>
                            <th>Word</th>
                            <th>Roman</th>
                            <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody id="words-table-body">
                            
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
<script>
    $(function () {
      $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
      });

      /*
      * Get Words from DB
      */
    $(document).ready(function() {
        $('#wordTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.romanizer.data') !!}',
            columns: [
                { data: 'id', name: 'id', searchable: false }, // Add this line
                { data: 'word_sd', name: 'word_sd' },
                { data: 'word_roman', name: 'word_roman' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }, // Add this line
            ],
            createdRow: function (row, data, dataIndex, cell) {
                console.log("cell == " + cell[1].innerHTML);
                $(cell[1]).attr('data-field-name', 'word_sd')
                $(cell[1]).attr('onkeypress', 'saveWord(this)')
                $(cell[1]).attr('ondblclick', 'editWord(this)')
                $(cell[1]).attr('data-word-id', data.id)
                $(cell[1]).attr('data-url', '{{ route('admin.romanizer.edit') }}')

                $(cell[2]).attr('data-field-name', 'word_roman')
                $(cell[2]).attr('onkeypress', 'saveWord(this)')
                $(cell[2]).attr('ondblclick', 'editWord(this)')
                $(cell[2]).attr('data-word-id', data.id)
                $(cell[2]).attr('data-url', '{{ route('admin.romanizer.edit') }}')
            }
        });
        _delete('word', 'Word');
    });
     
    });

    function editWord(e) {
          $(e).addClass('bg-editable')
          var col_name = $(e).data('field-name')
          var word_id = $(e).data('word-id')
          var currentTD = $(e);
          $.each(currentTD, function () {
              $(this).prop('contenteditable', true)
              $(this).focus()
          });
      }

      function saveWord(e) {
          var keycode = (event.keyCode ? event.keyCode : event.which);
          if(keycode == 13){
            $(e).prop('contenteditable', false)
            var col_name = $(e).data('field-name')
            var word_id = $(e).data('word-id');
            var col_data = $(e).text();
            var route = $(e).data('url')
            
            // ajax call to save data
            $.ajax({
                url: route,
                method: "POST",
                data: {
                    column:col_name,
                    tvalues:col_data,
                    id:word_id
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
                },
                beforeSend: function (){
                    /// do something
                },
                success: function(response){
                  if(response.type =='error'){ 
                    $(e).prop('contenteditable', true)
                    toastr.error(response.message)
                    $(e).focus()
                  }else{
                    toastr.success(response.message)
                    $(e).removeClass('bg-editable')
                  }

                }
            });
          }
        }

      
     


  </script>
@endsection