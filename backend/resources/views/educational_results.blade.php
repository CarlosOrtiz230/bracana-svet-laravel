<!-- resources/views/educational_results.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nikto Vulnerability Explanations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">
<div class="container">
    <h1 class="text-center mb-4">Nikto Vulnerability Explanations</h1>

    @if (isset($feedback) && count($feedback) > 0)
        @foreach ($feedback as $entry)
            <div class="card mb-3 shadow-sm">
                <div class="card-header fw-bold">Issue</div>
                <div class="card-body">
                    <p><strong>Description:</strong> {{ $entry['original'] }}</p>
                    <p><strong>Explanation:</strong> {{ $entry['explanation'] }}</p>
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info text-center">
            No explanations available.
        </div>
    @endif

    <div class="text-center mt-4">
        <a href="{{ url('/') }}" class="btn btn-secondary">Back to Scanner</a>
    </div>
</div>
</body>
</html>
