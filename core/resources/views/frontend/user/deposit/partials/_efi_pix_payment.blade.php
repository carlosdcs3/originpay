@extends('frontend.layouts.user-v2')

@section('user_content')
<div class="row justify-content-center">
    <div class="col-xl-6 col-lg-8 col-md-10">
        <div class="card shadow-sm border-0 mt-5">
            <div class="card-header bg-white text-center py-4 border-bottom">
                <h4 class="mb-0 text-dark">Pagamento via PIX</h4>
                <p class="text-muted mb-0">Escaneie o QR Code abaixo para pagar a sua recarga de R$ {{ number_format($amount, 2, ',', '.') }}</p>
            </div>
            <div class="card-body text-center p-4">
                <div class="mb-4">
                    <img src="{{ $qrCode }}" alt="QR Code PIX" class="img-fluid rounded border p-2" style="max-width: 250px;">
                </div>
                
                <div class="mb-3">
                    <p class="text-muted mb-2">Ou use o Pix Copia e Cola:</p>
                    <div class="input-group">
                        <input type="text" class="v2-input text-center bg-light" id="pixCopiaCola" value="{{ $qrCode }}" readonly>
                        <button class="v2-btn-primary" type="button" onclick="copyPix()"><i class="fas fa-copy"></i> Copiar</button>
                    </div>
                </div>

                <div class="alert alert-info border-0 mt-4 text-start">
                    <i class="fas fa-info-circle me-2"></i> O pagamento será processado e o saldo adicionado à sua carteira assim que for confirmado.
                </div>
                
                <a href="{{ route('user.transaction.index') }}" class="btn btn-outline-secondary mt-3">Ver Minhas Transações</a>
            </div>
        </div>
    </div>
</div>

<script>
function copyPix() {
    var copyText = document.getElementById("pixCopiaCola");
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    navigator.clipboard.writeText(copyText.value).then(function() {
        notify('success', 'Pix Copia e Cola copiado com sucesso!');
    });
}
</script>
@endsection
