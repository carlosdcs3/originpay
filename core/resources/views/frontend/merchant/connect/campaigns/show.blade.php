@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Detalhes da Campanha: {{ $campaign->name }}</h2>
    <ul>
        <li>Status: <strong>{{ $campaign->status }}</strong></li>
        <li>Canal: {{ $campaign->channel }}</li>
        <li>Segmento: {{ $campaign->segment->name }}</li>
        <li>Template: {{ $campaign->template->name }}</li>
    </ul>

    @if($campaign->status === 'draft')
        <form method="POST" action="{{ route('merchant.connect.campaigns.schedule', $campaign->id) }}">
            @csrf
            <button type="submit" class="btn btn-warning">Bloquear e Agendar (Snapshot)</button>
        </form>
    @endif

    <button onclick="runDryRun()" class="btn btn-secondary mt-3">Executar Dry Run (Teste em 10 contatos)</button>

    <div id="dryRunResults" class="mt-4"></div>
</div>

<script>
function runDryRun() {
    document.getElementById('dryRunResults').innerHTML = "Rodando Resolver...";
    fetch("{{ route('merchant.connect.campaigns.dryRun', $campaign->id) }}")
        .then(res => res.json())
        .then(data => {
            document.getElementById('dryRunResults').innerHTML = "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
        })
        .catch(err => alert("Erro"));
}
</script>
@endsection
