@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Segmentos Inteligentes</h2>
    <a href="{{ route('merchant.connect.segments.create') }}" class="btn btn-primary mb-3">Novo Segmento</a>
    <table class="table table-striped">
        <thead><tr><th>Nome</th><th>Descrição</th><th>Ações</th></tr></thead>
        <tbody>
            @foreach($segments as $s)
            <tr>
                <td>{{ $s->name }}</td>
                <td>{{ $s->description }}</td>
                <td><a href="{{ route('merchant.connect.segments.edit', $s->id) }}" class="btn btn-sm btn-outline-primary">Editar</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $segments->links() }}
</div>
@endsection
