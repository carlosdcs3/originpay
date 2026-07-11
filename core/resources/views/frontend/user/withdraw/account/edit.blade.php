@extends('frontend.layouts.user-v2')
@section('title', 'Editar conta de saque')

@section('content')
    <div class="v2-card">
        <div class="v2-card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div>
                <h2 class="v2-card-title mb-2 mb-md-0">Editar conta de saque</h2>
                <p class="mb-0 text-muted small">Atualize os dados usados nas liquidacoes para esta conta.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="v2-btn-secondary btn-sm" href="{{ route('user.withdraw.account.index') }}">
                    <i class="fa-solid fa-receipt"></i> Minhas contas
                </a>
            </div>
        </div>
        <div class="v2-card-body">
            <form action="{{ route('user.withdraw.account.update', $withdrawAccount->id) }}" method="POST"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <x-form.field
                            type="text"
                            name="account_name"
                            label="Nome da conta"
                            placeholder="Como voce deseja identificar esta conta"
                            :value="old('account_name', $withdrawAccount->name)"
                            :colClass="'col-md-6'"
                            :required="true"
                    />

                    @foreach($withdrawAccount->credentials as $field)
                        <x-form.field
                                :type="$field['type']"
                                :name="'credentials['.$field['name'].']'"
                                :label="ucfirst($field['name'])"
                                :placeholder="ucfirst($field['name'])"
                                :value="old('credentials.'.$field['name'], $field['value'] ?? null)"
                                :colClass="'col-md-6 v2-input-group'"
                                :required="$field['validation']"
                        />
                    @endforeach
                </div>

                <button type="submit" class="v2-btn-primary mt-3 w-100">Salvar alteracoes</button>
            </form>
        </div>
    </div>
@endsection
