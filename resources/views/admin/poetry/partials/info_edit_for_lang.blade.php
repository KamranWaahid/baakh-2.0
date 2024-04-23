<form action="{{ route('admin.poetry.translation.update-info', ['id' => $info->poetry_id, 'language' => $for_language]) }}" id="mainInformationForm" method="post">
    <div class="card overlay-wrapper">
        <!--= start[Main Information Card Overly] =-->
            <div class="overlay" style="display:none;">
                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                <div class="text-bold pt-2 sr-only">Loading...</div>
            </div>
        <!--= end[Main Information Card Overly] =-->
        <div class="card-header">Main Information <strong>{{ $for_language }}</strong> Language</div>
        <div class="card-body">
            <input type="hidden" name="info_id" value="{{ $info_tr->id }}">
            
            {{-- Title --}}
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" class="form-control blog-title @error('title') is-invalid @enderror" value="{{ old('title', $info_tr->title) }}"  placeholder="Insert Title here">
            
                @error('title')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- Source --}}
            <div class="form-group">
                <label for="source">Source</label>
                <textarea name="source" class="form-control blog-title @error('source') is-invalid @enderror" placeholder="Insert Source here" rows="5">{{ old('source', $info_tr->source) }}</textarea>
                
                @error('source')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            
            {{-- Information --}}
            <div class="form-group">
                <label for="info">Information</label>
                <textarea name="info" class="form-control blog-title @error('info') is-invalid @enderror" placeholder="Insert Information here" rows="5">{{ old('info', $info_tr->info) }}</textarea>
            
                @error('info')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-sm btn-success btn-save-main-information" onclick="saveInformation()"><i class="fa fa-save mr-2"></i>Update information</button>
        </div>
    </div>
</form>