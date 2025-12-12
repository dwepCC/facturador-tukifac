<header class="header">
    <div class="logo-container">        
        <div class="sidebar-toggle" data-toggle-class="sidebar-left-collapsed" data-target="html"
            data-fire-event="sidebar-left-toggle">
            <i class="fas fa-angle-left" aria-label="Toggle sidebar"></i>
            <i class="fas fa-angle-right" aria-label="Toggle sidebar"></i>
        </div>        
        <div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html"
            data-fire-event="sidebar-left-opened">
            <div style="width: 24px; height: 24px; display: flex; align-items: center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon icon-tabler icons-tabler-outline icon-tabler-grid-dots">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M5 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M19 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M5 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M19 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M5 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                    <path d="M19 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                </svg>
            </div>
            <h2>Modulos</h2> 
        </div>
        {{--<tenant-dialog-header-menu></tenant-dialog-header-menu>--}}
        {{--tukifac--}}
            <div class="d-none ml-1 d-lg-block" style="height: inherit;">
                <a href="/documents/create" title="Nuevo comprobante" data-placement="bottom" data-toggle="tooltip" class="topbar-links"><span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M14 3v4a1 1 0 0 0 1 1h4"></path><path d="M19 12v7a1.78 1.78 0 0 1 -3.1 1.4a1.65 1.65 0 0 0 -2.6 0a1.65 1.65 0 0 1 -2.6 0a1.65 1.65 0 0 0 -2.6 0a1.78 1.78 0 0 1 -3.1 -1.4v-14a2 2 0 0 1 2 -2h7l5 5v4.25"></path></svg>
                    </span> <span>NC</span>
                </a>
                
                <a href="/sale-notes" title="Notas de Venta" data-placement="bottom" data-toggle="tooltip" class="topbar-links"><span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M5 4v17l2 -2l2 2l2 -2l2 2l2 -2l2 2l2 -2v-17z"></path><path d="M14 8h-4"></path><path d="M14 12h-4"></path><path d="M14 16h-4"></path></svg>
                    </span> <span>NV</span>
                </a>

                <a href="/pos" title="Punto de venta" data-placement="bottom" data-toggle="tooltip" class="topbar-links"><span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 640 640" fill="currentColor"><path d="M0 72C0 58.7 10.7 48 24 48L69.3 48C96.4 48 119.6 67.4 124.4 94L162.6 304L472.4 304C483.9 304 493.8 295.8 496 284.5L528.4 115.5C530.9 102.5 543.5 94 556.5 96.5C569.5 99 578 111.6 575.5 124.6L543.1 293.6C536.6 327.5 506.9 352 472.4 352L171.4 352L176.5 380.3C178.6 391.7 188.5 400 200.1 400L456 400C469.3 400 480 410.7 480 424C480 437.3 469.3 448 456 448L200.1 448C165.3 448 135.5 423.1 129.3 388.9L77.2 102.6C76.5 98.8 73.2 96 69.3 96L24 96C10.7 96 0 85.3 0 72zM160 528C160 501.5 181.5 480 208 480C234.5 480 256 501.5 256 528C256 554.5 234.5 576 208 576C181.5 576 160 554.5 160 528zM384 528C384 501.5 405.5 480 432 480C458.5 480 480 501.5 480 528C480 554.5 458.5 576 432 576C405.5 576 384 554.5 384 528zM320 64C333.3 64 344 74.7 344 88L344 136L392 136C405.3 136 416 146.7 416 160C416 173.3 405.3 184 392 184L344 184L344 232C344 245.3 333.3 256 320 256C306.7 256 296 245.3 296 232L296 184L248 184C234.7 184 224 173.3 224 160C224 146.7 234.7 136 248 136L296 136L296 88C296 74.7 306.7 64 320 64z"></path></svg>
                    </span> <span>POS</span>
                </a>
            </div>

        {{--end tukifac--}}

        @if ($tenant_show_ads && $url_tenant_image_ads)
            <div class="ms-3 me-3">
                <img src="{{$url_tenant_image_ads}}" style="max-height: 50px; max-width: 500px;">
            </div>
        @endif

        <!-- @if(config('configuration.multi_user_enabled'))
            <tenant-multi-users-change-client></tenant-multi-users-change-client>
        @endif -->

        <div class="logo-container-mobile">
            <a href="{{route('tenant.dashboard.index')}}" class="logo pt-2 pt-md-0">
                {{--@if($vc_company->logo)
                    <img src="{{ asset('storage/uploads/logos/' . $vc_company->logo) }}" alt="Logo" class="logo-light"
                        style="{{ $vc_company->logo_dark ? '' : '--show-light-logo: block;' }}" />
                @else--}}
                    <img src="{{ asset('logo/Tukifac-large-ver-2.webp') }}" alt="Logo" />
                {{--@endif

                @if($vc_company->logo_dark)
                    <img src="{{ asset('storage/uploads/logos/' . $vc_company->logo_dark) }}" alt="Logo" class="logo-dark" />
                @endif--}}
            </a>
        </div>

        <div class="options-user-mobile">
            <i class="fas fa-bars"></i>
        </div>

        <!--tukifac-->
        <div class="payment-status-card" id="paymentStatusCard">
            <div class="payment-status-header">
                <div class="">
                <span class="days-count" id="plan_name">Plan: Cargando...</span><span id="days_indicator" class="info-row"></span>
                </div>
                <span class="payment-status" id="status_plan">Cargando...</span>
            </div>
            <div class="payment-due-date">
                Fecha de pago: <span class="date" id="payment_date">Cargando...</span>
            </div>

            <div class="card-content" id="cardContent">
                {{--<div class="info-row" id="days_info_row" style="display: none;">
                    <strong>Estado del pago:</strong>
                    <span id="days_info" class="days-status">Cargando...</span>
                </div>--}}
                
                {{--<div class="pending-payment-section" style="display: none;">
                    <div class="alert alert-warning">
                        <strong>Tienes un pago pendiente</strong>
                    </div>
                </div>--}}
            </div>
        </div>
        <!--end tukifac-->
        
    </div>
    <div class="header-right">
        <div class="dropdown-menu-mobile" style="display: none;">
            <div class="user-content">
                <div class="close-container-user">
                    <i class="fas fa-times"></i>
                </div>
                <div>

                </div>
                <a href="#" class="user-profile-content">
                    <div class="profile-info" data-lock-name="{{ $vc_user->email }}"
                        data-lock-email="{{ $vc_user->email }}">
                        <span class="name">{{ $vc_user->name }}</span>
                        <span class="role">{{ $vc_user->email }}</span>
                    </div>
                    <figure class="profile-picture">
                        {{-- <img src="{{asset('img/%21logged-user.jpg')}}" alt="Profile" class="rounded-circle"
                            data-lock-picture="img/%21logged-user.jpg" /> --}}
                        <div class="border rounded-circle text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-user-square-rounded">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 13a3 3 0 1 0 0 -6a3 3 0 0 0 0 6z" />
                                <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
                                <path d="M6 20.05v-.05a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v.05" />
                            </svg>
                        </div>
                    </figure>
                    {{-- <i class="fa custom-caret"></i> --}}
                </a>
            </div>
            <ul class="pendingWork-container">
                <li class="li-title-mobile">
                    <h4>Pendientes</h4>
                </li>
                @if($vc_document > 0)
                    <li>
                        <a href="{{route('tenant.documents.not_sent')}}"
                            class="notification-icon text-secondary navigation-options" data-toggle="tooltip"
                            data-placement="bottom" title="Comprobantes no enviados/por enviar">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-bell">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path
                                        d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                                    <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                                </svg>
                                <span class="ms-2">Comprobantes no enviados</span>
                                <span
                                    class="badge badge-pill badge-danger badge-up cart-item-count">{{ $vc_document }}</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 6l6 6l-6 6" />
                            </svg>
                        </a>
                    </li>
                @endif
                <li>
                    <a href="{{ route('tenant_orders_index') }}"
                        class="notification-icon text-secondary navigation-options" data-toggle="tooltip"
                        data-placement="bottom" title="Pedidos pendientes">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M17 17h-11v-14h-2" />
                                <path d="M6 5l14 1l-1 7h-13" />
                            </svg>
                            <span class="ms-2">Pedidos pendientes</span>
                            <span class="badge badge-pill badge-info badge-up cart-item-count">{{ $vc_orders }}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M9 6l6 6l-6 6" />
                        </svg>
                    </a>
                </li>
                @if(in_array('cuenta', $vc_modules))
                    @if(in_array('account_users_list', $vc_module_levels))
                        <li>
                            <a role="menuitem" href="{{route('tenant.payment.index')}}"
                                class="notification-icon text-secondary navigation-options">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-receipt-dollar me-2">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" />
                                        <path
                                            d="M14.8 8a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                                        <path d="M12 6v10" />
                                    </svg>
                                    <span>Mis Pagos</span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M9 6l6 6l-6 6" />
                                </svg>
                            </a>
                        </li>
                    @endif
                @endif
            </ul>

            <ul class="pendingWork-container">
                <li class="li-title-mobile">
                    <h4>Sistema</h4>
                </li>
                @php
                    $is_pse = $vc_company->send_document_to_pse;
                    $environment = 'SUNAT';
                    $is_ose = ($vc_company->soap_send_id === '02') ? true : false;
                    if ($is_pse) {
                        $environment = 'PSE';
                    }
                    if ($is_ose) {
                        $environment = 'OSE';
                    }
                    if ($is_ose && $is_pse) {
                        $environment = 'OSE-PSE';
                    }
                @endphp
                @if($vc_company->soap_type_id == "01")
                    <li>
                        <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif"
                            class="notification-icon text-secondary navigation-options" data-toggle="tooltip"
                            data-placement="bottom"
                            title="{{$environment}}: ENTORNO DE DEMOSTRACIÓN, pulse para ir a configuración"
                            style="background-color: transparent !important;">
                            <span class="options-sunat">
                                <i class="fas fa-2x fa-toggle-off me-2" style="font-size: 20px;"></i>
                                <span class="ms-2" style="display: flex; flex-direction: column;">
                                    <span>DEMO</span>
                                    <span>SUNAT Entorno de Demostración</span>
                                </span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 6l6 6l-6 6" />
                            </svg>
                        </a>
                    </li>
                @elseif($vc_company->soap_type_id == "02")
                    <li>
                        <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif"
                            class="notification-icon text-secondary navigation-options" data-toggle="tooltip"
                            data-placement="bottom"
                            title="{{$environment}}: ENTORNO DE PRODUCCIÓN, pulse para ir a configuración">
                            <span class="options-sunat">
                                <i class="text-success fas fa-2x fa-toggle-on me-2"
                                    style="font-size: 20px; color: #28a745 !important"></i>
                                <span class="ms-2" style="display: flex; flex-direction: column;">
                                    <span>PROD</span>
                                    <span>SUNAT Entorno de Demostración</span>
                                </span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 6l6 6l-6 6" />
                            </svg>
                        </a>
                    </li>
                @else
                    <li>
                        <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif"
                            class="notification-icon text-secondary navigation-options" data-toggle="tooltip"
                            data-placement="bottom" title="INTERNO: ENTORNO DE PRODUCCIÓN, pulse para ir a configuración">
                            <span class="options-sunat">
                                <i class="text-info fas fa-2x fa-toggle-on me-2"
                                    style="font-size: 20px; color: #398bf7!important;"></i>
                                <span class="ms-2" style="display: flex; flex-direction: column;">
                                    <span>INT</span>
                                    <span>SUNAT Entorno de Demostración</span>
                                </span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 6l6 6l-6 6" />
                            </svg>
                        </a>
                    </li>
                @endif
                {{--<li>
                    <a class="style-switcher-open notification-icon text-secondary navigation-options" href="#">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-paint me-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M5 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                                <path d="M19 6h1a2 2 0 0 1 2 2a5 5 0 0 1 -5 5l-5 0v2" />
                                <path
                                    d="M10 15m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" />
                            </svg>
                            Estilos y temas
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M9 6l6 6l-6 6" />
                        </svg>
                    </a>
                </li>--}}
            </ul>

            <ul class="log-out-container">
                <li>
                    <a class="nav-link" href="{{ route('tenant.profile.index') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 7a4 4 0 1 0 0 8a4 4 0 0 0 0 -8" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                        </svg>
                        Perfil
                    </a>
                </li>
                <li class="btn-primary" role="menuitem" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <a>
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none"
                            stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-logout me-2">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                            <path d="M9 12h12l-3 -3" />
                            <path d="M18 15l3 -3" />
                        </svg>
                        Cerrar Sesión
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
        <ul class="notifications mx-2">
            <li class="lt-soporte">
                <a target="_blank" href="https://wa.me/51916996847" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; background: linear-gradient(135deg, #28a745, #28a745); border-radius: 0.375rem; padding: 0.5rem 0.8rem; text-decoration: none; color: white; font-weight: 500;">
                    <i class="fab fa-whatsapp" style="font-size: 1.1rem;"></i>
                    Soporte
                </a>
            </li>
            @php
                $is_pse = $vc_company->send_document_to_pse;
                $environment = 'SUNAT';
                $is_ose = ($vc_company->soap_send_id === '02') ? true : false;
                if ($is_pse) {
                    $environment = 'PSE';
                }
                if ($is_ose) {
                    $environment = 'OSE';
                }
                if ($is_ose && $is_pse) {
                    $environment = 'OSE-PSE';
                }

                $productionClass = ($vc_company->soap_type_id == "02" && $is_ose) ? 'btn-success' : 'btn-primary';
            @endphp
            @if($vc_company->soap_type_id == "1")
                <li>
                    <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif"
                        class="btn-sunat btn-danger" data-toggle="tooltip" data-placement="bottom"
                        title="Clic para ver o cambiar la configuración del entorno y el tipo de conexión">
                        <span class="btn-title">Modo: DEMO</span>
                        <span style="font-size: 12px;">Conectado a {{ $environment }}</span>
                    </a>
                </li>
            @elseif($vc_company->soap_type_id == "02")
                <li>
                    <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif"
                        class="btn-sunat {{ $productionClass }}" data-toggle="tooltip" data-placement="bottom"
                        title="Clic para ver o cambiar la configuración del entorno y el tipo de conexión">
                        <span class="btn-title">PRODUCCIÓN</span>
                        <span style="font-size: 12px;">Conectado a {{ $environment }}</span>
                    </a>
                </li>
            @else
                <li>
                    <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif"
                        class="btn-sunat btn-info" data-toggle="tooltip" data-placement="bottom"
                        title="Clic para ver o cambiar la configuración del entorno y el tipo de conexión">
                        <span class="btn-title">Modo: INTERNO</span>
                        <span style="font-size: 12px;">Conectado a SERVIDOR</span>
                    </a>
                </li>
            @endif

            @php
                $certDue = $vc_company->certificate_due ?? null;
                $daysRemaining = null;
                $certBg = '#6c757d';
                if ($certDue) {
                    try {
                        $end = \Carbon\Carbon::parse($certDue)->startOfDay();
                        $now = \Carbon\Carbon::now()->startOfDay();
                        $daysRemaining = $now->diffInDays($end, false);
                    } catch (\Exception $e) {
                        $daysRemaining = null;
                    }
                }
                if (!is_null($daysRemaining)) {
                    if ($daysRemaining <= 7) {
                        $certBg = '#dc3545';
                    } elseif ($daysRemaining <= 30) {
                        $certBg = '#ffc107';
                    } else {
                        $certBg = '#28a745';
                    }
                }
            @endphp
            <li>
                <a href="@if(in_array('configuration', $vc_modules)){{route('tenant.companies.create')}}@else # @endif"
                   class="btn-sunat"
                   data-toggle="tooltip" data-placement="bottom"
                   title="Vencimiento de certificado"
                   style="background: {{ $certBg }}; border-color: {{ $certBg }}; color: #fff;">
                    <span class="btn-title">Certificado</span>
                    <span style="font-size: 12px;">
                        @if(!$certDue)
                            Sin fecha
                        @else
                            @if($daysRemaining < 0)
                                Vencido ({{ abs($daysRemaining) }} d)
                            @else
                                {{ $daysRemaining }} días restantes
                            @endif
                        @endif
                    </span>
                </a>
            </li>
        </ul>

        @inject('systemUser', 'App\Models\System\User')
        @php
            $supportUser = $systemUser::first();
            $hasSupportContact = $supportUser && ($supportUser->phone || $supportUser->whatsapp_number || $supportUser->address_contact);
        @endphp
        
        @if(isset($hasSupportContact) && $hasSupportContact)
        <span class="separator"></span>
        <ul class="notifications">
            <li class="m-0">
            <a role="menuitem"  class="notification-icon text-secondary"  onclick="toggleSupportSidebar()" title="Soporte" data-toggle="tooltip">
                <svg width="22" height="22" viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g id="support" fill="currentColor" transform="translate(42.666667, 42.666667)">
                            <path d="M379.734355,174.506667 C373.121022,106.666667 333.014355,-2.13162821e-14 209.067688,-2.13162821e-14 C85.1210217,-2.13162821e-14 45.014355,106.666667 38.4010217,174.506667 C15.2012632,183.311569 -0.101643453,205.585799 0.000508304259,230.4 L0.000508304259,260.266667 C0.000508304259,293.256475 26.7445463,320 59.734355,320 C92.7241638,320 119.467688,293.256475 119.467688,260.266667 L119.467688,230.4 C119.360431,206.121456 104.619564,184.304973 82.134355,175.146667 C86.4010217,135.893333 107.307688,42.6666667 209.067688,42.6666667 C310.827688,42.6666667 331.521022,135.893333 335.787688,175.146667 C313.347976,184.324806 298.68156,206.155851 298.667688,230.4 L298.667688,260.266667 C298.760356,283.199651 311.928618,304.070103 332.587688,314.026667 C323.627688,330.88 300.801022,353.706667 244.694355,360.533333 C233.478863,343.50282 211.780225,336.789048 192.906491,344.509658 C174.032757,352.230268 163.260418,372.226826 167.196286,392.235189 C171.132153,412.243552 188.675885,426.666667 209.067688,426.666667 C225.181549,426.577424 239.870491,417.417465 247.041022,402.986667 C338.561022,392.533333 367.787688,345.386667 376.961022,317.653333 C401.778455,309.61433 418.468885,286.351502 418.134355,260.266667 L418.134355,230.4 C418.23702,205.585799 402.934114,183.311569 379.734355,174.506667 Z M76.8010217,260.266667 C76.8010217,269.692326 69.1600148,277.333333 59.734355,277.333333 C50.3086953,277.333333 42.6676884,269.692326 42.6676884,260.266667 L42.6676884,230.4 C42.6676884,224.302667 45.9205765,218.668499 51.2010216,215.619833 C56.4814667,212.571166 62.9872434,212.571166 68.2676885,215.619833 C73.5481336,218.668499 76.8010217,224.302667 76.8010217,230.4 L76.8010217,260.266667 Z M341.334355,230.4 C341.334355,220.97434 348.975362,213.333333 358.401022,213.333333 C367.826681,213.333333 375.467688,220.97434 375.467688,230.4 L375.467688,260.266667 C375.467688,269.692326 367.826681,277.333333 358.401022,277.333333 C348.975362,277.333333 341.334355,269.692326 341.334355,260.266667 L341.334355,230.4 Z"></path>
                        </g>
                    </g>
                </svg>
            </a>
            </li>
        </ul>
        @endif
        <span class="separator"></span>
        <ul class="notifications">
            <li>
                <a href="{{ route('tenant_orders_index') }}" class="notification-icon text-secondary"
                    data-toggle="tooltip" data-placement="bottom" title="Pedidos pendientes">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                        <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                        <path d="M17 17h-11v-14h-2" />
                        <path d="M6 5l14 1l-1 7h-13" />
                    </svg>
                    <span class="badge badge-pill badge-info badge-up cart-item-count">{{ $vc_orders }}</span>
                </a>
            </li>
        </ul>

        @if($vc_document > 0)
            <span class="separator"></span>
            <ul class="notifications">
                <li>
                    <a href="{{route('tenant.documents.not_sent')}}" class="notification-icon text-secondary"
                        data-toggle="tooltip" data-placement="bottom" title="Comprobantes no enviados/por enviar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-bell">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path
                                d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                            <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                        </svg>
                        <span class="badge badge-pill badge-danger badge-up cart-item-count">{{ $vc_document }}</span>
                    </a>
                </li>
            </ul>
        @endif

        @if($vc_document_regularize_shipping > 0)
            <span class="separator"></span>
            <ul class="notifications">
                <li>
                    <a href="{{route('tenant.documents.regularize_shipping')}}" class="notification-icon text-secondary"
                        data-toggle="tooltip" data-placement="bottom" title="Comprobantes pendientes de rectificación">
                        <i class="fas fa-exclamation-triangle text-secondary"></i>
                        <span
                            class="badge badge-pill badge-danger badge-up cart-item-count">{{ $vc_document_regularize_shipping }}</span>
                    </a>
                </li>
            </ul>
        @endif

        @if(in_array('reports', $vc_modules) && $vc_finished_downloads > 0)
            <span class="separator"></span>
            <ul class="notifications">
                <li>

                    <a href="{{route('tenant.reports.download-tray.index')}}" class="notification-icon text-secondary"
                        data-toggle="tooltip" data-placement="bottom" title="Bandeja de descargas (Reportes procesados)">
                        <i class="fas fa-file-download text-secondary"></i>
                        <span
                            class="badge badge-pill badge-info badge-up cart-item-count">{{ $vc_finished_downloads }}</span>
                    </a>
                </li>
            </ul>
        @endif
        <span class="separator"></span>
        <div id="userbox" class="userbox">
            <a href="#" class="user-profile-content check-double" style="cursor: pointer;">
                <div class="profile-info profile-info-pc" data-lock-name="{{ $vc_user->email }}"
                    data-lock-email="{{ $vc_user->email }}">
                    <span class="name">{{ $vc_user->name }}</span>
                    <span class="role">{{ $vc_user->email }}</span>
                </div>
                <figure class="profile-picture">
                    {{-- <img src="{{asset('img/%21logged-user.jpg')}}" alt="Profile" class="rounded-circle"
                        data-lock-picture="img/%21logged-user.jpg" /> --}}
                    <div class="border rounded-circle text-center name-initials-container" style="width: 25px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon icon-tabler icons-tabler-outline icon-tabler-user-square-rounded svg-profile">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 13a3 3 0 1 0 0 -6a3 3 0 0 0 0 6z" />
                            <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
                            <path d="M6 20.05v-.05a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v.05" />
                        </svg>
                        @php
                            $userName = $vc_user->name;
                            $nameParts = explode(' ', trim($userName));
                            if (count($nameParts) >= 2) {
                                // Si hay al menos 2 palabras, tomar la primera letra de las primeras dos
                                $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                            } else {
                                // Si solo hay una palabra, tomar las dos primeras letras
                                $initials = strtoupper(substr($userName, 0, 2));
                            }
                        @endphp
                        <span class="name-initials" style="display: none;">{{ $initials }}</span>
                    </div>
                </figure>
                {{-- <i class="fa custom-caret"></i> --}}
             </a>
            <div class="dropdown-menu-desktop">
                <ul class="list-unstyled mb-0">
                    <li class="user-profile-li" style="display: none">
                        <a class="" href="#" style="text-decoration: none;">                        
                            <div class="profile-info " data-lock-name="{{ $vc_user->email }}"
                                data-lock-email="{{ $vc_user->email }}">
                                <span class="name text-start">{{ $vc_user->name }}</span>
                                <span class="role text-start">{{ $vc_user->email }}</span>
                            </div>
                        </a>
                    </li>
                    <li class="divider divider-theme-black" style="display: none"></li>
                                        <!--tukifac-->
                    @if(in_array('users', $vc_module_levels))
                    <li>
                        <a class="nav-link" href="{{route('tenant.users.index')}}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-users">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                            </svg>
                            Usuarios</a>
                    </li> 
                @endif
                <li>
                    <a class="nav-link" href="{{ route('tenant.profile.index') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 7a4 4 0 1 0 0 8a4 4 0 0 0 0 -8" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                        </svg>
                        Perfil
                    </a>
                </li>
                                @if(in_array('users_establishments', $vc_module_levels))
                    <li>
                        <a class="nav-link" href="{{route('tenant.establishments.index')}}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-list-numbers">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M11 6h9" />
                                <path d="M11 12h9" />
                                <path d="M12 18h8" />
                                <path d="M4 16a2 2 0 1 1 4 0c0 .591 -.5 1 -1 1.5l-3 2.5h4" />
                                <path d="M6 10v-6l-2 2" />
                            </svg>
                            Sucursales & Series</a>
                    </li>
                @endif
                
                               
                    <li>
                        <a class="nav-link" href="https://www.mediafire.com/file/0nngrr7hgmdrkvd/app-tukifac.apk/file">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-packages">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M7 16.5l-5 -3l5 -3l5 3v5.5l-5 3z"></path>
                                <path d="M2 13.5v5.5l5 3"></path>
                                <path d="M7 16.545l5 -3.03"></path>
                                <path d="M17 16.5l-5 -3l5 -3l5 3v5.5l-5 3z"></path>
                                <path d="M12 19l5 3"></path>
                                <path d="M17 16.5l5 -3"></path>
                                <path d="M12 13.5v-5.5l-5 -3l5 -3l5 3v5.5"></path>
                                <path d="M7 5.03v5.455"></path>
                                <path d="M12 8l5 -3"></path>
                            </svg>
                             APP</a>
                    </li>
                
                <!--end tukifac-->
                    @if(in_array('cuenta', $vc_modules))
                        @if(in_array('account_users_list', $vc_module_levels))
                            <li>
                                <a class="dropdown-item d-flex align-items-center" role="menuitem" href="{{route('tenant.payment.index')}}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="icon icon-tabler icons-tabler-outline icon-tabler-receipt-dollar me-2">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" />
                                        <path
                                            d="M14.8 8a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                                        <path d="M12 6v10" />
                                    </svg>
                                    <span>Mis Pagos</span>
                                </a>
                            </li>
                        @endif
                    @endif
                    {{--<li>
                        <a class="dropdown-item d-flex align-items-center style-switcher-open" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-paint me-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M5 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                                <path d="M19 6h1a2 2 0 0 1 2 2a5 5 0 0 1 -5 5l-5 0v2" />
                                <path
                                    d="M10 15m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" />
                            </svg>
                            Estilos y temas</a>
                    </li>--}}
                    <!--tukifac-->
                    @if(in_array('configuration', $vc_modules))
                    <li
                        class="nav-active">
                        <a class="nav-link" href="{{ url('list-settings') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                                <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                                <path d="M12 12l0 .01" />
                                <path d="M3 13a20 20 0 0 0 18 0" />
                            </svg>
                            Configuraciones Globales</a>
                    </li>
                @endif
                <!--end tukifac-->
                    <li class="divider my-2"></li>

                    <li class="multi-user-content px-4 pb-1">
                        @if(config('configuration.multi_user_enabled'))
                            <tenant-multi-users-change-client></tenant-multi-users-change-client>
                        @endif
                        {{-- <div id="reception-component-container" style="width: 100%;">
                            <reception-component
                                :user-type="'admin'"
                                :establishment-id="{{ auth()->user()->establishment_id }}"
                                :establishments="{{ isset($establishments) ? json_encode($establishments) : json_encode([]) }}"
                            ></reception-component>
                        </div> --}}
                        @php
                            $establishments = App\Models\Tenant\Establishment::select('id', 'description')->get();
                            $current =  auth()->user()->establishment_id;
                        @endphp
                        @if (auth()->user()->type == 'admin')
                           <tenant-hotel-sucursale
                            :establishments='@json($establishments)'
                            :current_establishment={{ $current }}
                           ></tenant-hotel-sucursale>
                        @endif
                    </li>

                    <li class="divider my-2"></li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" role="menuitem" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-door-exit me-2">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M13 12v.01" />
                                <path d="M3 21h18" />
                                <path d="M5 21v-16a2 2 0 0 1 2 -2h7.5m2.5 10.5v7.5" />
                                <path d="M14 7h7m-3 -3l3 3l-3 3" />
                            </svg>
                            Cerrar Sesión
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Sidebar de Soporte -->
@if(isset($hasSupportContact) && $hasSupportContact)
<div id="supportSidebar" class="support-sidebar">
    <div class="support-header">
        Soporte al Cliente
        <button class="close-btn" onclick="toggleSupportSidebar()">&times;</button>
    </div>
    <div class="support-body">
        @if($supportUser && $supportUser->introduction)
        <div class="support-intro richtext mb-3">
            {!! $supportUser->introduction !!}
        </div>
        @endif

        <div class="support-contacts">
            @if($supportUser && $supportUser->whatsapp_number)
            <div class="support-container support-whatsapp">
                <div class="icon-support-container support-left">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-brand-whatsapp">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" />
                            <path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" />
                        </svg>
                    </div>
                </div>
                <div class="support-right">
                    <strong>WhatsApp</strong>
                    <a class="support-link support-link-whatsapp d-flex flex-column" href="https://wa.me/{{ $supportUser->whatsapp_number }}" target="_blank" style="color: #04966a !important">
                        {{ $supportUser->whatsapp_number }}
                    </a>
                </div>
            </div>
            @endif

            @if($supportUser && $supportUser->phone)
            <div class="support-container support-phone">
                <div class="icon-support-container support-left">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-phone">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" />
                        </svg>
                    </div>
                </div>
                <div class="support-right">
                    <strong>Teléfono</strong>
                    <a class="support-link support-link-phone d-flex flex-column" href="tel:{{ $supportUser->phone }}" style="color: #ea580b !important">
                        {{ $supportUser->phone }}
                    </a>
                </div>
            </div>
            @endif

            @if($supportUser && $supportUser->address_contact)
            <div class="support-container support-email">   
                <div class="icon-support-container support-left">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-mail">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                            <path d="M3 7l9 6l9 -6" />
                        </svg>
                    </div>                
                </div>         
                <div class="support-right">
                    <strong>Correo Electrónico</strong>
                    <a class="support-link support-link-email d-flex flex-column" href="mailto:{{ $supportUser->address_contact }}" target="_blank" style="color: #2663eb !important">
                        {{ $supportUser->address_contact }}
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<div id="supportBackdrop" class="support-backdrop" onclick="toggleSupportSidebar()"></div>
@endif

<script>
    function toggleSupportSidebar() {
        const sidebar = document.getElementById('supportSidebar');
        const backdrop = document.getElementById('supportBackdrop');
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
    }
</script>
{{--
<div class="container d-none d-sm-block">
    <div id="switcher-top" class="d-flex justify-content-center switcher-hover">
        <span class="text-white py-0 px-5 text-center"><i class="fas fa-plus fa-fw"></i>Acceso Rápido</span>
    </div>
</div>
<div class="container d-none d-sm-block">
    <div id="switcher-list" class="d-flex justify-content-center switcher-hover">
        <div class="row">
            <div class="px-3"><a class="py-3" href="{{ route('tenant.documents.create') }}"><i
                        class="fas fa-fw fa-file-invoice" aria-hidden="true"></i> Nuevo Comprobante</a></div>
            <div class="px-3"><a class="py-3"
                    href="{{ in_array('pos', $vc_modules) ? route('tenant.pos.index') : '#' }}"><i
                        class="fas fa-fw fa-cash-register" aria-hidden="true"></i> POS</a></div>
            <div style="min-width: 220px;"></div>
            <div class="px-3"><a class="py-3"
                    href="{{ in_array('configuration', $vc_modules) ? route('tenant.companies.create') : '#' }}"><i
                        class="fas fa-fw fa-industry" aria-hidden="true"></i> Empresa</a></div>
            <div class="px-3"><a class="py-3"
                    href="{{ in_array('establishments', $vc_modules) ? route('tenant.establishments.index') : '#' }}"><i
                        class="fas fa-fw fa-warehouse" aria-hidden="true"></i> Establecimientos</a></div>
        </div>
    </div>
</div> --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@7.31.1/dist/sweetalert2.all.min.js"></script>

<script> 
/*tukifac*/

function renderInfoPlan(data){
    if (data.success == true) {
        $('#plan_name').text('Plan: ' + data.plan_name);
        $('#status_plan').text(data.status_plan);
        $('#payment_date').text(data.payment_date);
        const daysInfoElement = $('#days_indicator');
        daysInfoElement.removeClass('text-warning text-danger text-success');
        if (data.days_overdue > 0) {
            daysInfoElement.text(data.days_overdue + ' día(s) de atraso');
            daysInfoElement.addClass('text-danger');
            $('#days_indicator').show();
        } else if (data.days_remaining > 0) {
            daysInfoElement.text(data.days_remaining + ' día(s) restantes');
            daysInfoElement.addClass('text-warning');
            $('#days_indicator').show();
        } else {
            if (data.has_pending_payment) {
                daysInfoElement.text('Fecha de pago hoy');
                daysInfoElement.addClass('text-warning');
                $('#days_indicator').show();
            } else {
                $('#days_indicator').hide();
            }
        }
        if (data.has_pending_payment) {
            $('.pending-payment-section').show();
        } else {
            $('.pending-payment-section').hide();
        }
    }
}

function showInfoPlan(){
    var userId = {!! json_encode(auth()->id()) !!};
    var cacheKey = 'tukifac.info_plan.' + userId;
    var ttl = 3600000;
    try {
        var cached = localStorage.getItem(cacheKey);
        if (cached) {
            var obj = JSON.parse(cached);
            if (obj && obj.data && obj.cachedAt && (Date.now() - obj.cachedAt) < ttl) {
                renderInfoPlan(obj.data);
                return;
            }
        }
    } catch (e) {}
    $.ajax({
        url: "{{ url('cuenta/info_plan') }}",
        method: 'get',
        dataType: 'JSON',
        success: function (data) {
            try {
                localStorage.setItem(cacheKey, JSON.stringify({data: data, cachedAt: Date.now()}));
            } catch (e) {}
            renderInfoPlan(data);
        },
        error: function (error_data) {
            console.log('Error:', error_data);
        }
    });
}

$(document).ready(function() {
    showInfoPlan();
    try {
        var userId = {!! json_encode(auth()->id()) !!};
        var cacheKey = 'tukifac.info_plan.' + userId;
        var forms = document.querySelectorAll('form#logout-form');
        forms.forEach(function(f){
            f.addEventListener('submit', function(){
                try { localStorage.removeItem(cacheKey); } catch(e) {}
            });
        });
    } catch(e) {}
});
/*tukifac*/
</script>
@endpush
