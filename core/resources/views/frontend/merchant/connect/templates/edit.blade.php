@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Editar Template (v{{ $template->version }})</h2>
    @if($template->status === 'published')
        <div class="alert alert-warning">Você está editando um template publicado. Ao salvar, uma nova versão <b>Draft</b> será criada.</div>
    @endif
    <form method="POST" action="{{ route('merchant.connect.templates.update', $template->id) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="name" class="form-control" value="{{ $template->name }}" required>
        </div>
        <div class="mb-3">
            <label>Conteúdo (AST JSON)</label>
            <textarea name="content" class="form-control" rows="15" required>{{ json_encode($template->content, JSON_PRETTY_PRINT) }}</textarea>
        </div>
        <button type="submit" class="btn btn-success">Salvar</button>
    </form>
</div>
@endsection
