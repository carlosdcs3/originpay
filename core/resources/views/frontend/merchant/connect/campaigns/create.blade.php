@extends('merchant.layouts.app')
@section('content')
<div class="container">
    <h2>Nova Campanha</h2>
    <form method="POST" action="{{ route('merchant.connect.campaigns.store') }}">
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
            <label>Nome da Campanha</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Segmento (Audiência)</label>
            <select name="segment_id" class="form-control" required>
                @foreach($segments as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Template</label>
            <select name="template_id" class="form-control" required>
                @foreach($templates as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->channel }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-success">Criar Campanha</button>
    </form>
</div>
@endsection
