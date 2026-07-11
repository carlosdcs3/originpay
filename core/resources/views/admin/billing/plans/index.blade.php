@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius--10">
            <div class="card-body p-0">
                <div class="table-responsive--md table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Plano</th>
                            <th>Produto</th>
                            <th>Status</th>
                            <th>Badge</th>
                            <th>Acoes</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td data-label="Ordem">{{ $plan->sort_order }}</td>
                                <td data-label="Plano">
                                    <span class="font-weight-bold">{{ $plan->name }}</span>
                                    <br>
                                    <small class="text-muted">{{ $plan->slug }}</small>
                                </td>
                                <td data-label="Produto">{{ $plan->product->name ?? '-' }}</td>
                                <td data-label="Status">
                                    @if($plan->is_active)
                                        <span class="badge badge--success">Ativo</span>
                                    @else
                                        <span class="badge badge--warning">Inativo</span>
                                    @endif
                                </td>
                                <td data-label="Badge">{{ $plan->badge ?: '-' }}</td>
                                <td data-label="Acoes">
                                    <a href="{{ route('admin.billing.plans.edit', $plan->id) }}" class="icon-btn" data-toggle="tooltip" title="" data-original-title="Editar">
                                        <i class="la la-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">Nenhum plano encontrado</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.billing.plans.create') }}" class="btn btn-sm btn--primary box--shadow1 text--small">
        <i class="fa fa-fw fa-plus"></i>Criar Novo Plano
    </a>
@endpush
