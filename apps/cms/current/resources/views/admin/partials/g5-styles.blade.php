{{-- Kobalt G5 page styles (users, groups, portal, warranties, inventory, stock, audit). Loaded after ald1n-admin.css. --}}
@push('head')
<link rel="stylesheet" href="{{ asset('assets/css/ald1n-admin-g5.css') }}?v={{ @filemtime(public_path('assets/css/ald1n-admin-g5.css')) ?: config('app.version') }}">
@endpush
