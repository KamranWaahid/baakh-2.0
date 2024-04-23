@extends('layouts.web')

@section('body')
<!-- ====== Top Spacer Start ====== -->
<div class="top-spacer"></div>
<!-- ====== Top Spacer End ====== -->


<!-- ========== Start Top Section ========== -->
<section class="section-bg all-tags-page" id="couplet_page">
    <div class="container">
        <div class="d-flex justify-content-between">
            <h3 class="text-baakh">{{ trans_choice('labels.topic', $totalTags, ['count' => $totalTags]) }}</h3>
            <p>ڪُل {{ $totalTags }} مليا</p>
        </div>
        
        <p class="text-justify">
            ڀليڪار اسان جي شعرن واري صفحي تي، موجود شعرن جي جي اندر، توهان کي اختصار جي خوبصورتي ۽ جوڙي جي طاقت کي ڳوليندا. هر ڪوپٽ، لفظن جو هڪ مجموعو، هڪ ڪهاڻي ٺاهي ٿو، هڪ جذبات جو اظهار ڪري ٿو، يا مختصر خوبصورتي ۾ هڪ لمحو قبضو ڪري ٿو. اسان سان شامل ٿيو هن سفر تي ٻولن جي دنيا ذريعي، جتي ٻه سٽون معنيٰ جي ڪائنات کي پهچائي سگهن ٿيون. چاهي توهان الهام، عڪاسي، يا صرف شاعراڻي رابطي جي خوشي ڳوليو، اسان جا شعر توهان جي حواس کي جادو ڪرڻ ۽ توهان جي روح کي ڦهلائڻ لاء انتظار ڪندا آهن.
        </p>
    </div>

    <div class="container mt-5">
        @foreach ($tags as $firstLetter => $tagsWithSameLetter)
        <div class="card mt-2 p-2">
        <h3 class="text-baakh">{{ $firstLetter }}</h3>
            <ul class="row px-5">
            @foreach ($tagsWithSameLetter as $tag)
                <li class="col-md-3 col-6">
                    <a href="{{ URL::localized(route('poetry.with-tag', $tag->slug)) }}" class="tag-title">
                        {{ Str::ucfirst($tag->tag) }}
                    </a>
                </li>    
            @endforeach
            </ul>
        </div>
        @endforeach {{-- enforeach $tags --}}
    </div>
</section>
<!-- ========== End Top Section ========== -->
 

@endsection