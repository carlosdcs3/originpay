@props(['icon', 'title', 'desc'])

<div class="op-feature-item">
    <div class="op-feature-icon">
        <i class="{{ $icon }}"></i>
    </div>
    <h3 class="op-feature-title">{{ $title }}</h3>
    <p class="op-feature-desc">{{ $desc }}</p>
</div>
