@props([
    'size' => 40,
    'color' => '#7C3AED',
    'trackColor' => 'rgba(124,58,237,.15)',
    'text' => null,
])

<div class="ds-spinner-wrapper" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 0; opacity: 0; animation: dsFadeIn 0.3s ease forwards;">
    <style>
        @keyframes dsSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes dsFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .ds-spinner-svg {
            animation: dsSpin 0.9s linear infinite;
        }
    </style>
    
    <svg class="ds-spinner-svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg" style="transform-origin: center;">
        <!-- Track -->
        <circle cx="25" cy="25" r="20" fill="none" stroke="{{ $trackColor }}" stroke-width="4"></circle>
        <!-- Spinner -->
        <circle cx="25" cy="25" r="20" fill="none" stroke="{{ $color }}" stroke-width="4" stroke-linecap="round" stroke-dasharray="90 150" stroke-dashoffset="0"></circle>
    </svg>
    
    @if($text)
        <p style="margin-top: 16px; color: var(--ds-text-muted, #A1A1AA); font-size: 0.85rem; font-weight: 500; margin-bottom: 0;">
            {{ $text }}
        </p>
    @endif
</div>
