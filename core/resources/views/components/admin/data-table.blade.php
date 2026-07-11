@props(['headers', 'emptyMessage' => 'Nenhum registro encontrado', 'emptySubmessage' => 'Ajuste os filtros acima', 'hasData' => false, 'paginator' => null])

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table admin-data-table mb-0">
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @if($hasData)
                        {{ $slot }}
                    @else
                        <tr>
                            <td colspan="{{ count($headers) }}">
                                <div class="admin-empty-state">
                                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                    <h5>{{ $emptyMessage }}</h5>
                                    <p>{{ $emptySubmessage }}</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @if($paginator && $paginator->hasPages())
    <div class="card-footer py-3">
        {{ $paginator->links() }}
    </div>
    @endif
</div>
