<!DOCTYPE html>
<html>
<head>
    <title>Scan Results</title>
</head>
<body>
    <h1>Scan Results</h1>

    @if(is_array($results) && count($results))
        <ul>
            @foreach($results as $item)
                <li>
                    <strong>Severity:</strong> {{ $item['severity'] ?? 'Unknown' }} <br>
                    <strong>Message:</strong> {{ $item['message'] ?? 'No details' }}
                </li>
            @endforeach
        </ul>
    @else
        <p>No issues found or invalid report format.</p>
    @endif

    <a href="/">Run another scan</a>
</body>
</html>
