@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Connect - Contatos</h2>
    
    <div class="mb-3">
        <a href="{{ route('merchant.connect.contacts.create') }}" class="btn btn-primary">Novo Contato</a>
    </div>

    @if($contacts->isEmpty())
        <div class="alert alert-info text-center">
            <h4>Nenhum contato encontrado</h4>
            <p>Comece a construir sua base de clientes.</p>
        </div>
    @else
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Tags</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contacts as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->email }}</td>
                    <td>{{ $c->whatsapp }}</td>
                    <td>
                        @foreach($c->tags as $tag)
                            <span class="badge bg-secondary">{{ $tag->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('merchant.connect.contacts.edit', $c->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        {{ $contacts->links() }}
    @endif
</div>
@endsection
