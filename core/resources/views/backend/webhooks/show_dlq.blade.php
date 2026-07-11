@extends('backend.layouts.app')

@section('title', 'DLQ Event Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5>DLQ Item: {{ $dlq->id }} (Provider: {{ $dlq->provider }})</h5>
            </div>
            <div class="card-body">
                <h6>Payload (Masked for Security)</h6>
                <pre class="bg-dark text-light p-3 rounded"><code>{{ $payloadMasked }}</code></pre>

                <h6 class="mt-4">Headers (Masked)</h6>
                <pre class="bg-dark text-light p-3 rounded"><code>{{ $headersMasked }}</code></pre>

                <h6 class="mt-4 text-danger">Error Message</h6>
                <div class="alert alert-danger">
                    <strong>Class:</strong> {{ $dlq->error_class }}<br>
                    <strong>Message:</strong> {{ $dlq->error_message }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Metadata & Actions</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item"><strong>Event ID:</strong> {{ $dlq->event_id }}</li>
                    <li class="list-group-item"><strong>Attempts:</strong> {{ $dlq->attempts }}</li>
                    <li class="list-group-item"><strong>Created:</strong> {{ $dlq->created_at }}</li>
                    <li class="list-group-item"><strong>Resolved:</strong> {{ $dlq->resolved_at ?? 'Pending' }}</li>
                </ul>

                @if(!$dlq->resolved_at)
                <form action="{{ route('admin.webhooks.reprocessSingle', $dlq->id) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100" onclick="return dsConfirmForm(event, this.closest('form'), { title: 'Dispatch Reprocess', text: 'Dispatch for reprocessing? This action will be audited.', confirmBtnText: 'Reprocess', confirmBtnClass: 'btn-primary' })">
                        <i class="fas fa-sync"></i> Reprocess Item
                    </button>
                </form>
                @endif
            </div>
        </div>

        @if(!$dlq->resolved_at)
        <div class="card">
            <div class="card-header bg-warning">
                <h5>Manual Resolution</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.webhooks.resolveManual', $dlq->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="dlq">
                    <div class="mb-3">
                        <label>Reason for manual resolution (Required)</label>
                        <textarea name="reason" class="form-control" rows="3" required minlength="5"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100" onclick="return dsConfirmForm(event, this.closest('form'), { title: 'Confirm manual resolution', text: 'Confirm manual resolution? This will be audited.', confirmBtnText: 'Mark as Resolved', confirmBtnClass: 'btn-warning' })">Mark as Resolved</button>
                </form>
            </div>
        </div>
        @else
        <div class="alert alert-success">
            <strong>Item is Resolved.</strong><br>
            If manual: Admin ID {{ $dlq->resolution_admin_id }}<br>
            Reason: {{ $dlq->resolution_reason }}
            
            <hr>
            <form action="{{ route('admin.webhooks.reprocessSingle', $dlq->id) }}" method="POST" class="mt-2">
                @csrf
                <div class="mb-2">
                    <label>Reason to Override Resolution:</label>
                    <input type="text" name="reason" class="form-control form-control-sm" required>
                </div>
                <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return dsConfirmForm(event, this.closest('form'), { title: 'Force Reprocess', text: 'Force reprocess a resolved item?', confirmBtnText: 'Force Reprocess', confirmBtnClass: 'btn-danger' })">Force Reprocess</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection

