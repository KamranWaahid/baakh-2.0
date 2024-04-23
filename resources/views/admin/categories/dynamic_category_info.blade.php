<div class="row" id="row1">
    {{-- Name field --}}
    <div class="form-group col-8">
        <label for="cat_name">Name</label>
        <input type="text" class="form-control  @error('cat_name') is-invalid @enderror" value="{{ old('cat_name') }}"  name="cat_name" id="cat_name" placeholder="Enter Category Name">
        
        @error('cat_name')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    {{-- language --}}
    <div class="form-group col-4">
        <label for="lang">Language</label>
        <select name="lang" id="lang" class="form-control select2 @error('lang') is-invalid @enderror">
            @foreach ($languages as $item)
                <option value="{{ $item->lang_code }}">{{ $item->lang_title }}</option>
            @endforeach
        </select>
        
        @error('lang')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>

    {{-- details --}}
    <div class="form-group col-12">
        <label for="cat_detail">Details</label>
        <x-adminlte-textarea name="cat_detail" placeholder="Insert description..."/>
        
        @error('cat_detail')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>


</div>