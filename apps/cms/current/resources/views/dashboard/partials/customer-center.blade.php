@php
    $portalSummary = $portal['summary'] ?? [];
    $portalOrders = $portal['orders'] ?? collect();
    $portalDocuments = $portal['documents'] ?? collect();
    $portalWarranties = $portal['warranties'] ?? collect();
    $portalCases = $portal['cases'] ?? collect();
    $portalServiceAppointments = $portal['serviceAppointments'] ?? collect();
    $portalInstallments = $portal['installments'] ?? collect();
    $portalTimeline = $portal['timeline'] ?? collect();
    $portalConversations = $portal['conversations'] ?? collect();
    $notificationPreference = $portal['notificationPreference'] ?? null;
    $preferenceFields = [
        'in_app_enabled' => ['Obaveštenja u aplikaciji', null],
        'email_enabled' => ['E-mail obaveštenja', null],
        'order_updates' => ['Status porudžbine', null],
        'payment_alerts' => ['Uplate i rokovi', null],
        'document_updates' => ['Novi dokumenti', true],
        'after_sales_updates' => ['Reklamacije i poruke', true],
        'warranty_updates' => ['Garancija i održavanje', true],
        'service_updates' => ['Servisni termini', true],
        'receivable_updates' => ['Plan otplate i opomene', true],
    ];
@endphp

