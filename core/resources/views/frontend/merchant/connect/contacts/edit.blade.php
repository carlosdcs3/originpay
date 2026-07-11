@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Editar Contato</h2>
    <form method="POST" action="{{ route('merchant.connect.contacts.update', $contact->id) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="name" class="form-control" value="{{ $contact->name }}">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ $contact->email }}">
        </div>
        <div class="mb-3">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp" class="form-control" value="{{ $contact->whatsapp }}">
        </div>
        <button type="submit" class="btn btn-success">Salvar Alterações</button>
    </form>
</div>
@endsection
