<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PinLab') · Peminjaman Laboratorium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/lab.css', 'resources/js/lab.js'])
</head>
<body>
<a class="skip-link" href="#konten">Lewati ke konten utama</a>
<header class="site-head">
    <div class="container d-flex flex-wrap align-items-center gap-2 gap-md-4 py-3">
        <a class="brand d-flex align-items-center gap-2 fs-5" href="{{ url('/') }}" aria-label="PinLab beranda">
            <span class="brand-mark" aria-hidden="true"></span>PinLab
        </a>
        <nav class="site-nav d-flex flex-wrap" id="navLinks" aria-label="Navigasi utama"></nav>
        <div id="navAuth" class="d-flex align-items-center gap-2 ms-auto"></div>
    </div>
</header>

<main id="konten">
    @yield('content')
</main>

<footer class="site-foot mt-5">
    <div class="container py-3">
        PinLab, portal peminjaman laboratorium. Operasional 08.00 sampai 17.00.
    </div>
</footer>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastBox" aria-live="polite"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
