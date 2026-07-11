@extends('admin.layouts.app')

@section('panel')
@php
    $isEdit = isset($plan);
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius--10">
            <div class="card-body">
                <form method="POST" action="{{ $isEdit ? route('admin.billing.plans.update', $plan->id) : route('admin.billing.plans.store') }}">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Produto</label>
                                <select name="product_id" class="form-control" required>
                                    <option value="">Selecione</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" @selected(old('product_id', $plan->product_id ?? '') == $product->id)>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nome</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name ?? '') }}" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $plan->slug ?? '') }}" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ordem</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $plan->sort_order ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cor</label>
                                <input type="text" name="color" class="form-control" value="{{ old('color', $plan->color ?? '') }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Icone</label>
                                <input type="text" name="icon" class="form-control" value="{{ old('icon', $plan->icon ?? '') }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Badge</label>
                                <input type="text" name="badge" class="form-control" value="{{ old('badge', $plan->badge ?? '') }}">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Descricao</label>
                                <textarea name="description" class="form-control" rows="4">{{ old('description', $plan->description ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $plan->is_active ?? true))>
                                <label class="form-check-label" for="is_active">Ativo</label>
                            </div>
                        </div>
                    </div>

                    @if($features->isNotEmpty())
                        <div class="alert alert-info">
                            Features comerciais cadastradas: {{ $features->count() }}. A vinculacao por versao de plano permanece no motor comercial.
                        </div>
                    @endif

                    <button type="submit" class="btn btn--primary">{{ $isEdit ? 'Atualizar Plano' : 'Criar Plano' }}</button>
                    <a href="{{ route('admin.billing.plans.index') }}" class="btn btn--secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
