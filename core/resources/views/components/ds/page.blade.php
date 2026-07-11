@props([
    'title' => null,
    'desc' => null,
    'breadcrumb' => [], // array of ['title' => 'Dashboard', 'url' => route('admin.dashboard')]
    'actions' => null,
])

<div class="ds-page ds-fade-in">
    <div class="ds-page-header mb-4">
        <div>
            @if(!empty($breadcrumb))
                <div class="ds-breadcrumb mb-1">
                    @foreach($breadcrumb as $index => $item)
                        @if(!$loop->last)
                            <a href="{{ $item['url'] ?? '#' }}">{{ $item['title'] }}</a>
                            <span class="ds-breadcrumb-sep">/</span>
                        @else
                            <span style="color:var(--ds-text);">{{ $item['title'] }}</span>
                        @endif
                    @endforeach
                </div>
            @endif

            @if($title)
                <h1 class="ds-heading-lg mb-0">{{ $title }}</h1>
            @endif

            @if($desc)
                <p class="ds-body-sm mb-0 mt-1">{{ $desc }}</p>
            @endif
        </div>

        @if($actions)
            <div class="d-flex align-items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>

    {{ $slot }}
</div>
