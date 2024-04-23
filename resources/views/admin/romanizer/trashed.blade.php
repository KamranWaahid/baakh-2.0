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
                    <h3 class="card-title">Trashed Words</h3>
                    <div class="float-right">
                        <a href="{{ route('admin.romanizer.words') }}" class="btn btn-sm btn-warning"><i class="fa fa-list mr-2"></i> View Available</a>
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
<script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>
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
            ajax: '{!! route('admin.romanizer.data-trashed') !!}',
            columns: [
                { data: 'id', name: 'id', searchable: false }, // Add this line
                { data: 'word_sd', name: 'word_sd' },
                { data: 'word_roman', name: 'word_roman' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }, // Add this line
            ]
        });
    });

    _delete('word', 'Word', true);
    _restore('word', 'Word');
    
     
});

  </script>
@endsection