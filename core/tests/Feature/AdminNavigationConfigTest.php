<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminNavigationConfigTest extends TestCase
{
    public function test_admin_sidebar_sections_match_originpay_structure(): void
    {
        $menus = config('admin_menus');
        $labels = collect($menus)->pluck('label')->values()->all();

        $this->assertSame([
            'Dashboard',
            'Financeiro',
            'Gateways',
            'Clientes',
            'Operações',
            'Marketing',
            'Relatórios',
            'Configurações',
        ], $labels);

        $this->assertNotContains('Developer Portal', $labels);
    }

    public function test_removed_admin_menu_entries_are_not_present_anymore(): void
    {
        $menus = collect(config('admin_menus'));
        $allSubmenuLabels = $menus
            ->flatMap(fn (array $section) => collect($section['menus'] ?? [])->pluck('label'))
            ->all();

        $this->assertContains('Carteiras', $allSubmenuLabels);
        $this->assertContains('Campanhas', $allSubmenuLabels);
        $this->assertContains('Empresa', $allSubmenuLabels);
        $this->assertContains('Agendador', $allSubmenuLabels);
        $this->assertContains('Status Público', $allSubmenuLabels);

        $this->assertNotContains('Wallets', $allSubmenuLabels);
        $this->assertNotContains('Ranking', $allSubmenuLabels);
        $this->assertNotContains('Indicações', $allSubmenuLabels);
        $this->assertNotContains('Geral', $allSubmenuLabels);
        $this->assertNotContains('Scheduler', $allSubmenuLabels);
        $this->assertNotContains('Status da Plataforma', $allSubmenuLabels);
    }
}
