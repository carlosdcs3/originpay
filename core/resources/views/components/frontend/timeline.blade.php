@props(['items' => []])

<style>
    .op-timeline {
        border-left: 2px solid var(--border);
        padding-left: 40px;
        position: relative;
        margin-left: 10px;
    }
    .op-timeline-item {
        margin-bottom: 48px;
        position: relative;
    }
    .op-timeline-item:last-child {
        margin-bottom: 0;
    }
    .op-timeline-dot {
        position: absolute;
        left: -51px; /* 40px padding + 2px border / 2 + size / 2 */
        top: 0;
        width: 20px;
        height: 20px;
        background: var(--primary);
        border-radius: 50%;
        border: 4px solid var(--bg-deep);
    }
    .op-timeline-date {
        color: var(--primary);
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 8px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .op-timeline-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 16px;
        letter-spacing: -0.01em;
    }
    .op-timeline-content {
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 1.1rem;
    }
</style>

<div class="op-timeline">
    @foreach($items as $item)
        <div class="op-timeline-item">
            <span class="op-timeline-dot"></span>
            @if(isset($item['date']))
                <div class="op-timeline-date">{{ $item['date'] }}</div>
            @endif
            <h3 class="op-timeline-title">{{ $item['title'] }}</h3>
            <div class="op-timeline-content">
                {!! $item['content'] !!}
            </div>
        </div>
    @endforeach
    
    {{ $slot }}
</div>
