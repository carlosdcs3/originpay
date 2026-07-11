@extends('backend.settings.layout')
@section('setting_title', 'Empresa')

@section('setting_action')
    <button class="btn btn-primary shadow-sm" type="submit" form="empresaForm">
        <i class="la la-save me-1"></i> Salvar Alterações
    </button>
@endsection

@section('setting_content')

<form id="empresaForm" action="{{ route('admin.settings.site.update', 1) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold text-uppercase text-muted" style="letter-spacing: 0.05em; font-size: 0.75rem;">Dados Cadastrais</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Nome da empresa</label>
                            <input type="text" class="form-control form-control-sm" name="company_name" value="OriginPay" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Razão Social</label>
                            <input type="text" class="form-control form-control-sm" name="legal_name" value="OriginPay Pagamentos S.A.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">CNPJ</label>
                            <input type="text" class="form-control form-control-sm" name="cnpj" value="00.000.000/0001-00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Inscrição Estadual (opcional)</label>
                            <input type="text" class="form-control form-control-sm" name="state_registration" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Telefone</label>
                            <input type="text" class="form-control form-control-sm" name="phone" value="+55 (11) 99999-9999">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-semibold mb-1">E-mail Institucional</label>
                            <input type="email" class="form-control form-control-sm" name="email" value="contato@originpay.com.br">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold text-uppercase text-muted" style="letter-spacing: 0.05em; font-size: 0.75rem;">Endereço</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-semibold mb-1">CEP</label>
                            <input type="text" class="form-control form-control-sm" name="zip_code" value="00000-000">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-muted small fw-semibold mb-1">Endereço</label>
                            <input type="text" class="form-control form-control-sm" name="address" value="Av. Faria Lima, 1000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-semibold mb-1">Cidade</label>
                            <input type="text" class="form-control form-control-sm" name="city" value="São Paulo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-semibold mb-1">Estado</label>
                            <input type="text" class="form-control form-control-sm" name="state" value="SP">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-semibold mb-1">País</label>
                            <input type="text" class="form-control form-control-sm" name="country" value="Brasil">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold text-uppercase text-muted" style="letter-spacing: 0.05em; font-size: 0.75rem;">Branding Institucional</h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold mb-1">Logo Principal</label>
                        <div class="border rounded-3 p-2 text-center" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.1) !important;">
                            <img src="{{ asset('assets/global/images/logo.png') }}" alt="Logo" class="img-fluid" style="max-height: 35px;">
                        </div>
                        <input type="file" class="form-control form-control-sm mt-1" name="logo">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold mb-1">Logo Escura</label>
                        <div class="border rounded-3 p-2 text-center" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.1) !important;">
                            <img src="{{ asset('assets/global/images/logo_white.png') }}" alt="Logo Escura" class="img-fluid" style="max-height: 35px;">
                        </div>
                        <input type="file" class="form-control form-control-sm mt-1" name="logo_white">
                    </div>

                    <div>
                        <label class="form-label text-muted small fw-semibold mb-1">Favicon</label>
                        <div class="border rounded-3 p-2 text-center" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.1) !important;">
                            <img src="{{ asset('assets/global/images/favicon.png') }}" alt="Favicon" class="img-fluid" style="max-height: 28px;">
                        </div>
                        <input type="file" class="form-control form-control-sm mt-1" name="favicon">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
