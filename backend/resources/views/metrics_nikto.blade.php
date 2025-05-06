<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Nikto Metrics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container">
    <h2 class="mb-4">Submit Nikto HTML Report for Metrics</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('metrics.nikto') }}">
        @csrf
        <div class="mb-3">
            <label for="filename" class="form-label">Filename (only the name, not full path)</label>
            <input type="text" name="filename" id="filename" class="form-control" placeholder="nikto_report_2025-05-01_21-41-07.html" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit for Analysis</button>
    </form>
</div>
</body>
</html>
