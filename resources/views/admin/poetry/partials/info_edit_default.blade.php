<div class="card" style="background: #4caf4f62">
    <div class="card-header">Main Information {Default Language}</div>
    <div class="card-body">
        {{-- Title --}}
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" disabled class="form-control blog-title @error('title') is-invalid @enderror" value="{{ $info->title }}"  placeholder="Insert Title here">
        
            @error('title')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Source --}}
        <div class="form-group">
            <label for="source">Source</label>
            <textarea disabled class="form-control blog-title @error('source') is-invalid @enderror" placeholder="Insert Source here" rows="5">{{ $info->source }}</textarea>
            
            @error('source')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        
        {{-- Information --}}
        <div class="form-group">
            <label for="info">Information</label>
            <textarea disabled class="form-control blog-title @error('info') is-invalid @enderror" placeholder="Insert Information here" rows="5">{{ $info->info }}</textarea>
        
            @error('info')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    <div class="card-footer">
        <a href="#" class="btn btn-sm btn-secondary"><i class="fa fa-edit mr-2"></i>Edit Default Language's Information</a>
    </div>
</div>