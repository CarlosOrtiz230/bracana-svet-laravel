a<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scan History - BRANACA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f2f4f7;
            padding: 2rem;
        }
        .scan-card {
            transition: 0.2s ease-in-out;
        }
        .scan-card:hover {
            background-color: #f8f9fa;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4 text-center">📜 Scan History</h1>

    <div class="mb-5">
        <h4 class="text-primary">🔍 ZAP Scans</h4>
        @if($zapScans->count())
            <ul class="list-group">
                @foreach($zapScans as $scan)
                    <li class="list-group-item scan-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Target:</strong> {{ $scan->target_url }}<br>
                                <strong>Date:</strong> {{ $scan->created_at->format('Y-m-d H:i') }}
                            </div>
                            <a href="{{ route('scan.results.zap', $scan->id) }}" class="btn btn-outline-primary btn-sm">View Report</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No ZAP scans available.</p>
        @endif
    </div>

    <div>
        <h4 class="text-success">🌐 Nikto Scans</h4>
        @if($niktoScans->count())
            <ul class="list-group">
                @foreach($niktoScans as $scan)
                    <li class="list-group-item scan-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Target:</strong> {{ $scan->target_url }}<br>
                                <strong>Date:</strong> {{ $scan->created_at->format('Y-m-d H:i') }}
                            </div>
                            <a href="{{ route('scan.results.nikto', $scan->id) }}" class="btn btn-outline-success btn-sm">View Report</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No Nikto scans available.</p>
        @endif
    </div>

    <div class="mt-5">
        <h4 class="text-danger">🧬 Semgrep Scans</h4>
        @if($semgrepScans->count())
            <ul class="list-group">
                @foreach($semgrepScans as $scan)
                    <li class="list-group-item scan-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>File:</strong> {{ $scan->target_file}}<br>
                                <strong>Date:</strong> {{ $scan->created_at->format('Y-m-d H:i') }}
                            </div>
                            <a href="{{ route('scan.results.semgrep', $scan->id) }}" class="btn btn-outline-danger btn-sm">View Report</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No Semgrep scans available.</p>
        @endif
    </div>
    

    <div class="text-center mt-4">
        <a href="{{ url('/') }}" class="btn btn-secondary">🏠 Back to Home</a>
    </div>

    <form method="POST" action="{{ route('scan.recoverStored') }}">
        @csrf
        <button type="submit" class="btn btn-warning">Fetch Stored Analyses</button>
    </form>
    
</div>
</body>
</html>
