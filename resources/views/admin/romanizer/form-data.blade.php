{{-- sample data --}}
<form id="add_new_word_form{{ $key }}" data-key="{{ $key }}" class="col-6 p-2 rounded add_new_word_form" style="background: #b7c9c9;font-family: 'AMBILE'; font-size:30sp;"  method="post">
    <input type="hidden" name="user_id" value="{{ auth()->user()->id; }}">
    <div class="row">
        <div class="col-5">
            <input type="text" value="{{ $word }}" class="form-control add_word_sindhi_from_modal" disabled  dir="rtl">
            <input type="hidden" value="{{ $word }}" class="form-control add_word_sindhi_from_modal"  name="word_sd"  dir="rtl" placeholder="سنڌي لفظ" autocomplete="false">
        </div>
        <div class="col-5">
            <input type="text" class="form-control" name="word_roman" placeholder="Roman" id="word_roman_modal" autocomplete="false">
        </div>
        <div class="col-2">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-save"></i></button>
            <button type="button" class="btn btn-sm btn-danger btn-remove-form" data-form-id="add_new_word_form{{ $key }}" ><i class="fa fa-trash"></i></button>
        </div>
    </div>
</form>
{{-- sample data --}}