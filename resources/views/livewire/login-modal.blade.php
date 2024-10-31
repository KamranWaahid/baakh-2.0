<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">لاگ ان ڪريو</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="login">
                    @if($errorMessage)
                        <div class="alert alert-danger">{{ $errorMessage }}</div>
                    @endif

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="exampleInputEmail1" class="form-label">ايميل ايڊريس *</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" class="form-control border-0 bg-light rounded-end ps-1  @error('email') is-invalid @enderror" wire:model.defer="email" placeholder="E-mail"  id="email">

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        
                    </div>
                    <!-- Password -->
                    <div class="mb-4">
                        <label for="inputPassword5" class="form-label">پاسورڊ *</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light rounded-start border-0 text-secondary px-3"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control border-0 bg-light rounded-end ps-1 @error('password') is-invalid @enderror" placeholder="password"  wire:model.defer="password" id="inputPassword5">
                            
                            @error('password')
                                <div  class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>
                     
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" wire:model.defer="remember">
                        <label class="form-check-label" for="remember">
                            ٻيھر ياد رکو
                        </label>
                    </div>

                    <!-- Button -->
                    <div class="align-items-center mt-3">
                        <div class="d-grid">
                            <button class="btn btn-primary mb-0" type="submit">لاگ ان</button>
                        </div>
                    </div>
                </form>

                <!-- OR divider -->
                
                <div class="position-relative my-4">
                    <hr>
                    <p class="small position-absolute top-50 start-50 translate-middle bg-body px-5">يا</p>
                </div>

                <!-- Google Login Button -->
                <div class="col-xxl-6 d-grid">
                    <a href="{{ route('login.with-google') }}{{ request()->query('url') ? '?url='.request()->query('url') : '' }}" class="btn bg-google mb-2 mb-xxl-0">
                        <i class="fab fa-fw fa-google text-white me-2"></i> گوگل سان لاگ ان ٿيو
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>