{{-- Jedna akcija reda porudžbine. $kind: confirm | complete. $menu=true je prikazuje kao stavku menija "Još". --}}
@php($btn = ($menu ?? false) ? 'a1-nav-dd__item' : 'a1-btn a1-btn--sm')
@if($kind === 'confirm')
    {{-- Potvrda traži token svežine porudžbine, pa lista samo vodi na panel potvrde na detalju (bez 409 iz liste). --}}
    <a class="{{ $btn }}" href="{{ route('admin.orders.show', $order) }}#order-confirm" aria-label="Potvrdi porudžbinu: {{ $order->order_number }}">Potvrdi</a>
@else
    <a class="{{ $btn }}" href="{{ route('admin.orders.show', $order) }}#order-workspace-logistics-actions" aria-label="Potvrdi isporuku: {{ $order->order_number }}">Potvrdi isporuku</a>
@endif
