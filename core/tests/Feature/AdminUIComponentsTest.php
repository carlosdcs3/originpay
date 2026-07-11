<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminUIComponentsTest extends TestCase
{
    /**
     * Teste básico para garantir que os componentes do Enterprise Design System (2.2) 
     * estão sendo renderizados sem erros e com a estrutura esperada.
     */

    public function test_badge_component_renders()
    {
        $rendered = Blade::render('<x-ds.badge status="paid" />');
        $this->assertStringContainsString('ds-badge-success', $rendered);
        $this->assertStringContainsString('Pago', $rendered);
    }

    public function test_skeleton_component_renders()
    {
        $rendered = Blade::render('<x-ds.skeleton type="card" />');
        $this->assertStringContainsString('ds-skeleton', $rendered);
        $this->assertStringContainsString('height: 120px', $rendered);
    }

    public function test_empty_state_component_renders()
    {
        $rendered = Blade::render('<x-ds.empty-state title="Sem dados" desc="Vazio" />');
        $this->assertStringContainsString('Sem dados', $rendered);
        $this->assertStringContainsString('ds-empty-title', $rendered);
        $this->assertStringContainsString('Vazio', $rendered);
    }

    public function test_stat_card_component_renders()
    {
        $rendered = Blade::render('<x-ds.stat-card title="Métrica" value="100" />');
        $this->assertStringContainsString('ds-kpi-card', $rendered);
        $this->assertStringContainsString('Métrica', $rendered);
        $this->assertStringContainsString('100', $rendered);
    }

    public function test_table_component_renders_with_slots()
    {
        $rendered = Blade::render('
            <x-ds.table title="Tabela Teste" count="5">
                <x-slot name="search"><input name="q"></x-slot>
                <tr><td>Row</td></tr>
            </x-ds.table>
        ');
        $this->assertStringContainsString('Tabela Teste', $rendered);
        $this->assertStringContainsString('ds-table-count', $rendered);
        $this->assertStringContainsString('<input name="q">', $rendered);
        $this->assertStringContainsString('Row', $rendered);
    }
}
