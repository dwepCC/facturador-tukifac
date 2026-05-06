<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <title>Tu cuenta está en pausa - Tukifac</title>

    <style>
        :root {
            --bg: #f6f9ff;
            --text: #0b1220;
            --muted: rgba(11, 18, 32, 0.62);
            --card: rgba(255, 255, 255, 0.7);
            --card-border: rgba(255, 255, 255, 0.55);
            --shadow: 0 20px 55px rgba(17, 24, 39, 0.14);
            --green: #16a34a;
            --green-2: #22c55e;
            --purple: #7c3aed;
            --purple-2: #a78bfa;
            --radius: 1.25rem;
        }

        .tuk403, .tuk403 * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .tuk403 {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
            background:
                radial-gradient(1200px 700px at 10% 0%, rgba(34, 197, 94, 0.20), transparent 60%),
                radial-gradient(900px 600px at 90% 0%, rgba(124, 58, 237, 0.18), transparent 55%),
                radial-gradient(900px 600px at 50% 100%, rgba(34, 197, 94, 0.10), transparent 55%),
                linear-gradient(180deg, #f8fbff 0%, #f4f7ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: var(--text);
        }

        .tuk403-wrap {
            width: 100%;
            max-width: 980px;
            display: grid;
            place-items: center;
        }

        .tuk403-card {
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            width: 100%;
            padding: 2rem;
            overflow: hidden;
            position: relative;
        }

        .tuk403-card::before {
            content: "";
            position: absolute;
            inset: -2px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.18), rgba(124, 58, 237, 0.14));
            filter: blur(26px);
            opacity: 0.7;
            pointer-events: none;
        }

        .tuk403-content {
            position: relative;
            display: grid;
            gap: 1.5rem;
        }

        .tuk403-header {
            display: grid;
            gap: 0.75rem;
        }

        .tuk403-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            background: rgba(34, 197, 94, 0.10);
            border: 1px solid rgba(34, 197, 94, 0.22);
            color: #0b7a34;
            width: fit-content;
        }

        .tuk403-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
        }

        .tuk403-subtitle {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.55;
        }

        .tuk403-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 1.25rem;
            align-items: start;
        }

        .tuk403-summary {
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.55);
            padding: 1rem;
            display: grid;
            gap: 0.9rem;
        }

        .tuk403-summary-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .tuk403-amount {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--green), var(--purple));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .tuk403-meta {
            display: grid;
            gap: 0.5rem;
        }

        .tuk403-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .tuk403-meta-label {
            color: var(--muted);
        }

        .tuk403-meta-value {
            font-weight: 700;
            color: rgba(11, 18, 32, 0.86);
        }

        .tuk403-benefits {
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.55);
            padding: 1rem;
        }

        .tuk403-alert {
            border-radius: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.16), rgba(124, 58, 237, 0.08));
            border: 1px solid rgba(249, 115, 22, 0.22);
            color: rgba(11, 18, 32, 0.88);
        }

        .tuk403-alert-title {
            font-weight: 900;
            letter-spacing: -0.01em;
            margin-bottom: 0.35rem;
        }

        .tuk403-alert-head {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.35rem;
        }

        .tuk403-alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(249, 115, 22, 0.18);
            border: 1px solid rgba(249, 115, 22, 0.28);
            color: rgba(11, 18, 32, 0.88);
            flex: 0 0 auto;
        }

        .tuk403-alert-icon svg {
            width: 22px;
            height: 22px;
        }

        .tuk403-alert-text {
            color: rgba(11, 18, 32, 0.72);
            line-height: 1.5;
            margin: 0;
        }

        .tuk403-alert-amount {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: rgba(249, 115, 22, 0.18);
            border: 1px solid rgba(249, 115, 22, 0.28);
            color: rgba(11, 18, 32, 0.9);
            font-weight: 900;
        }

        .tuk403-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .tuk403-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 2.85rem;
            padding: 0 1.15rem;
            border-radius: 0.9rem;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
            will-change: transform;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        .tuk403-btn:hover {
            transform: translateY(-1px) scale(1.01);
        }

        .tuk403-btn:active {
            transform: translateY(0) scale(0.99);
        }

        .tuk403-btn-primary {
            background: linear-gradient(135deg, var(--green), var(--green-2));
            color: #fff;
            box-shadow: 0 14px 30px rgba(34, 197, 94, 0.28);
        }

        .tuk403-btn-secondary {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.12), rgba(34, 197, 94, 0.08));
            border: 1px solid rgba(124, 58, 237, 0.22);
            color: rgba(11, 18, 32, 0.88);
        }

        .tuk403-btn-link {
            background: transparent;
            border: 1px dashed rgba(11, 18, 32, 0.22);
            color: rgba(11, 18, 32, 0.85);
        }

        .tuk403-top-support {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 20;
        }

        .tuk403-top-support a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 0.95rem;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--green), rgba(124, 58, 237, 0.45));
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            box-shadow: 0 14px 32px rgba(34, 197, 94, 0.22);
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .tuk403-top-support a:hover {
            transform: translateY(-1px);
            opacity: 0.97;
        }

        .tuk403-pay-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.14), rgba(124, 58, 237, 0.14));
            border: 1px solid rgba(255, 255, 255, 0.7);
            display: grid;
            place-items: center;
        }

        .tuk403-pay-icon svg {
            width: 34px;
            height: 34px;
            color: rgba(11, 18, 32, 0.78);
        }

        @media (max-width: 860px) {
            .tuk403-grid {
                grid-template-columns: 1fr;
            }

            .tuk403-card {
                padding: 1.25rem;
            }

            .tuk403-title {
                font-size: 1.7rem;
            }

            .tuk403-buttons {
                gap: 0.6rem;
            }

            .tuk403-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="tuk403">
    @php
        $isTenantLocked = false;
        try {
            $tenantConfig = \App\Models\Tenant\Configuration::query()->first();
            if ($tenantConfig && (bool) $tenantConfig->locked_tenant) {
                $isTenantLocked = true;
            }
        } catch (\Throwable $e) {
            $isTenantLocked = false;
        }

        $planName = null;
        $dueDate = null;
        $amountPending = null;
        $statusText = null;

        try {
            $hostname = app(\Hyn\Tenancy\Environment::class)->hostname();
            if ($hostname) {
                $client = \App\Models\System\Client::query()->with(['plan'])->where('hostname_id', $hostname->id)->first();
                if ($client) {
                    $planName = $client->plan ? $client->plan->name : null;
                    $dueDate = $client->ending_billing_cycle ? \Carbon\Carbon::parse($client->ending_billing_cycle)->format('d/m/Y') : null;
                    $order = \App\Models\System\PaymentOrder::query()
                        ->where('client_id', $client->id)
                        ->whereIn('order_state_id', [1, 3, 5, 6])
                        ->orderByDesc('date_of_due')
                        ->orderByDesc('id')
                        ->first();
                    if ($order) {
                        $amountPending = $order->amount;
                        $statusText = $order->order_state ? $order->order_state->name : null;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        if ($amountPending === null) {
            $amountPending = 25.00;
        }
        if (!$planName) {
            $planName = 'Tu plan';
        }
        if (!$statusText) {
            $statusText = $isTenantLocked ? 'Vencido' : 'Inactivo';
        }

        $supportUrl = null;
        try {
            $supportPhone = '51916996847';
            $supportMessage = $isTenantLocked
                ? 'Hola, mi cuenta está en pausa por suscripción vencida. Monto pendiente S/ ' . number_format((float) $amountPending, 2, '.', '') . ($dueDate ? '. Fecha: ' . $dueDate : '') . '. Necesito ayuda para reactivar el servicio.'
                : 'Hola, necesito soporte.';
            $supportUrl = 'https://wa.me/' . $supportPhone . '?text=' . rawurlencode($supportMessage);
        } catch (\Throwable $e) {
            $supportUrl = null;
        }
    @endphp
    @if($supportUrl)
        <div class="tuk403-top-support">
            <a href="{{ $supportUrl }}" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 21l1.7-5.2a9 9 0 1 1 3.3 3.2l-5 2z"></path>
                    <path d="M9.5 10.5c.3 1.2 1.6 2.6 2.8 3.1c.5.2 1 .2 1.4-.1l.7-.5c.3-.2.7-.2 1 0l1.1.7c.4.3.5.8.3 1.2c-.4.8-1.3 1.5-2.3 1.4c-1.6-.2-3.4-1.2-4.8-2.6c-1.4-1.4-2.4-3.2-2.6-4.8c-.1-1 .6-1.9 1.4-2.3c.4-.2.9-.1 1.2.3l.7 1.1c.2.3.2.7 0 1l-.5.7c-.3.4-.3.9-.1 1.4z"></path>
                </svg>
                Soporte
            </a>
        </div>
    @endif
    <div class="tuk403-wrap">
        <div class="tuk403-card">
            <div class="tuk403-content">
                <div class="tuk403-header">
                    <span class="tuk403-badge">
                        <span style="width: 8px; height: 8px; border-radius: 999px; background: linear-gradient(135deg, var(--green), var(--purple)); display: inline-block;"></span>
                        Estado de cuenta
                    </span>
                    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <div class="tuk403-pay-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="M3 10h18"></path>
                                <path d="M7 15h.01"></path>
                                <path d="M11 15h2"></path>
                            </svg>
                        </div>
                        <div style="min-width: 260px; flex: 1;">
                            <div class="tuk403-title">Tu cuenta está en pausa</div>
                            <div class="tuk403-subtitle">Tu suscripción venció, pero puedes activarla en segundos y seguir usando el sistema sin interrupciones</div>
                        </div>
                    </div>
                </div>

                <div class="tuk403-grid">
                    <div class="tuk403-summary">
                        <div class="tuk403-summary-top">
                            <div>
                                <div class="tuk403-meta-label" style="font-weight:700;">Monto pendiente</div>
                                <div class="tuk403-amount">S/ {{ number_format((float) $amountPending, 2, '.', '') }}</div>
                            </div>
                            <div style="text-align:right;">
                                <div class="tuk403-meta-label">Estado</div>
                                <div class="tuk403-meta-value">{{ $statusText }}</div>
                            </div>
                        </div>
                        <div class="tuk403-meta">
                            <div class="tuk403-meta-row">
                                <span class="tuk403-meta-label">Plan</span>
                                <span class="tuk403-meta-value">{{ $planName }}</span>
                            </div>
                            <div class="tuk403-meta-row">
                                <span class="tuk403-meta-label">Fecha</span>
                                <span class="tuk403-meta-value">{{ $dueDate ?: '—' }}</span>
                            </div>
                        </div>
                        <div class="tuk403-buttons">
                            <a class="tuk403-btn tuk403-btn-primary" href="/cuenta/payment_index">Pagar ahora</a>
                            <a class="tuk403-btn tuk403-btn-secondary" href="/login">Volver</a>
                            @if($supportUrl)
                                <a class="tuk403-btn tuk403-btn-link" href="{{ $supportUrl }}" target="_blank">Contactar soporte (WhatsApp)</a>
                            @endif
                        </div>
                    </div>

                    <div class="tuk403-benefits">
                        <div class="tuk403-alert">
                            <div class="tuk403-alert-head">
                                <div class="tuk403-alert-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.41 3.86a2 2 0 0 0-3.12 0z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </div>
                                <div class="tuk403-alert-title">Costo de reconexión</div>
                            </div>
                            <p class="tuk403-alert-text">
                                El costo de la reconexión es de <span class="tuk403-alert-amount">S/ 50.00</span>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>
