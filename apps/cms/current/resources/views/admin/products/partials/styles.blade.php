@push('head')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-catalog.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-catalog.css')) ?: config('app.version') }}">
@endpush
