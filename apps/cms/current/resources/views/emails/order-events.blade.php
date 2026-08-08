@php
    $productOnly = $events->isNotEmpty() && $events->every(static fn ($event): bool => str_starts_with((string) $event->event_type, 'product_'));
    $headerLabel = $productOnly ? 'Novi artikal u katalogu' : 'Obaveštenje o porudžbini';
@endphp
<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $headerLabel }}</title>
</head>
<body style="margin:0;background:#f3f5f8;font-family:Arial,sans-serif;color:#202735">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f5f8;padding:24px"><tr><td align="center">
<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #dce2ea">
<tr><td style="background:#172033;color:#fff;padding:24px 30px"><strong style="font-size:20px">{{ config('app.name','Ald1n CMS') }}</strong><div style="color:#cbd5e1;margin-top:6px">{{ $headerLabel }}</div></td></tr>
<tr><td style="padding:28px 30px">
<p style="margin-top:0">Poštovani{{ $recipientName ? ' '.$recipientName : '' }},</p>
@foreach($events as $event)
    @php
        $isProductEvent = str_starts_with((string) $event->event_type, 'product_');
        $metadata = is_array($event->metadata_json) ? $event->metadata_json : [];
        $productImage = trim((string) ($metadata['product_image_url'] ?? ''));
        $productDescription = trim((string) ($metadata['product_description'] ?? ''));
        $productPrice = trim((string) ($metadata['product_price_formatted'] ?? ''));
        $productSku = trim((string) ($metadata['product_sku'] ?? ''));
    @endphp
    <div style="border:1px solid #dce2ea;border-radius:12px;padding:18px;margin:16px 0;background:#f8fafc;overflow:hidden">
        <strong style="display:block;font-size:18px;margin-bottom:12px">{{ $event->subject }}</strong>

        @if($isProductEvent && $productImage !== '')
            <div style="margin:-2px -2px 16px;background:#fff;border:1px solid #e5eaf0;border-radius:10px;padding:12px;text-align:center">
                <img src="{{ $productImage }}" alt="{{ $metadata['product_name'] ?? $event->subject }}" width="560" style="display:block;width:100%;max-width:560px;max-height:420px;height:auto;object-fit:contain;margin:0 auto;border:0">
            </div>
        @endif

        @if($isProductEvent)
            @if($productSku !== '')<div style="color:#667085;font-size:12px;margin-bottom:8px">Šifra artikla: <strong style="color:#344054">{{ $productSku }}</strong></div>@endif
            @if($productDescription !== '')
                <p style="margin:0 0 14px;line-height:1.6;color:#344054">{{ $productDescription }}</p>
            @else
                <p style="margin:0 0 14px;line-height:1.55">{{ $event->message }}</p>
            @endif
            @if($productPrice !== '')
                <div style="margin:0 0 16px;padding:12px 14px;border-radius:10px;background:#eef5ff;color:#174ea6;font-size:18px;font-weight:700">Cena: {{ $productPrice }}</div>
            @endif
        @else
            <p style="margin:0 0 12px;line-height:1.55">{{ $event->message }}</p>
        @endif

        @if($event->action_url)
            <a href="{{ $event->action_url }}" style="display:inline-block;background:#1f6feb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px">{{ $isProductEvent ? 'Pogledaj artikal' : 'Otvori porudžbinu' }}</a>
        @endif
    </div>
@endforeach
<p style="color:#667085;font-size:12px;margin-bottom:0">Ova poruka je automatski poslata iz {{ config('app.name','Ald1n CMS') }} sistema.</p>
</td></tr></table></td></tr></table>
</body>
</html>
