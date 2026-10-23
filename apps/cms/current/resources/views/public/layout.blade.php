<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>@yield('title', 'Javni katalog') · AP Computers</title>
    <style>
        :root{color-scheme:dark;--bg:#101a30;--panel:#1a2745;--ink:#edf2ff;--muted:#b4c0d9;--line:#364c74;--accent:#759aff}
        *{box-sizing:border-box}html{font-family:Inter,system-ui,-apple-system,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink)}
        body{margin:0;min-width:280px;background:radial-gradient(ellipse at top left,#243c73 0,transparent 55%),var(--bg);min-height:100vh}
        a{color:#a8c3ff}a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible{outline:3px solid #ffc75b;outline-offset:3px}
        .shell{width:min(1180px,calc(100% - 32px));margin:auto}.top{border-bottom:1px solid #4c6590;background:#14203bdc;backdrop-filter:blur(12px)}
        .nav{display:flex;gap:16px;align-items:center;flex-wrap:wrap;min-height:76px}.brand{margin-right:auto;font-weight:800;color:#fff;text-decoration:none;font-size:1.2rem}
        .navlinks{display:flex;gap:8px;flex-wrap:wrap}.pill,.button{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #56729f;border-radius:14px;padding:11px 15px;background:linear-gradient(135deg,#344565,#182747);text-decoration:none;color:#fff;cursor:pointer;font:inherit;font-weight:650}
        .button.primary{background:linear-gradient(130deg,#6d96ff,#4257d9);border-color:#8cb4ff;color:#0b1535}.button:disabled{opacity:.5;cursor:not-allowed}
        .intro{padding:34px 0 20px}h1{font-size:clamp(1.6rem,4vw,2.7rem);margin:4px 0 12px}h2{font-size:1.26rem;margin:0 0 14px}h3{margin:0 0 12px}
        p,.meta{color:var(--muted);line-height:1.6}p{overflow-wrap:anywhere}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(235px,100%),1fr));gap:18px}
        .card,.panel{border:1px solid var(--line);border-radius:20px;background:linear-gradient(135deg,#273656cc,#13213bdc);box-shadow:0 14px 30px #03081439;padding:21px;min-width:0}
        .card{display:flex;flex-direction:column;gap:10px}.card-title{font-size:1.14rem;font-weight:800;line-height:1.3;overflow-wrap:anywhere}.grow{flex:1}.price{font-size:1.25rem;font-weight:800;color:#e2eaff}
        .image{height:180px;background:#0d172c;border-radius:12px;display:grid;place-items:center;overflow:hidden}.image img{width:100%;height:100%;object-fit:contain}
        .toolbar{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:24px}.field{display:flex;flex-direction:column;gap:6px;flex:1;min-width:min(210px,100%)}
        label{font-weight:650}input,textarea,select{width:100%;border:1px solid #506792;border-radius:12px;background:#142341;color:#fff;padding:13px 14px;font:inherit;max-width:100%}
        .stack{display:grid;gap:16px}.two{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(280px,0.7fr);gap:18px}.row{display:flex;justify-content:space-between;gap:16px;align-items:center}
        .divider{height:1px;background:var(--line);margin:14px 0}.errors{border:1px solid #fa8896;background:#5a203258;padding:12px 16px;border-radius:12px;margin:14px 0}
        .notice{border:1px solid #7bae95;background:#1d654040;padding:12px 16px;border-radius:12px;margin:14px 0}.small{font-size:.9rem;color:var(--muted)}
        footer{border-top:1px solid var(--line);padding:26px 0;margin-top:36px;color:var(--muted);font-size:.9rem}
        .list-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;border-bottom:1px solid #455674;padding:16px 0}
        .long{overflow-wrap:anywhere}.muted{color:var(--muted)}.columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
        @media(max-width:720px){.shell{width:min(100% - 24px,1180px)}.two,.columns{grid-template-columns:1fr}.nav{padding:12px 0}.row{align-items:flex-start;flex-wrap:wrap}}
        @media(prefers-reduced-motion:no-preference){.card,.button{transition:border-color .15s,box-shadow .15s,transform .15s}.card:hover{border-color:#7d9ade;box-shadow:0 16px 34px #03081460}.button:hover{border-color:#b1c5ff;transform:translateY(-1px)}}
        @media(forced-colors:active){.card,.panel,.button,input{border:1px solid CanvasText}}
    </style>
    @yield('head')
</head>
<body>
<header class="top"><div class="shell nav"><a class="brand" href="{{ route('public.catalog') }}">AP.Computers · Javni katalog</a>
<nav class="navlinks" aria-label="Glavna navigacija"><a class="pill" href="{{ route('public.catalog') }}">Proizvodi</a><a class="pill" href="{{ route('public.cart') }}">Korpa @if(($cartCount ?? 0)>0)({{ $cartCount }})@endif</a><a class="pill" href="{{ route('login') }}">Prijava</a></nav></div></header>
<main class="shell" id="main"><div class="intro"><h1>@yield('heading', 'Javni katalog')</h1><p>@yield('subtitle', 'Kupovina bez obavezne registracije, po punoj prodajnoj ceni.')</p></div>
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="errors" role="alert"><strong>Proveri unete podatke:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main><footer><div class="shell">AP.Computers · Za pitanja o narudžbini koristi kontakt trgovca prikazan tokom poručivanja. · <a href="{{ route('login') }}">Interna prijava</a></div></footer>
</body></html>
