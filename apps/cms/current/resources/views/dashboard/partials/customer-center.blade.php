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
@endphp

<section class="dashboard-operations-section universal-customer-section" data-customer-center-ready="1">
    <div class="dashboard-section-title universal-section-heading">
        <div>
            <span class="eyebrow">Jedinstveni korisnički centar</span>
            <h2>Porudžbine, dokumenti i podrška</h2>
            <p>Svi lični podaci i usluge sada su deo početnog univerzalnog dashboarda.</p>
        </div>
        <div class="universal-section-actions">
            <a class="button button-ghost button-small" href="{{ route('portal.messages.index') }}">
                <x-icon name="mail" /> Poruke
                @if(($portalSummary['unread_messages'] ?? 0) > 0)
                    <span class="notification-count">{{ $portalSummary['unread_messages'] }}</span>
                @endif
            </a>
            <a class="button button-ghost button-small" href="{{ route('account.show') }}"><x-icon name="settings" /> Moj nalog</a>
        </div>
    </div>

    @if($portal['warning'] ?? null)
        <div class="alert warning">{{ $portal['warning'] }}</div>
    @endif

    <div class="portal-layout">
        <div class="portal-main-column">
            <section class="panel portal-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="eyebrow">Pregled poslovanja</span>
                        <h2>Moje porudžbine</h2>
                        <p class="muted">Poslednje porudžbine sa trenutnim statusom, uplatom i tracking podacima.</p>
                    </div>
                    <a class="button button-ghost button-small" href="{{ route('orders.index') }}">Sve porudžbine</a>
                </div>
                <div class="portal-order-list">
                    @forelse($portalOrders as $order)
                        <a class="portal-order-card" href="{{ $order['url'] }}">
                            <div class="portal-order-head">
                                <div>
                                    <strong>{{ $order['number'] }}</strong>
                                    <small>{{ $order['created_at']?->format('d.m.Y H:i') }}</small>
                                </div>
                                <span class="portal-status tone-{{ $statusTone($order['status']) }}">{{ $order['status_label'] }}</span>
                            </div>
                            <div class="portal-order-meta">
                                <span><x-icon name="money" size="15" />{{ number_format($order['total_rsd'], 2, ',', '.') }} RSD</span>
                                <span><x-icon name="wallet" size="15" />{{ $order['payment_label'] }}</span>
                                <span><x-icon name="file-text" size="15" />{{ $order['documents_count'] }} dok.</span>
                                @if($order['tracking_number'])
                                    <span><x-icon name="truck" size="15" />{{ $order['tracking_number'] }}</span>
                                @endif
                            </div>
                            @if($order['remaining_rsd'] > 0)
                                <div class="portal-progress">
                                    <span style="width:{{ $order['total_rsd'] > 0 ? min(100, round($order['paid_rsd'] / $order['total_rsd'] * 100)) : 0 }}%"></span>
                                </div>
                                <small class="portal-balance">Preostalo: {{ number_format($order['remaining_rsd'], 2, ',', '.') }} RSD</small>
                            @endif
                        </a>
                    @empty
                        <div class="empty-state">
                            <x-icon name="orders" size="34" />
                            <h3>Još nema porudžbina</h3>
                            <p>Kada kreiraš porudžbinu, ovde će se pojaviti njen kompletan status.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="panel portal-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="eyebrow">Direktna podrška</span>
                        <h2>Poruke</h2>
                        <p class="muted">Pošaljite pitanje ili nastavite postojeću komunikaciju.</p>
                    </div>
                    <a class="button button-primary button-small" href="{{ route('portal.messages.index') }}"><x-icon name="mail" /> Nova poruka</a>
                </div>
                <div class="portal-thread-list compact">
                    @forelse($portalConversations as $conversation)
                        <a class="portal-thread-card {{ ($conversation->unread_count ?? 0) > 0 ? 'is-unread' : '' }}" href="{{ route('portal.messages.show', $conversation) }}">
                            <div class="portal-thread-head">
                                <strong>{{ $conversation->subject }}</strong>
                                @if(($conversation->unread_count ?? 0) > 0)
                                    <span class="notification-count">{{ $conversation->unread_count }}</span>
                                @endif
                            </div>
                            <small>
                                {{ \App\Models\PortalConversation::statusLabels()[$conversation->status] ?? $conversation->status }}
                                @if($conversation->order)
                                    · {{ $conversation->order->order_number }}
                                @endif
                            </small>
                            <p>{{ \Illuminate\Support\Str::limit((string) $conversation->latestPublicMessage?->body, 120) }}</p>
                        </a>
                    @empty
                        <div class="empty-inline">Nema otvorenih tema. Koristite dugme „Nova poruka“ kada vam je potrebna pomoć.</div>
                    @endforelse
                </div>
            </section>

            <section class="panel portal-panel">
                <div class="section-heading-row">
                    <div>
                        <span class="eyebrow">Istorija aktivnosti</span>
                        <h2>Vremenska linija</h2>
                        <p class="muted">Najnovije promene iz porudžbina, dokumenata, uplata, garancija i servisa.</p>
                    </div>
                </div>
                <div class="portal-timeline">
                    @forelse($portalTimeline as $event)
                        <article class="portal-timeline-event">
                            <span class="portal-timeline-icon"><x-icon name="{{ $event['icon'] }}" size="16" /></span>
                            <div>
                                <div class="portal-timeline-head">
                                    <strong>{{ $event['title'] }}</strong>
                                    <time>{{ $event['at']?->format('d.m.Y H:i') }}</time>
                                </div>
                                <p>{{ $event['description'] }}</p>
                                @if($event['url'])
                                    <a href="{{ $event['url'] }}">Otvori detalje <x-icon name="arrow-right" size="14" /></a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="muted">Još nema događaja za prikaz.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="portal-side-column">
            <section class="panel portal-panel">
                <div class="section-heading-row">
                    <div>
                        <h2>Dokumenti</h2>
                        <p class="muted">Aktivne revizije spremne za preuzimanje.</p>
                    </div>
                </div>
                <div class="portal-compact-list">
                    @forelse($portalDocuments as $document)
                        <a href="{{ $document['url'] }}" target="_blank" rel="noopener">
                            <span class="portal-list-icon"><x-icon name="file-text" /></span>
                            <div>
                                <strong>{{ $document['number'] }}</strong>
                                <small>{{ ucfirst($document['type_label']) }} · {{ $document['order_number'] }}</small>
                            </div>
                            <x-icon name="download" size="16" />
                        </a>
                    @empty
                        <p class="muted">Nema izdatih dokumenata.</p>
                    @endforelse
                </div>
            </section>

            @if($portalInstallments->isNotEmpty())
                <section class="panel portal-panel portal-attention-panel">
                    <div class="section-heading-row">
                        <div>
                            <h2>Predstojeće rate</h2>
                            <p class="muted">Sledeće obaveze iz aktivnog plana otplate.</p>
                        </div>
                    </div>
                    <div class="portal-compact-list">
                        @foreach($portalInstallments as $installment)
                            <a href="{{ $installment['url'] }}">
                                <span class="portal-list-icon tone-{{ $statusTone($installment['status']) }}"><x-icon name="wallet" /></span>
                                <div>
                                    <strong>{{ number_format($installment['remaining_rsd'], 2, ',', '.') }} RSD</strong>
                                    <small>{{ $installment['order_number'] }} · {{ $installment['due_at']?->format('d.m.Y') }}</small>
                                </div>
                                <span class="portal-status tone-{{ $statusTone($installment['status']) }}">{{ $installment['status'] === 'overdue' ? 'Kasni' : 'Čeka' }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @can('warranties.view_own')
                <section class="panel portal-panel">
                    <div class="section-heading-row">
                        <div>
                            <h2>Garancije</h2>
                            <p class="muted">Aktivna pokrića i planirano održavanje.</p>
                        </div>
                        <a class="button button-ghost button-small" href="{{ route('warranties.index') }}">Sve</a>
                    </div>
                    <div class="portal-compact-list">
                        @forelse($portalWarranties as $warranty)
                            <a href="{{ $warranty['url'] }}">
                                <span class="portal-list-icon tone-{{ $statusTone($warranty['status']) }}"><x-icon name="shield" /></span>
                                <div>
                                    <strong>{{ $warranty['product'] }}</strong>
                                    <small>{{ $warranty['number'] }} · do {{ $warranty['expires_at']?->format('d.m.Y') }}</small>
                                </div>
                                <span class="portal-status tone-{{ $statusTone($warranty['status']) }}">{{ $warranty['status_label'] }}</span>
                            </a>
                        @empty
                            <p class="muted">Nema izdatih garancija.</p>
                        @endforelse
                    </div>
                </section>
            @endcan

            @can('after_sales.view_own')
                <section class="panel portal-panel">
                    <div class="section-heading-row">
                        <div>
                            <h2>Reklamacije i servis</h2>
                            <p class="muted">Otvoreni slučajevi i zakazani termini.</p>
                        </div>
                        <a class="button button-ghost button-small" href="{{ route('after-sales.index') }}">Sve</a>
                    </div>
                    <div class="portal-compact-list">
                        @forelse($portalCases as $case)
                            <a href="{{ $case['url'] }}">
                                <span class="portal-list-icon tone-{{ $statusTone($case['status']) }}"><x-icon name="alert" /></span>
                                <div>
                                    <strong>{{ $case['number'] }}</strong>
                                    <small>{{ $case['subject'] }}</small>
                                </div>
                                <span class="portal-status tone-{{ $statusTone($case['status']) }}">{{ $case['status_label'] }}</span>
                            </a>
                        @empty
                            <p class="muted">Nema postprodajnih slučajeva.</p>
                        @endforelse
                    </div>
                    @if($portalServiceAppointments->isNotEmpty())
                        <div class="portal-service-stack">
                            @foreach($portalServiceAppointments->take(4) as $appointment)
                                <article>
                                    <span><x-icon name="truck" /></span>
                                    <div>
                                        <strong>{{ $appointment['number'] }}</strong>
                                        <small>{{ $appointment['planned_start_at']?->format('d.m.Y H:i') ?? 'Termin nije zakazan' }} · {{ $appointment['status_label'] }}</small>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endcan

            @if($notificationPreference)
                <section class="panel portal-panel">
                    <div class="section-heading-row">
                        <div>
                            <h2>Moja obaveštenja</h2>
                            <p class="muted">Izaberi koje opcione događaje želiš da primaš.</p>
                        </div>
                    </div>
                    <form method="post" action="{{ route('account.notifications') }}" class="portal-preferences">
                        @csrf
                        @method('put')
                        <label><input type="checkbox" name="in_app_enabled" value="1" @checked($notificationPreference->in_app_enabled)><span>Obaveštenja u aplikaciji</span></label>
                        <label><input type="checkbox" name="email_enabled" value="1" @checked($notificationPreference->email_enabled)><span>E-mail obaveštenja</span></label>
                        <label><input type="checkbox" name="order_updates" value="1" @checked($notificationPreference->order_updates)><span>Status porudžbine</span></label>
                        <label><input type="checkbox" name="payment_alerts" value="1" @checked($notificationPreference->payment_alerts)><span>Uplate i rokovi</span></label>
                        <label><input type="checkbox" name="document_updates" value="1" @checked($notificationPreference->document_updates ?? true)><span>Novi dokumenti</span></label>
                        <label><input type="checkbox" name="after_sales_updates" value="1" @checked($notificationPreference->after_sales_updates ?? true)><span>Reklamacije i poruke</span></label>
                        <label><input type="checkbox" name="warranty_updates" value="1" @checked($notificationPreference->warranty_updates ?? true)><span>Garancija i održavanje</span></label>
                        <label><input type="checkbox" name="service_updates" value="1" @checked($notificationPreference->service_updates ?? true)><span>Servisni termini</span></label>
                        <label><input type="checkbox" name="receivable_updates" value="1" @checked($notificationPreference->receivable_updates ?? true)><span>Plan otplate i opomene</span></label>
                        <input type="hidden" name="commission_updates" value="{{ $notificationPreference->commission_updates ? 1 : 0 }}">
                        <input type="hidden" name="stock_alerts" value="{{ $notificationPreference->stock_alerts ? 1 : 0 }}">
                        <input type="hidden" name="daily_digest" value="{{ $notificationPreference->daily_digest ? 1 : 0 }}">
                        <button class="button button-primary" type="submit">Sačuvaj podešavanja</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</section>
