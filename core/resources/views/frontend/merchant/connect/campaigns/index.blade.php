@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Campanhas</h2>
    <a href="{{ route('merchant.connect.campaigns.create') }}" class="btn btn-primary mb-3">Nova Campanha</a>
    <table class="table table-striped">
        <thead><tr><th>Nome</th><th>Canal</th><th>Segmento</th><th>Template</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
            @foreach($campaigns as $c)
            <tr>
                <td>{{ $c->name }}</td>
                <td>{{ $c->channel }}</td>
                <td>{{ $c->segment->name }}</td>
                <td>{{ $c->template->name }}</td>
                <td><span class="badge bg-primary">{{ $c->status }}</span></td>
                <td>
                    <a href="{{ route('merchant.connect.campaigns.show', $c->id) }}" class="btn btn-sm btn-info">Ver Detalhes</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
