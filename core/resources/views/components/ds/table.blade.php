@props([
    'title' => null,
    'subtitle' => null,
    'count' => null,
    'thead' => null,
    'pagination' => null,
    'isEmpty' => false,
    'action' => null, // If provided, the header acts as a GET form
])

<div class="ds-table-card">
    @if($title || $count !== null || isset($filters) || isset($search))
        @if($action)
        <form action="{{ $action }}" method="GET" class="ds-table-header" style="flex-direction:column; align-items:stretch; gap:1rem; margin:0; padding:1.5rem;">
        @else
        <div class="ds-table-header" style="flex-direction:column; align-items:stretch; gap:1rem; margin:0; padding:1.5rem;">
        @endif
            
            {{-- Top Row: Title, Subtitle, Count, and Search --}}
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                <div>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        @if($title)
                            <span class="ds-table-title">{{ $title }}</span>
                        @endif
                        @if($count !== null)
                            <span class="ds-table-count">{{ $count }}</span>
                        @endif
                    </div>
                    @if($subtitle)
                        <div style="font-size:var(--ds-text-sm);color:var(--ds-text-muted);margin-top:2px;">{{ $subtitle }}</div>
                    @endif
                </div>

                @if(isset($search))
                    <div style="min-width: 250px;">
                        {{ $search }}
                    </div>
                @endif
            </div>

            {{-- Bottom Row: Filters and Header Actions (including Bulk Actions) --}}
            @if(isset($filters) || isset($headerActions) || isset($bulkActions))
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; padding-top:1rem; border-top:1px solid var(--ds-border);">
                    <div class="d-flex align-items-center gap-2 flex-wrap" style="flex:1;">
                        @if(isset($filters))
                            {{ $filters }}
                        @endif
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        @if(isset($bulkActions))
                            {{ $bulkActions }}
                        @endif
                        @if(isset($headerActions))
                            {{ $headerActions }}
                        @endif
                    </div>
                </div>
            @endif

        @if($action)
        </form>
        @else
        </div>
        @endif
    @endif

    <div class="table-responsive">
        <table class="ds-table table mb-0">
            @if($thead && !$isEmpty)
                <thead>
                    <tr>
                        {{ $thead }}
                    </tr>
                </thead>
            @endif
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if($pagination && !$isEmpty)
        {{ $pagination }}
    @endif
</div>
