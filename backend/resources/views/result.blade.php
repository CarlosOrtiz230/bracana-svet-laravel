<!DOCTYPE html>
<html>
<head>
    <title>Scan Results</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .scan-item { margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .severity { font-weight: bold; color: darkred; }
    </style>
</head>
<body>
    <h1>Scan Results</h1>

    @if(!empty($tool))
        <h3>Tool Used: {{ strtoupper($tool) }}</h3>
    @endif

    @if(is_array($results) && count($results))
        <ul>
            @foreach($results as $item)
                <li class="scan-item">
                    <div class="severity">
                        <strong>Severity:</strong> {{ $item['risk'] ?? $item['severity'] ?? 'Unknown' }}
                    </div>
                    <div>
                        <strong>Alert:</strong> {{ $item['alert'] ?? $item['message'] ?? 'No details' }}
                    </div>
                    @if(!empty($item['description']))
                        <div><strong>Description:</strong> {{ $item['description'] }}</div>
                    @endif
                    @if(!empty($item['uri']))
                        <div><strong>URI:</strong> {{ $item['uri'] }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p>No issues found or invalid report format.</p>
    @endif

    <p><a href="{{ url('/') }}">Run another scan</a></p>
</body>
</html>
