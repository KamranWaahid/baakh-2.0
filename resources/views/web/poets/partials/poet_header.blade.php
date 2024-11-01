
<!-- ========== Start Information Section ========== -->
<section id="basic-info" class="bundle-info p-0">
        
    <!---= START background image if available =--->
    <div class="bg-cover-bundle" style="background-image: url('{{ asset('assets/images/site/baakh-header-01.jpg') }}'); background-size:cover;"></div>
    <!---= END background image if available =--->
    

    <!---= START container =--->
    <div class="container p-2" style=" background: var(--color-poet-sections); margin-top:-100px; min-height:120px;">
    <!---= START Poet Picture =--->
    <div class="row pt-lg-3 pt-5 pt-sm-5 ps-3 pe-3">
        <!---= START col-3 for photo =--->
        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12" id="author-picture">
            <img src="{{ file_exists($profile->poet_pic) ? asset($profile->poet_pic) : asset('assets/img/placeholder290x293.jpg') }}" class="img-fluid shadow" alt="">
        </div>
        <!---= END col-3 for photo =--->

        <!---= START col-9 =--->
        <div class="col-lg-10 col-md-10 col-sm-12 col-xs-12" id="poet-info">
            <!---= START d-flex and buttons with info divider =--->
            <div class="info-box">
                <!---= START basic info div =--->
                <div class="basic-info">
                    <h2 class="text-primary">
                        {{ $profile->details->poet_laqab; }}
                    </h2>
                    {!! '<span>'.$profile->details->tagline.'</span>' !!}
                    <p>
                        <span><i class="lni lni-calendar mx-1"></i>{{ date('Y', strtotime($profile->date_of_birth)) }} @if ($profile->date_of_death)
                            - {{ date('Y', strtotime($profile->date_of_death)) }}
                        @endif </span>
                        <span class="ms-3"><i class="lni lni-map-marker"></i> {{ $profile->details->birthPlace->city_name }}</span>
                    </p>
                    <button type="button" class="btn btn-baakh" data-bs-toggle="modal" data-bs-target="#poetInfoDetailModal"><i class="lni lni-list"></i> {{ trans('buttons.detail') }}</button>
                </div>
                <!---= END basic info div =--->

                <div class="buttons d-flex justify-content-between">
                    <div class="right-buttons text-center">
                        <h4 class="m-0" style="margin-bottom:-10px !important;">{{ $totalLikes }}</h4>
                        <span>{{ trans('buttons.likes') }}</span>
                    </div>
                    <div class="left-buttons">
                        <div class="d-flex justify-content-between">
                            
                            <x-baakh_share_buttons poetryUrl="{{ $profileUrl }}" shareText="" componentId="buttons_social_profile"  />
                            <button type="button" class="btn btn-share btn-default" data-id="buttons_social_profile"><i class="bi bi-share"></i></button>
                            <livewire:LikePoetButton :poet="$profile" />
                        </div>
                    </div>
                </div>
            </div>
            <!---= END d-flex and buttons with info divider =---> 
        </div>
        <!---= END col-9 =--->
    </div>
    <!---= END Poet Picture =--->
     
     <div class="spacer-dotted mt-3"></div>
  </div>
  <!---= END container =--->
</section>
<!-- ========== End Information Section ========== -->