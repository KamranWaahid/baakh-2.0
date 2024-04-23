@if ($errors->any())
    
@php
    $poetLaqabCount = count(request()->input('poet_name', []));
@endphp

<h1>{{ $poetLaqabCount }} submits</h1>

@for ($itemCounter = 0; $itemCounter < $poetLaqabCount; $itemCounter++)
<div class="row p-2 rounded mt-3" data-cusid="{{ $itemCounter }}" id="dyn_row{{ $itemCounter }}" style="background:#d1e9e4;">
    <div class="form-group col-6">
        <label for="poet_name_{{ $itemCounter }}">Name</label>
        <input type="text" class="form-control" name="poet_name[]" value="{{ old('poet_name[]') }}" id="poet_name_{{ $itemCounter }}" placeholder="Enter Poet Name">
    </div>
    <div class="form-group col-6">
        <label for="poet_laqab_{{ $itemCounter }}">Laqab</label>
        <input type="text" class="form-control" name="poet_laqab[]" value="{{ old('poet_laqab[]') }}" id="poet_laqab_{{ $itemCounter }}" placeholder="Enter Laqab">
    </div>
    <div class="form-group col-2">
        <label for="lang_{{ $itemCounter }}">Language</label>
        <select name="lang[]" id="lang_{{ $itemCounter }}" data-id="{{ $itemCounter }}" class="form-control changeLanguage">
            @foreach ($languages as $lang)
                <option value="{{ $lang->lang_code }}" @if ($poet->lang == $lang->lang_code) 
                    selected 
                @endif>{{ $lang->lang_title }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-5">
        <label for="birth_place_{{ $itemCounter }}">Birth Place</label>
        <select name="birth_place[]" id="birth_place_{{ $itemCounter }}" class="form-control select2">
            <option value="">Choose City</option>
        </select>
    </div>
    <div class="form-group col-5">
        <label for="death_place_{{ $itemCounter }}">Death Place</label>
        <select name="death_place[]" id="death_place_{{ $itemCounter }}" class="form-control select2">
            <option value="">Choose City</option>
        </select>
    </div>
    <div class="form-group col-12"><label for="poet_bio_{{ $itemCounter }}">Details</label>
        <textarea class="textarea form-control" name="poet_bio[]" id="poet_bio_{{ $itemCounter }}" >{{ $item->poet_bio }}</textarea>
    </div><button type="button" onclick="deleteRow({{ $itemCounter }})" data-row-id="1" class="btn btn-danger btn-block"><i class="fa fa-trash"></i> Delete This Information</button>
</div>
@endfor


@endif {{-- @endif for ($errors->any()) --}}