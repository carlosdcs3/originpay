@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h2>Novo Template</h2>
            <form method="POST" action="{{ route('merchant.connect.templates.store') }}">
                @csrf
                <div class="mb-3">
                    <label>Canal</label>
                    <select name="channel" class="form-control">
                        <option value="email">E-mail</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="sms">SMS</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Nome</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Assunto (Apenas Email)</label>
                    <input type="text" name="subject" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Conteúdo (AST JSON)</label>
                    <textarea name="content" class="form-control" rows="15" required>{{ $defaultJson }}</textarea>
                </div>
                <button type="submit" class="btn btn-success">Salvar Rascunho</button>
            </form>
        </div>
        <div class="col-md-4">
            <h4>Variáveis Disponíveis</h4>
            <ul class="list-group">
                @foreach($variables as $key => $var)
                    <li class="list-group-item">
                        <strong>{{'{{'}}{{ $key }}{{'}}'}}</strong><br>
                        <small>{{ $var->description }} (Ex: {{ $var->example }})</small>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
