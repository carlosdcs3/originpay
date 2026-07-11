@props(['title' => 'Resumo'])

<div style="background: rgba(124, 58, 237, 0.05); border-left: 3px solid var(--primary); padding: 24px; border-radius: 0 8px 8px 0; margin-bottom: 32px;">
    <div style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--primary); margin-bottom: 8px;">{{ $title }}</div>
    <div style="font-size: 1.05rem; color: var(--text-base); line-height: 1.6; margin: 0;">
        {{ $slot }}
    </div>
</div>
