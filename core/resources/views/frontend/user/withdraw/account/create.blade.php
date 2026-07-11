@extends('frontend.layouts.user-v2')
@section('title', 'Nova conta de saque')

@section('content')
    <div class="v2-card">
        <div class="v2-card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2 class="v2-card-title mb-0">Nova conta de saque</h2>
                <p class="mb-0 text-muted small">Informe o metodo e os dados necessarios para receber seus saques.</p>
            </div>
            <a class="v2-btn-secondary btn-sm" href="{{ route('user.withdraw.account.index') }}">
                <i class="fa-solid fa-receipt"></i> Minhas contas
            </a>
        </div>
        <div class="v2-card-body bg-main">
            <form action="{{ route('user.withdraw.account.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="method-select" class="v2-label">Metodo de saque</label>
                            <select class="v2-input" id="method-select" name="method_id">
                                <option disabled selected>Selecionar metodo</option>
                                @foreach($withdrawMethods as $method)
                                    <option value="{{ $method->id }}">{{ ucfirst($method->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="account-name" class="v2-label">Nome da conta</label>
                            <input type="text" class="v2-input" id="accountName" name="account_name"
                                   placeholder="Como voce deseja identificar esta conta" required>
                        </div>
                    </div>
                </div>

                <div class="row" id="credential-fields"></div>

                <button type="submit" class="v2-btn-primary mt-3 w-100">Salvar conta de saque</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#method-select').on('change', function () {
                let methodId = $(this).val();
                let url = "{{ route('user.withdraw.credentials.fields', ':method_id') }}".replace(':method_id', methodId);

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function (data) {
                        $('#credential-fields').html(data.html);
                        $('#accountName').val(data.method_name);
                    },
                    error: function () {
                        notifyEvs('error', 'Nao foi possivel carregar os campos deste metodo.');
                    }
                });
            });
        });
    </script>
@endpush
