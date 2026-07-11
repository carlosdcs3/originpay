@props(['nodes' => []])

{{-- 
    A technical architecture diagram built purely with CSS.
    Nodes can have: 'icon', 'label', 'sub', 'highlight'
--}}

<style>
    .op-arch-diagram {
        padding: 80px 20px;
        background: var(--bg-panel);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }
    .op-arch-wrapper {
        max-width: 900px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }
    @media (min-width: 768px) {
        .op-arch-wrapper {
            flex-direction: row;
            justify-content: center;
        }
    }
    .op-arch-node {
        background: var(--bg-deep);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 24px;
        min-width: 220px;
        text-align: center;
        position: relative;
    }
    .op-arch-node.highlight {
        border-color: var(--primary);
    }
    .op-arch-icon {
        font-size: 1.5rem;
        color: var(--text-muted);
        margin-bottom: 12px;
    }
    .op-arch-node.highlight .op-arch-icon {
        color: var(--primary);
    }
    .op-arch-label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.85rem;
        font-weight: 600;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }
    .op-arch-sub {
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    .op-arch-connector {
        width: 2px;
        height: 30px;
        background: var(--border);
    }
    @media (min-width: 768px) {
        .op-arch-connector {
            width: 40px;
            height: 2px;
        }
    }
</style>

<div class="op-arch-diagram">
    <div class="op-arch-wrapper">
        @foreach($nodes as $index => $node)
            <div class="op-arch-node {{ isset($node['highlight']) && $node['highlight'] ? 'highlight' : '' }}">
                <div class="op-arch-icon"><i class="{{ $node['icon'] }}"></i></div>
                <div class="op-arch-label">{{ $node['label'] }}</div>
                @if(isset($node['sub']))
                    <div class="op-arch-sub">{{ $node['sub'] }}</div>
                @endif
            </div>
            
            @if($index < count($nodes) - 1)
                <div class="op-arch-connector"></div>
            @endif
        @endforeach
    </div>
</div>
