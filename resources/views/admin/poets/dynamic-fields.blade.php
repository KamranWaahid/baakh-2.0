@php
    $itemCounter = 1;
@endphp
@foreach ($details as $k => $item)
<div class="old_cities_info" data-row="{{ $itemCounter }}" data-lang="{{ $item->lang }}" data-birth="{{ $item->birth_place }}" data-death="{{ $item->death_place }}"></div>
<div class="row p-2 rounded mt-3" data-cusid="{{ $itemCounter }}" id="dyn_row{{ $itemCounter }}" style="background:#d1e9e4;">
    <div class="form-group col-6">
        <label for="poet_name_{{ $itemCounter }}">Name</label>
        <input type="text" class="form-control" name="poet_name[]" value="{{ old('poet_name[]', $item->poet_name) }}" id="poet_name_{{ $itemCounter }}" placeholder="Enter Poet Name">
    </div>
    
    <div class="form-group col-6">
        <label for="poet_laqab_{{ $itemCounter }}">Laqab</label>
        <input type="text" class="form-control" name="poet_laqab[]" value="{{ old('poet_laqab[]', $item->poet_laqab) }}" id="poet_laqab_{{ $itemCounter }}" placeholder="Enter Laqab">
    </div>
    <div class="form-group col-6">
        <label for="pen_name_{{ $itemCounter }}">Laqab</label>
        <input type="text" class="form-control" name="pen_name[]" value="{{ old('pen_name[]', $item->pen_name) }}" id="pen_name_{{ $itemCounter }}" placeholder="Enter Takhalus">
    </div>
    <div class="form-group col-6">
        <label for="tagline_{{ $itemCounter }}">Tagline</label>
        <input type="text" class="form-control" name="tagline[]" value="{{ old('tagline[]', $item->tagline) }}" id="tagline_{{ $itemCounter }}" placeholder="Enter Tagline">
    </div>
    <div class="form-group col-2">
        <label for="lang_{{ $itemCounter }}">Language</label>
        <select name="lang[]" id="lang_{{ $itemCounter }}" data-id="{{ $itemCounter }}" class="form-control changeLanguage">
            @foreach ($languages as $lang)
                <option value="{{ $lang->lang_code }}" @if ($item->lang == $lang->lang_code) 
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

@php
    $itemCounter++;
@endphp

@endforeach