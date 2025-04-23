<!DOCTYPE html>
<html>
<head>
    <title>BRANACA File Scanner</title>
</head>
<body>
    <h1>Upload a File for Security Scan</h1>

    @if(session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ route('scan.run') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="code_file" required>
        <button type="submit">Scan File</button>
    </form>
</body>
</html>