<section class="a1-stack" data-customer-center-ready="1" aria-labelledby="customer-center-title">
    <div class="a1-card__head">
        <div class="a1-card__title">
            <span class="a1-eyebrow">Jedinstveni korisnički centar</span>
            <h2 class="a1-h2" id="customer-center-title">Porudžbine, dokumenti i podrška</h2>
            <p class="a1-text">Svi lični podaci i usluge sada su deo početnog univerzalnog dashboarda.</p>
        </div>
        <div class="a1-cluster">
            <a class="a1-btn a1-btn--sm" href="{{ route('portal.messages.index') }}">
                <x-icon name="mail" :size="20" :shell="true" /> Poruke
                @if(($portalSummary['unread_messages'] ?? 0) > 0)
                    <span class="a1-count">{{ $portalSummary['unread_messages'] }}</span>
                @endif
            </a>
            <a class="a1-btn a1-btn--sm" href="{{ route('account.show') }}"><x-icon name="settings" :size="20" :shell="true" /> Moj nalog</a>
        </div>
    </div>

    @if($portal['warning'] ?? null)
        <div class="a1-alert a1-alert--warning" role="status"><x-icon name="alert" :size="20" :shell="true" /><div class="a1-alert__body"><p>{{ $portal['warning'] }}</p></div></div>
    @endif

    <div class="a1-g12">
        <div class="a1-stack a1-lg-8">
            <section class="a1-card" aria-labelledby="portal-orders-title">
                <div class="a1-card__head">
                    <div class="a1-card__title">
                        <span class="a1-eyebrow">Pregled poslovanja</span>
                        <h3 class="a1-h2" id="portal-orders-title">Moje porudžbine</h3>
                        <p class="a1-text">Poslednje porudžbine sa trenutnim statusom, uplatom i tracking podacima.</p>
                    </div>
                    <a class="a1-btn a1-btn--sm" href="{{ route('orders.index') }}">Sve porudžbine</a>
                </div>
                @if($portalOrders->isNotEmpty())
                    <ul class="a1-list">
                        @foreach($portalOrders as $order)
                            <li>
                                <a class="a1-item a1-item--stack" href="{{ $order['url'] }}">
                                    <span class="a1-item__main">
                                        <span class="a1-timeline__head">
                                            <span class="a1-item__title">{{ $order['number'] }}</span>
                                            <span class="a1-pill a1-pill--{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span>
                                        </span>
                                        <span class="a1-item__sub">{{ $order['created_at']?->format('d.m.Y H:i') }}</span>
                                        <span class="a1-meta">
                                            <span><x-icon name="money" :size="16" :shell="true" />{{ number_format($order['total_rsd'], 2, ',', '.') }} RSD</span>
                                            <span><x-icon name="wallet" :size="16" :shell="true" />{{ $order['payment_label'] }}</span>
                                            <span><x-icon name="file-text" :size="16" :shell="true" />{{ $order['documents_count'] }} dok.</span>
                                            @if($order['tracking_number'])
                                                <span><x-icon name="truck" :size="16" :shell="true" />{{ $order['tracking_number'] }}</span>
                                            @endif
                                        </span>
                                        @if($order['remaining_rsd'] > 0)
                                            <progress class="a1-progress" max="100" value="{{ $order['total_rsd'] > 0 ? min(100, round($order['paid_rsd'] / $order['total_rsd'] * 100)) : 0 }}" aria-label="Plaćeno {{ $order['total_rsd'] > 0 ? min(100, round($order['paid_rsd'] / $order['total_rsd'] * 100)) : 0 }}%"></progress>
                                            <span class="a1-item__sub a1-num">Preostalo: {{ number_format($order['remaining_rsd'], 2, ',', '.') }} RSD</span>
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="a1-empty">
                        <span class="a1-empty__icon"><x-icon name="orders" :size="28" :shell="true" /></span>
                        <h3 class="a1-h3">Još nema porudžbina</h3>
                        <p>Kada kreiraš porudžbinu, ovde će se pojaviti njen kompletan status.</p>
                    </div>
                @endif
            </section>

            <section class="a1-card" aria-labelledby="portal-messages-title">
                <div class="a1-card__head">
                    <div class="a1-card__title">
                        <span class="a1-eyebrow">Direktna podrška</span>
                        <h3 class="a1-h2" id="portal-messages-title">Poruke</h3>
                        <p class="a1-text">Pošaljite pitanje ili nastavite postojeću komunikaciju.</p>
                    </div>
                    <a class="a1-btn a1-btn--primary a1-btn--sm" href="{{ route('portal.messages.index') }}"><x-icon name="mail" :size="20" :shell="true" /> Nova poruka</a>
                </div>
                @if($portalConversations->isNotEmpty())
                    <ul class="a1-list">
                        @foreach($portalConversations as $conversation)
                            <li>
                                <a class="a1-item a1-item--stack" href="{{ route('portal.messages.show', $conversation) }}">
                                    <span class="a1-item__main">
                                        <span class="a1-timeline__head">
                                            <span class="a1-item__title">{{ $conversation->subject }}</span>
                                            @if(($conversation->unread_count ?? 0) > 0)
                                                <span class="a1-count">{{ $conversation->unread_count }}</span>
                                            @endif
                                        </span>
                                        <span class="a1-item__sub">
                                            {{ \App\Models\PortalConversation::statusLabels()[$conversation->status] ?? $conversation->status }}
                                            @if($conversation->order)
                                                · {{ $conversation->order->order_number }}
                                            @endif
                                        </span>
                                        <span class="a1-text">{{ \Illuminate\Support\Str::limit((string) $conversation->latestPublicMessage?->body, 120) }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="a1-text">Nema otvorenih tema. Koristite dugme „Nova poruka“ kada vam je potrebna pomoć.</p>
                @endif
            </section>

            <section class="a1-card" aria-labelledby="portal-timeline-title">
                <div class="a1-card__head">
                    <div class="a1-card__title">
                        <span class="a1-eyebrow">Istorija aktivnosti</span>
                        <h3 class="a1-h2" id="portal-timeline-title">Vremenska linija</h3>
                        <p class="a1-text">Najnovije promene iz porudžbina, dokumenata, uplata, garancija i servisa.</p>
                    </div>
                </div>
                @if($portalTimeline->isNotEmpty())
                    <ol class="a1-timeline">
                        @foreach($portalTimeline as $event)
                            <li>
                                <span class="a1-item__icon" aria-hidden="true"><x-icon :name="$event['icon']" :size="16" :shell="true" /></span>
                                <div class="a1-timeline__body">
                                    <div class="a1-timeline__head">
                                        <strong class="a1-item__title">{{ $event['title'] }}</strong>
                                        <time class="a1-timeline__time">{{ $event['at']?->format('d.m.Y H:i') }}</time>
                                    </div>
                                    <p class="a1-text">{{ $event['description'] }}</p>
                                    @if($event['url'])
                                        <a class="a1-link a1-link--quiet" href="{{ $event['url'] }}">Otvori detalje <x-icon name="arrow-right" :size="16" :shell="true" /></a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="a1-text">Još nema događaja za prikaz.</p>
                @endif
            </section>
        </div>

        <aside class="a1-stack a1-lg-4" aria-label="Dokumenti, garancije i podešavanja">
            <section class="a1-card" aria-labelledby="portal-documents-title">
                <div class="a1-card__title">
                    <h3 class="a1-h2" id="portal-documents-title">Dokumenti</h3>
                    <p class="a1-text">Aktivne revizije spremne za preuzimanje.</p>
                </div>
                @if($portalDocuments->isNotEmpty())
                    <ul class="a1-list">
                        @foreach($portalDocuments as $document)
                            <li><a class="a1-item" href="{{ $document['url'] }}" target="_blank" rel="noopener"><span class="a1-item__icon"><x-icon name="file-text" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">{{ $document['number'] }}</span><span class="a1-item__sub">{{ ucfirst($document['type_label']) }} · {{ $document['order_number'] }}</span></span><x-icon class="a1-item__go" name="download" :size="16" :shell="true" /></a></li>
                        @endforeach
                    </ul>
                @else
                    <p class="a1-text">Nema izdatih dokumenata.</p>
                @endif
            </section>

            @if($portalInstallments->isNotEmpty())
                <section class="a1-card" aria-labelledby="portal-installments-title">
                    <div class="a1-card__title">
                        <h3 class="a1-h2" id="portal-installments-title">Predstojeće rate</h3>
                        <p class="a1-text">Sledeće obaveze iz aktivnog plana otplate.</p>
                    </div>
                    <ul class="a1-list">
                        @foreach($portalInstallments as $installment)
                            <li><a class="a1-item" href="{{ $installment['url'] }}"><span class="a1-item__icon a1-item__icon--{{ $statusTone($installment['status']) }}"><x-icon name="wallet" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title a1-num">{{ number_format($installment['remaining_rsd'], 2, ',', '.') }} RSD</span><span class="a1-item__sub">{{ $installment['order_number'] }} · {{ $installment['due_at']?->format('d.m.Y') }}</span></span><span class="a1-pill a1-pill--{{ $statusTone($installment['status']) }}">{{ $installment['status'] === 'overdue' ? 'Kasni' : 'Čeka' }}</span></a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @can('warranties.view_own')
                <section class="a1-card" aria-labelledby="portal-warranties-title">
                    <div class="a1-card__head">
                        <div class="a1-card__title">
                            <h3 class="a1-h2" id="portal-warranties-title">Garancije</h3>
                            <p class="a1-text">Aktivna pokrića i planirano održavanje.</p>
                        </div>
                        <a class="a1-link" href="{{ route('warranties.index') }}">Sve</a>
                    </div>
                    @if($portalWarranties->isNotEmpty())
                        <ul class="a1-list">
                            @foreach($portalWarranties as $warranty)
                                <li><a class="a1-item" href="{{ $warranty['url'] }}"><span class="a1-item__icon a1-item__icon--{{ $statusTone($warranty['status']) }}"><x-icon name="shield" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">{{ $warranty['product'] }}</span><span class="a1-item__sub">{{ $warranty['number'] }} · do {{ $warranty['expires_at']?->format('d.m.Y') }}</span></span><span class="a1-pill a1-pill--{{ $statusTone($warranty['status']) }}">{{ $warranty['status_label'] }}</span></a></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="a1-text">Nema izdatih garancija.</p>
                    @endif
                </section>
            @endcan

            @can('after_sales.view_own')
                <section class="a1-card" aria-labelledby="portal-cases-title">
                    <div class="a1-card__head">
                        <div class="a1-card__title">
                            <h3 class="a1-h2" id="portal-cases-title">Reklamacije i servis</h3>
                            <p class="a1-text">Otvoreni slučajevi i zakazani termini.</p>
                        </div>
                        <a class="a1-link" href="{{ route('after-sales.index') }}">Sve</a>
                    </div>
                    @if($portalCases->isNotEmpty())
                        <ul class="a1-list">
                            @foreach($portalCases as $case)
                                <li><a class="a1-item" href="{{ $case['url'] }}"><span class="a1-item__icon a1-item__icon--{{ $statusTone($case['status']) }}"><x-icon name="alert" :size="20" :shell="true" /></span><span class="a1-item__main"><span class="a1-item__title">{{ $case['number'] }}</span><span class="a1-item__sub">{{ $case['subject'] }}</span></span><span class="a1-pill a1-pill--{{ $statusTone($case['status']) }}">{{ $case['status_label'] }}</span></a></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="a1-text">Nema postprodajnih slučajeva.</p>
                    @endif
                    @if($portalServiceAppointments->isNotEmpty())
                        <ul class="a1-list">
                            @foreach($portalServiceAppointments->take(4) as $appointment)
                                <li class="a1-item a1-item--plain a1-item--static"><span class="a1-item__main"><span class="a1-item__title">{{ $appointment['number'] }}</span><span class="a1-item__sub">{{ $appointment['planned_start_at']?->format('d.m.Y H:i') ?? 'Termin nije zakazan' }} · {{ $appointment['status_label'] }}</span></span><x-icon name="truck" :size="20" :shell="true" /></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endcan

            @if($notificationPreference)
                <section class="a1-card" aria-labelledby="portal-prefs-title">
                    <div class="a1-card__title">
                        <h3 class="a1-h2" id="portal-prefs-title">Moja obaveštenja</h3>
                        <p class="a1-text">Izaberi koje opcione događaje želiš da primaš.</p>
                    </div>
                    <form method="post" action="{{ route('account.notifications') }}" class="a1-form">
                        @csrf
                        @method('put')
                        <div class="a1-prefs">
                            @foreach($preferenceFields as $field => [$fieldLabel, $defaultTrue])
                                <label class="a1-check"><input class="a1-check__input" type="checkbox" name="{{ $field }}" value="1" @checked($defaultTrue ? ($notificationPreference->{$field} ?? true) : $notificationPreference->{$field})><span class="a1-check__box" aria-hidden="true"></span><span>{{ $fieldLabel }}</span></label>
                            @endforeach
                        </div>
                        <input type="hidden" name="commission_updates" value="{{ $notificationPreference->commission_updates ? 1 : 0 }}">
                        <input type="hidden" name="stock_alerts" value="{{ $notificationPreference->stock_alerts ? 1 : 0 }}">
                        <input type="hidden" name="daily_digest" value="{{ $notificationPreference->daily_digest ? 1 : 0 }}">
                        <div><button class="a1-btn a1-btn--primary" type="submit">Sačuvaj podešavanja</button></div>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</section>
