@php
	try {
		$customCss = \App\Models\CustomCode::getCached(\App\Enums\CustomCodeType::CSS);
	} catch (\Exception $e) {
		$customCss = null;
	}
@endphp

@if ($customCss)
	<style>{!! is_array($customCss) ? ($customCss['content'] ?? '') : ($customCss->content ?? '') !!}</style>
@endif
