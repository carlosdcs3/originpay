@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Novo Contato</h2>
    <form method="POST" action="{{ route('merchant.connect.contacts.store') }}">
        @csrf
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" name="name" class="form-control">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="mb-3">
            <label>WhatsApp</label>
            <input type="text" name="whatsapp" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Salvar</button>
    </form>
</div>
@endsection
