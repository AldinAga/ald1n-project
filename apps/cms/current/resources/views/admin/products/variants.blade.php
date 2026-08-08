@extends('layouts.app')
@section('title', 'Varijante proizvoda')
@section('content')
<a class="back-link" href="{{ route('admin.products.edit', $product) }}">← Nazad na artikal</a>
<div class="page-heading"><div><span class="eyebrow">Konfiguracije proizvoda</span><h1>Varijante</h1><p>{{ $product->name }} · {{ $product->sku }}</p></div><span class="count-pill">{{ $product->variants->count() }} varijanti · ukupno {{ $product->stock_quantity }} kom.</span></div>
@if(!$product->type)<div class="alert error">Pre dodavanja varijanti artikal mora imati izabran tip.</div>@endif
<section class="panel form-section">
<h2>Dodaj novu varijantu</h2>
<form method="post" data-variant-form action="{{ route('admin.products.variants.store',$product) }}">@csrf
@include('admin.products.partials.variant-fields',['variant'=>null,'formPrefix'=>'new'])
<button class="button button-primary" type="submit" @disabled(!$product->type)>Dodaj varijantu</button>
</form>
</section>
@forelse($product->variants as $variant)
<section class="panel form-section" id="variant-{{ $variant->id }}">
<div class="section-heading-row"><div><h2>{{ $variant->name }}</h2><p class="muted">{{ $variant->sku }} · lager {{ $variant->stock_quantity }} · {{ number_format((float)$variant->price_amount,2,',','.') }} {{ $variant->price_currency }}</p></div><div class="tags"><span>{{ $variant->status }}</span>@if($variant->is_default)<span class="success">Podrazumevana</span>@endif</div></div>
<form method="post" data-variant-form action="{{ route('admin.products.variants.update',[$product,$variant]) }}">@csrf @method('PUT')
@include('admin.products.partials.variant-fields',['variant'=>$variant,'formPrefix'=>'variant-'.$variant->id])
<button class="button button-primary" type="submit">Sačuvaj varijantu</button>
@if(!$variant->is_default)<button class="button button-ghost" type="submit" form="default-{{ $variant->id }}">Postavi kao podrazumevanu</button>@endif
@if((int)$variant->stock_quantity===0)<button class="button button-danger" type="submit" form="archive-{{ $variant->id }}" onclick="return confirm('Arhivirati varijantu?')">Arhiviraj</button>@endif
</form>
<div class="two-column-grid compact-grid">
<form class="panel nested-panel" method="post" action="{{ route('admin.products.variants.stock',[$product,$variant]) }}">@csrf
<h3>Korekcija lagera</h3><input type="hidden" name="idempotency_key" value="{{ $stockIdempotencyKey }}-{{ $variant->id }}"><div class="field-grid"><label><span>Promena</span><input type="number" name="quantity_change" required min="-1000000" max="1000000" placeholder="npr. 5 ili -2"></label><label><span>Razlog</span><input name="note" required maxlength="1000"></label></div><button class="button button-ghost" type="submit">Primeni korekciju</button>
</form>
<form class="panel nested-panel" method="post" enctype="multipart/form-data" action="{{ route('admin.products.variants.images',[$product,$variant]) }}">@csrf
<h3>Posebne slike varijante</h3><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required><button class="button button-ghost" type="submit">Dodaj slike</button>
<div class="variant-image-strip">@foreach($variant->images as $image)<span>@if($image->url)<img src="{{ $image->url }}" alt="{{ $variant->name }}">@endif<button type="submit" form="delete-variant-image-{{ $image->id }}" aria-label="Ukloni">×</button></span>@endforeach</div>
</form>
</div>
</section>
<form id="default-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.default',[$product,$variant]) }}">@csrf</form>
<form id="archive-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.archive',[$product,$variant]) }}">@csrf @method('DELETE')</form>
@foreach($variant->images as $image)<form id="delete-variant-image-{{ $image->id }}" method="post" action="{{ route('admin.products.variants.images.delete',[$product,$variant,$image]) }}">@csrf @method('DELETE')</form>@endforeach
@empty<div class="empty-state">Još nema varijanti. Postojeći artikal nastavlja da koristi sopstvenu cenu i lager dok ne dodaš prvu varijantu.</div>@endforelse
@endsection
@push('styles')<style>.variant-image-strip{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.variant-image-strip span{position:relative;width:72px;height:58px;border:1px solid var(--line);border-radius:10px;overflow:hidden}.variant-image-strip img{width:100%;height:100%;object-fit:contain;background:var(--panel-2);padding:3px}.variant-image-strip button{position:absolute;right:2px;top:2px;border:0;border-radius:50%;width:22px;height:22px;background:#111c;color:#fff}.nested-panel{margin-top:16px}.compact-grid{align-items:start}</style>@endpush

@push('scripts')
<script>
(() => {
  document.querySelectorAll('[data-variant-form]').forEach(form => {
    const selects = new Map(Array.from(form.querySelectorAll('[data-variant-spec-field]')).map(select => [String(select.dataset.variantSpecField), select]));
    const refresh = () => {
      selects.forEach(select => {
        const parentId = String(select.dataset.parentField || '');
        if (!parentId) return;
        const parent = selects.get(parentId);
        const wrap = form.querySelector(`[data-variant-field-wrap="${select.dataset.variantSpecField}"]`);
        const parentOptionId = parent?.selectedOptions?.[0]?.dataset.optionId || '';
        let available = 0;
        Array.from(select.options).forEach((option, index) => {
          if (index === 0) return;
          const allowed = String(option.dataset.parentOptionIds || '').split(',').filter(Boolean);
          const visible = parentOptionId !== '' && allowed.includes(String(parentOptionId));
          option.hidden = !visible;
          option.disabled = !visible;
          if (visible) available++;
        });
        if (select.selectedOptions[0]?.disabled) select.value = '';
        select.disabled = parentOptionId === '' || available === 0;
        if (wrap) wrap.hidden = parentOptionId === '' || available === 0;
        wrap?.querySelectorAll('input[name^="spec_details"]').forEach(input => input.disabled = select.disabled);
      });
    };
    selects.forEach(select => select.addEventListener('change', refresh));
    refresh();
  });
})();
</script>
<script src="{{ asset('assets/js/product-media-manager.js') }}?v={{ config('app.version') }}" defer></script>
@endpush
