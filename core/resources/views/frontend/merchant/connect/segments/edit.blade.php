@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Editar Segmento</h2>
    <form method="POST" action="{{ route('merchant.connect.segments.update', $segment->id) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="name" class="form-control" value="{{ $segment->name }}" required>
        </div>
        <div class="mb-3">
            <label>Descrição</label>
            <input type="text" name="description" class="form-control" value="{{ $segment->description }}">
        </div>
        <div class="mb-3">
            <label>Regras (JSON)</label>
            <textarea name="rules" class="form-control" rows="10" required>{{ json_encode($segment->rules, JSON_PRETTY_PRINT) }}</textarea>
        </div>
        <button type="submit" class="btn btn-success">Salvar Alterações</button>
    </form>
</div>
@endsection
