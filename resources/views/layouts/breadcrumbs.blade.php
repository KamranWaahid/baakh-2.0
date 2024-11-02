@if(isset($breadcrumbs) && count($breadcrumbs) > 0)
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            @foreach($breadcrumbs as $breadcrumb)
                @if(isset($breadcrumb['active']) && $breadcrumb['active'])
                    <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['label'] }}</li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
