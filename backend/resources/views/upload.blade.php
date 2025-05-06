<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BRANACA Security Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .dot-animation {
        display: inline-block;
        animation: bounce 1.4s infinite ease-in-out both;
        font-size: 2rem;
        color: #0d6efd;
    }

    .dot-animation:nth-child(1) {
        animation-delay: -0.32s;
    }

    .dot-animation:nth-child(2) {
        animation-delay: -0.16s;
    }

    .dot-animation:nth-child(3) {
        animation-delay: 0;
    }

    @keyframes bounce {
        0%, 80%, 100% {
            transform: scale(0);
        }
        40% {
            transform: scale(1);
        }
    }
</style>

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

<!-- Global Loading Spinner (only once!) -->
<div id="loadingSpinner" class="position-fixed top-0 start-0 w-100 h-100 d-none flex-column justify-content-center align-items-center bg-light bg-opacity-75" style="z-index: 1050;">

    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
        <span class="visually-hidden">Scanning...</span>
    </div>
    <div class="mt-4 fs-5 text-secondary">Analyzing your input... please wait</div>
    <div class="mt-2">
        <span class="dot-animation">.</span>
        <span class="dot-animation">.</span>
        <span class="dot-animation">.</span>
    </div>
</div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const forms = document.querySelectorAll("form");
        const spinner = document.getElementById("loadingSpinner");

        forms.forEach(form => {
            form.addEventListener("submit", function () {
                spinner.classList.remove("d-none");
                spinner.style.opacity = 0;
                setTimeout(() => {
                    spinner.style.transition = "opacity 0.5s";
                    spinner.style.opacity = 1;
                }, 10);

                // Optional fallback
                setTimeout(() => {
                    if (!spinner.classList.contains("d-none")) {
                        spinner.innerHTML += `<div class="mt-4 text-danger">⚠️ Scan may have stalled. Please refresh.</div>`;
                    }
                }, 90000);
            });
        });
    });
</script>


</html>
