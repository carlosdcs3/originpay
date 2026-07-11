{{-- Command Palette Modal --}}
<div class="modal fade ds-command-modal" id="commandPaletteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- Search Input --}}
            <div class="position-relative">
                <div class="ds-command-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </div>
                <input type="text" class="form-control ds-command-input" id="commandPaletteInput"
                       placeholder="Buscar ou navegar...  (usuário, TXID, lojista)" autocomplete="off">
            </div>

            {{-- Results --}}
            <div class="ds-command-results" id="commandResults">
                <div class="ds-command-section-label">Navegação Rápida</div>
                
                @php
                    // Generate searchable items from admin_menus
                    $commandItems = collect();
                    foreach(config('admin_menus') as $section) {
                        if(!isset($section['menus'])) continue;
                        
                        foreach($section['menus'] as $menu) {
                            if($menu['type'] === 'single') {
                                $perm = $menu['permission'] ?? null;
                                if(is_null($perm) || auth()->guard('admin')->user()->can($perm)) {
                                    $commandItems->push([
                                        'label' => __($menu['label']),
                                        'desc' => __($section['label']),
                                        'route' => route($menu['route'], $menu['params'] ?? []),
                                        'icon' => $menu['icon'] ?? $section['icon'] ?? 'file'
                                    ]);
                                }
                            } elseif($menu['type'] === 'groups') {
                                foreach($menu['sub_menus'] as $sub) {
                                    $subPerm = $sub['permission'] ?? $sub['can'] ?? null;
                                    if(is_null($subPerm) || auth()->guard('admin')->user()->can($subPerm)) {
                                        $commandItems->push([
                                            'label' => __($sub['label']),
                                            'desc' => __($section['label']) . ' > ' . __($menu['label']),
                                            'route' => route($sub['route'], $sub['params'] ?? []),
                                            'icon' => $menu['icon'] ?? $section['icon'] ?? 'file'
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                @endphp

                <div id="commandItemsContainer">
                    @foreach($commandItems as $item)
                        <a href="{{ $item['route'] }}" class="ds-command-item" data-search="{{ strtolower($item['label'] . ' ' . $item['desc']) }}">
                            <div class="ds-command-item-icon">
                                <x-icon name="{{ $item['icon'] }}" style="width:15px;height:15px;"/>
                            </div>
                            <div>
                                <div style="font-size:var(--ds-text-sm);font-weight:500;">{{ $item['label'] }}</div>
                                <div style="font-size:var(--ds-text-xs);color:var(--ds-text-muted);">{{ $item['desc'] }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                <div id="commandNoResults" class="ds-empty" style="display:none; padding: 2rem; text-align: center; color: var(--ds-text-muted);">
                    Nenhum resultado encontrado.
                </div>
            </div>

            {{-- Footer --}}
            <div class="ds-command-footer">
                <span><kbd>↑</kbd> <kbd>↓</kbd> Navegar</span>
                <span><kbd>Enter</kbd> Selecionar</span>
                <span><kbd>ESC</kbd> Fechar</span>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cpModalEl = document.getElementById('commandPaletteModal');
    if (!cpModalEl) return;

    // Trigger CMD+K
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const modal = coreui.Modal.getOrCreateInstance(cpModalEl);
            modal.show();
        }
    });

    const cpInput = document.getElementById('commandPaletteInput');
    const container = document.getElementById('commandItemsContainer');
    const noResults = document.getElementById('commandNoResults');
    const allItems = container ? Array.from(container.querySelectorAll('.ds-command-item')) : [];

    // Focus input on show
    cpModalEl.addEventListener('shown.coreui.modal', function () {
        if(cpInput) cpInput.focus();
    });
    
    // Clear on hide
    cpModalEl.addEventListener('hidden.coreui.modal', function () {
        if(cpInput) {
            cpInput.value = '';
            filterItems('');
        }
    });

    // Filter Logic
    function filterItems(query) {
        query = query.toLowerCase().trim();
        let visibleCount = 0;
        
        allItems.forEach(item => {
            item.classList.remove('active'); // reset active state
            if (query === '' || item.dataset.search.includes(query)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
            // Auto-select first visible
            const firstVisible = allItems.find(i => i.style.display !== 'none');
            if(firstVisible) firstVisible.classList.add('active');
        }
    }

    if (cpInput) {
        cpInput.addEventListener('input', function(e) {
            filterItems(e.target.value);
        });

        // Keyboard navigation
        cpInput.addEventListener('keydown', function(e) {
            const visibleItems = allItems.filter(i => i.style.display !== 'none');
            if (visibleItems.length === 0) return;

            let activeIdx = visibleItems.findIndex(i => i.classList.contains('active'));
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if(activeIdx >= 0) visibleItems[activeIdx].classList.remove('active');
                activeIdx = (activeIdx + 1) % visibleItems.length;
                visibleItems[activeIdx].classList.add('active');
                visibleItems[activeIdx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if(activeIdx >= 0) visibleItems[activeIdx].classList.remove('active');
                activeIdx = (activeIdx - 1 + visibleItems.length) % visibleItems.length;
                visibleItems[activeIdx].classList.add('active');
                visibleItems[activeIdx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIdx >= 0) visibleItems[activeIdx].click();
            }
        });
    }
});
</script>
@endpush
