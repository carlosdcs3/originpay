@extends('backend.layouts.app')

@section('title', 'Financial Anomalies & Operations')

@section('content')
<div class="row">
    <!-- Financial Health Score Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2 border-left-{{ $health['status'] == 'Healthy' ? 'success' : ($health['status'] == 'Warning' ? 'warning' : 'danger') }}">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1 text-{{ $health['status'] == 'Healthy' ? 'success' : ($health['status'] == 'Warning' ? 'warning' : 'danger') }}">
                            Financial Health Score
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $health['score'] }} / 100</div>
                        <div class="text-xs mt-1">{{ $health['status'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-heartbeat fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Anomalies Breakdown -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Open Anomalies (Critical/High)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $counts['CRITICAL'] ?? 0 }} Critical / {{ $counts['HIGH'] ?? 0 }} High
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Open Anomalies (Med/Low)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $counts['MEDIUM'] ?? 0 }} Med / {{ $counts['LOW'] ?? 0 }} Low
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Resolved (Last 7 Days)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $resolvedLast7Days }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Anomalies Detected</h6>
        <div class="dropdown no-arrow">
            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-filter fa-sm fa-fw text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                <div class="dropdown-header">Filter by Status:</div>
                <a class="dropdown-item" href="{{ route('admin.compliance.anomalies') }}">Open Anomalies</a>
                <a class="dropdown-item" href="{{ route('admin.compliance.anomalies', ['status' => 'resolved']) }}">Resolved Anomalies</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Detected</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Description</th>
                        <th>Suggested Actions</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($anomalies as $anomaly)
                        <tr>
                            <td>{{ $anomaly->detected_at->diffForHumans() }}<br><small>{{ $anomaly->detected_at }}</small></td>
                            <td>{{ $anomaly->type }}</td>
                            <td>
                                @if($anomaly->severity == 'CRITICAL') <span class="badge bg-danger">CRITICAL</span>
                                @elseif($anomaly->severity == 'HIGH') <span class="badge bg-warning text-dark">HIGH</span>
                                @elseif($anomaly->severity == 'MEDIUM') <span class="badge bg-info text-dark">MEDIUM</span>
                                @else <span class="badge bg-secondary">{{ $anomaly->severity }}</span>
                                @endif
                            </td>
                            <td>{{ $anomaly->description }}</td>
                            <td>
                                @if($anomaly->suggested_actions)
                                    <ul class="mb-0 pl-3">
                                    @foreach($anomaly->suggested_actions as $action)
                                        <li><code>{{ $action }}</code></li>
                                    @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($anomaly->resolved_at)
                                    <span class="badge bg-success">Resolved</span><br>
                                    <small>{{ $anomaly->resolved_at->diffForHumans() }}</small>
                                @else
                                    <span class="badge bg-danger">Open</span>
                                @endif
                            </td>
                            <td>
                                @if(!$anomaly->resolved_at)
                                    <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#resolveModal{{ $anomaly->id }}">
                                        Resolve
                                    </button>
                                    
                                    <!-- Modal -->
                                    <div class="modal fade" id="resolveModal{{ $anomaly->id }}" tabindex="-1" role="dialog">
                                      <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                          <form action="{{ route('admin.compliance.anomalies.resolve', $anomaly->id) }}" method="POST">
                                              @csrf
                                              <div class="modal-header">
                                                <h5 class="modal-title">Resolve Anomaly #{{ $anomaly->id }}</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                  <span aria-hidden="true">&times;</span>
                                                </button>
                                              </div>
                                              <div class="modal-body">
                                                <p><strong>Description:</strong> {{ $anomaly->description }}</p>
                                                <div class="form-group">
                                                    <label>Resolution Notes</label>
                                                    <textarea class="form-control" name="resolution_notes" required rows="3" placeholder="How was this fixed?"></textarea>
                                                </div>
                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-success">Mark as Resolved</button>
                                              </div>
                                          </form>
                                        </div>
                                      </div>
                                    </div>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="alert('Notes: {{ addslashes($anomaly->resolution_notes) }}')">View Notes</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No anomalies found. Everything is looking good!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $anomalies->links() }}
    </div>
</div>
@endsection
