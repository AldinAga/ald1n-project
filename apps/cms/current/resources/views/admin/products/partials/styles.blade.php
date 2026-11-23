@push('head')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-products.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-products.css')) ?: config('app.version') }}">
@endpush
