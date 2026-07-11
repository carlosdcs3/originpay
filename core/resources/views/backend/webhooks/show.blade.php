@extends('backend.layouts.app')

@section('title', 'Webhook Event Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Event: {{ $event->event_id }}</h5>
                <span class="badge bg-info">{{ $event->status }}</span>
            </div>
            <div class="card-body">
                <h6>Payload (Masked for Security)</h6>
                <pre class="bg-dark text-light p-3 rounded"><code>{{ $payloadMasked }}</code></pre>

                <h6 class="mt-4">Headers (Masked)</h6>
                <pre class="bg-dark text-light p-3 rounded"><code>{{ $headersMasked }}</code></pre>

                @if($event->last_error)
                <h6 class="mt-4 text-danger">Last Error</h6>
                <div class="alert alert-danger">{{ $event->last_error }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Metadata</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Provider:</strong> {{ $event->provider }}</li>
                    <li class="list-group-item"><strong>Attempts:</strong> {{ $event->attempts }}</li>
                    <li class="list-group-item"><strong>Created:</strong> {{ $event->created_at }}</li>
                    <li class="list-group-item"><strong>Processed:</strong> {{ $event->processed_at ?? 'N/A' }}</li>
                </ul>
            </div>
        </div>

        @if(!$event->resolution_admin_id && $event->status != 'PROCESSED')
        <div class="card">
            <div class="card-header bg-warning">
                <h5>Manual Resolution</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.webhooks.resolveManual', $event->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="event">
                    <div class="mb-3">
                        <label>Reason for manual resolution (Required)</label>
                        <textarea name="reason" class="form-control" rows="3" required minlength="5"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100" onclick="return dsConfirmForm(event, this.closest('form'), { title: 'Confirm manual resolution', text: 'Confirm manual resolution? This will be audited.', confirmBtnText: 'Mark as Resolved', confirmBtnClass: 'btn-warning' })">Mark as Resolved</button>
                </form>
            </div>
        </div>
        @elseif($event->resolution_admin_id)
        <div class="alert alert-success mt-3">
            <strong>Resolved Manually</strong><br>
            Admin ID: {{ $event->resolution_admin_id }}<br>
            Reason: {{ $event->resolution_reason }}
        </div>
        @endif
    </div>
</div>
@endsection

