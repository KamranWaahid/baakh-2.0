@extends('adminlte::page')

@section('title', 'Sindhi Romanizer')

@section('content_header')
    <div class="row d-flex justify-content-between">
        <h1 class="m-0 text-dark">Romanizer</h1>
        <div class="buttons">
            <a href="{{ route('admin.romanizer.words') }}" class="btn btn-sm btn-success mr-1"><i class="fa fa-list mr-2"></i> View All Words</a>
            <a href="{{ route('admin.romanizer.refresh-dictionary') }}" class="btn btn-sm btn-info mr-1"><i class="fa fa-undo mr-2"></i> Refresh Dictionary</a>
        </div>
    </div>
@stop

@section('content')
    
    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Input Content</h3>
                </div>
                <div class="card-body" style="font-family: 'AMBILE'; font-size:30sp;">
                    <textarea name="sindhi_text" spellcheck="false" dir="rtl" id="sindhi_text" class="form-control" rows="10"></textarea>
                </div>
                <div class="card-footer">
                    <button type="button" onclick="convertText(this)" style="display: none;" data-is-slug="0" data-url="{{ route('admin.romanizer.check-words') }}" data-is-slug="1" data-sindhi-field="sindhi_text" data-roman-field="paragraph" id="button_romanizer" class="btn btn-warning btn-block btn-check-words"><i class="fa fa-language mr-2"></i>Convert into Roman</button>
                    <button type="button" onclick="heySudhar(this)" data-button-show="button_romanizer" data-sindhi-field="sindhi_text" class="btn btn-info btn-block btn-hesudhar"><i class="fa fa-filter mr-2"></i>Process with Hey Sudhar</button>
                </div>
            </div>
        </div>

        {{-- output romanizing --}}
        <div class="col-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Output Roman</h3>
                </div>
                <div class="card-body">
                    <textarea name="paragraph" style="font-family: 'AMBILE'; font-size:30sp;" id="paragraph" class="form-control" rows="13" disabled></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== Start Add Words Modal ========== -->

    <!-- ========== Start Row for New Generated Words ========== -->
    <div class="row mt-2 mb-3">
        <div class="container-fluid" >
            <div class="row"  id="new-words-buttons">
                
            </div>
        </div>
    </div>
    <!-- ========== End Row for New Generated Words ========== -->
    
@endsection

@section('plugins.Datatables', true)
@section('plugins.toastr', true)
@section('css')
  <link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
@endsection

@section('js')
<script>
    var final_roman_dict_file = "{{ asset('vendor/roman-converter/all_words.dic') }}";
    var hesudhar_dict_file = "{{ asset('vendor/hesudhar/words.dic') }}";
</script>
<script src="{{ asset('vendor/roman-converter/sindhi_to_roman.js') }}" charset="UTF-8"></script>
<script src="{{ asset('vendor/hesudhar/hesudhar.js') }}"></script>
<script>
$(function () {
      $('[data-toggle="tooltip"]').tooltip();

      $(document).on('click', '.btn-remove-form', function (e) {
        e.preventDefault()
        
        var formId = $(this).data('form-id')
        
        $('#'+formId).remove();
      })

      /**
       * Ajax Methods to Checking Words
       * on click of [.btn-check-words] button
       * check from URL /romanizers/check-words
      */

     $(document).on('click', '.btn-check-words', function (e) {
        e.preventDefault();
        var button = $(this);
        var row = $('#new-words-buttons');
        var itemUrl = button.data('url');
        var content = $('#sindhi_text').val().replace(/\n/g, ' ');
        
        /// Ajax Request for Delete poet
        $.ajax({
          url: itemUrl,
          type:'PUT',
          data: {text: content},
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
            
            if(response.type === 'success'){
                row.html(response.html_content)
                toastr.success(response.message)
            }else{
              toastr.info(response.message)
            }
            
          },
            error: function (xhr, ajaxOptions, thrownError){
            console.error('error called on ajax request of Check Words')
            console.error(xhr.status)
            console.error(thrownError)
            toastr.error('error called on ajax request of Check Words')
          }
        });
     })

     /**
      * Ajax Method to add words
      * 
      * 
    */

    $(document).on('submit', '.add_new_word_form', function (e) {
        e.preventDefault();
        var formId = "#add_new_word_form" + $(this).data('key')
        var form_data = $(this).serialize();
        var post_url = "{{ route('admin.romanizer.store') }}"

        var submitButton = $(formId).find('.btn-submit-form')
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

                $('#new-words-buttons').find(formId).remove()
                
            },
            error: function (xhr, ajaxOptions, thrownError){
                console.error('error called on ajax request of Add New Word form')
                console.error(xhr.status)
                console.error(thrownError)
            }
        });
    })
     
});
 
  </script>
@endsection