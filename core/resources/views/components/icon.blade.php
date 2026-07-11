@php use App\View\Components\Icon; @endphp
@props(['name', 'width' => null, 'height' => null, 'class' => ''])

@php
    $svgContent = (new Icon($name))->svgContent();

    if ($svgContent) {
        $dom = new DOMDocument();
        $dom->loadXML($svgContent);
        $svg = $dom->getElementsByTagName('svg')->item(0);

        if ($width) {
            $svg->setAttribute('width', $width);
        }

        if ($height) {
            $svg->setAttribute('height', $height);
        }

        if ($class) {
            $existingClasses = $svg->getAttribute('class');
            $svg->setAttribute('class', trim("$existingClasses $class"));
        }

        $svgContent = $dom->saveXML($svg);
    }
@endphp

@if ($svgContent)
    {!! $svgContent !!}
@else
    {{-- Fallback to LineAwesome icon if SVG doesn't exist --}}
    <i class="la la-{{ str_replace('cil-', '', $name) }} {{ $class ?: 'text-muted' }}" 
       style="{{ $width ? 'font-size: '.$width.';' : '' }} {{ $width ? 'width: '.$width.';' : '' }} {{ $height ? 'height: '.$height.';' : '' }}">
    </i>
@endif
