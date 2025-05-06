<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BRANACA Security Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">

<div class="container">
    <h1 class="text-center mb-4">BRANACA Security Scanner</h1>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Static Analysis -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Static Analysis</h5>
                <p class="card-text">Upload a source code file for scanning (e.g., Python, Java, JavaScript).</p>
                <form method="POST" action="{{ route('scan.static') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" class="form-control" name="code_file" accept=".py,.java,.js" required>
                    </div>

                    <div class="mb-3">
                        <label for="complexity_static" class="form-label">Complexity of Analysis:</label>
                        <select class="form-select" name="complexity" id="complexity_static" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="very_high">Very High</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Run Static Scan</button>
                </form>
            </div>
        </div>
    </div>




        <!-- Dynamic Analysis -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Dynamic Analysis</h5>
                    <p class="card-text">Enter a live IP and port for real-time scanning (e.g., 192.168.1.10:8080).</p>
                    <form method="POST" action="{{ route('scan.dynamic') }}">
                        @csrf
                        <label for="target_url">Target URL:</label>
                        <input type="url" name="target_url" required>

                        <label for="tool">Tool:</label>
                        <select name="tool" required>
                            <option value="zap">OWASP ZAP</option>
                            <option value="nikto">Nikto</option>
                        </select>

                        <div class="mb-3">
                            <label for="complexity_dynamic" class="form-label">Complexity of Analysis:</label>
                            <select class="form-select" name="complexity" id="complexity_dynamic" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="very_high">Very High</option>
                            </select>
                        </div>


                        <button type="submit">Run Scan</button>
                        
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
