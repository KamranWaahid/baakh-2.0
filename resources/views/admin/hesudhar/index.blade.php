@extends('adminlte::page')

@section('title', 'Hesudhar')

@section('content_header')
    <div class="row d-flex justify-content-between">
        <h1 class="m-0 text-dark">Hesudhar</h1>
        <div class="buttons">
            <a href="{{ route('admin.hesudhar.refresh-file') }}" class="btn btn-sm btn-info mr-1"><i class="fa fa-undo mr-2"></i> Refresh Hesudhar File</a>
            <button type="button" class="btn btn-success btn-sm open-add-new-word-form"><i class="fa fa-plus mr-1"></i>Add New Word</button>
        </div>
    </div>
    <div class="container rounded" id="add-new-word-form-container" style="background: #dadada; display:none;">
        <form action="{{ route('admin.hesudhar.store') }}" id="add-new-word-form" method="post">
        @csrf
            <div class="row col-8 m-auto p-3">
                <!--= start[Word] =-->
                <div class="form-group col-6">
                    <label for="word">Word</label>
                    <input type="text" name="word" class="form-control input-word @error('word') is-invalid @enderror" value="{{ old('word') }}"  placeholder="Insert Word">
                
                    @error('word')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <!--= end[Word] =-->
                <!--= start[Correct Word] =-->
                <div class="form-group col-6">
                    <label for="correct">Correct Word</label>
                    <input type="text" name="correct" class="form-control input-correct @error('correct') is-invalid @enderror" value="{{ old('correct') }}"  placeholder="Insert Correct Word">
                
                    @error('correct')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <!--= end[Correct Word] =-->
                <div class="form-group col-12">
                    <button type="submit" class="btn btn-success btn-block btn-submit-form"><i class="fa fa-save mr-1"></i>Save</button>
                </div>
            </div>
        </form>
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
                    <h3 class="card-title">Hesudhar Words</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.hesudhar.words-trashed') }}" class="btn btn-sm btn-warning"><i class="fa fa-trash mr-2"></i> View Trashed</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="wordTable" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                            <th>Sr #</th>
                            <th>Word</th>
                            <th>Correct Word</th>
                            <th>Actions</th>
                           </tr>
                       </thead>
                         <tfoot>
                           <tr>
                            <th>Sr #</th>
                            <th>Word</th>
                            <th>Correct Word</th>
                            <th>Actions</th>
                           </tr>
                         </tfoot>
                         <tbody id="words-table-body" class="sd">
                            
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
            ajax: '{!! route('admin.hesudhar.data') !!}',
            columns: [
                { data: 'id', name: 'id', searchable: false }, // Add this line
                { data: 'word', name: 'word' },
                { data: 'correct', name: 'correct' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }, // Add this line
            ],
            createdRow: function (row, data, dataIndex, cell) {
                $(cell[1]).attr('data-field-name', 'word')
                $(cell[1]).attr('onkeypress', 'saveWord(this)')
                $(cell[1]).attr('ondblclick', 'editWord(this)')
                $(cell[1]).attr('data-word-id', data.id)
                $(cell[1]).attr('data-url', '{{ route('admin.hesudhar.edit') }}')

                $(cell[2]).attr('data-field-name', 'correct')
                $(cell[2]).attr('onkeypress', 'saveWord(this)')
                $(cell[2]).attr('ondblclick', 'editWord(this)')
                $(cell[2]).attr('data-word-id', data.id)
                $(cell[2]).attr('data-url', '{{ route('admin.hesudhar.edit') }}')
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


    $(document).on('click', '.open-add-new-word-form', function(){
        $(this).find('i').toggleClass('fa-minus fa-plus');
        $('#add-new-word-form-container').toggle();
    })
    $('form#add-new-word-form').submit(function (e) {
        e.preventDefault();
        
    })

    $(document).on('submit', '#add-new-word-form', function (e) {
        e.preventDefault();
        var formId = "#add-new-word-form" + $(this).data('key')
        var form_data = $(this).serialize();
        var post_url = $(this).attr('action');

        var submitButton = $(this).find('.btn-submit-form')
        /// Ajax Request for Add New Word form
        $.ajax({
            url:post_url,
            type:'POST',
            data:form_data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If you're using CSRF protection
            },
            beforeSend: function (){
                /// do domthing
                submitButton.attr('disabled', true);
            },
            success: function (response){
                
                toastr.success(response.message)
                submitButton.attr('disabled', true);
                
            },
            error: function (xhr, ajaxOptions, thrownError){
                console.error('error called on ajax request of Add New Word form')
                console.error(xhr.status)
                console.error(thrownError)
            }
        });
    })
      
     


  </script>
@endsection