<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SiPinLab') · Peminjaman Laboratorium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/lab.css', 'resources/js/lab.js'])
</head>
<body>
<nav class="navbar navbar-dark navbar-sipinlab">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">SiPinLab</a>
        <div id="navAuth" class="d-flex align-items-center"></div>
    </div>
</nav>

@yield('content')

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastBox"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>