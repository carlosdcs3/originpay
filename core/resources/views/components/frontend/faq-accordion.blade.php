@props(['question'])

<style>
    .op-editorial-faq-item {
        border-bottom: 1px solid var(--border);
    }
    .op-editorial-faq-item:last-child {
        border-bottom: none;
    }
    .op-editorial-faq-summary {
        padding: 24px 0;
        font-size: 1.15rem;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        list-style: none; /* remove default arrow */
        background: transparent !important;
        border: none !important;
    }
    .op-editorial-faq-summary::-webkit-details-marker {
        display: none; /* remove safari arrow */
    }
    .op-editorial-faq-summary .icon {
        color: var(--primary);
        transition: transform 0.2s;
    }
    details[open] .op-editorial-faq-summary .icon {
        transform: rotate(180deg);
    }
    .op-editorial-faq-content {
        padding-bottom: 24px;
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 1.05rem;
    }
</style>

<details class="op-editorial-faq-item">
    <summary class="op-editorial-faq-summary">
        {{ $question }}
        <span class="icon"><i class="fas fa-chevron-down"></i></span>
    </summary>
    <div class="op-editorial-faq-content">
        {{ $slot }}
    </div>
</details>
