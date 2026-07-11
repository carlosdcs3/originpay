@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Templates</h2>
    <a href="{{ route('merchant.connect.templates.create') }}" class="btn btn-primary mb-3">Novo Template</a>
    <table class="table table-striped">
        <thead><tr><th>Nome</th><th>Canal</th><th>Status</th><th>Versão</th><th>Ações</th></tr></thead>
        <tbody>
            @foreach($templates as $t)
            <tr>
                <td>{{ $t->name }}</td>
                <td>{{ $t->channel }}</td>
                <td>
                    <span class="badge {{ $t->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $t->status }}
                    </span>
                </td>
                <td>v{{ $t->version }}</td>
                <td>
                    <a href="{{ route('merchant.connect.templates.edit', $t->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    @if($t->status === 'draft')
                    <form action="{{ route('merchant.connect.templates.publish', $t->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-sm btn-success">Publicar</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
