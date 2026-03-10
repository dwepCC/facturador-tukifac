@extends('tenant.layouts.app')

@push('styles')
<style>
    .tukifac-top-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    @media (min-width: 992px) {
        .tukifac-top-grid {
            grid-template-columns: 1fr 1fr;
            align-items: stretch;
        }
    }

    .tukifac-dashboard {
        --tukifac-top-height-mobile: 160px;
        --tukifac-top-height-desktop: 250px;
    }

    @media (max-width: 991.98px) {
        .tukifac-dashboard {
            padding: 10px;
        }
    }

    .tukifac-dashboard .tukifac-hero-banner {
        height: var(--tukifac-top-height-mobile);
        min-height: var(--tukifac-top-height-mobile);
        max-height: var(--tukifac-top-height-mobile);
    }

    @media (min-width: 992px) {
        .tukifac-dashboard .tukifac-hero-banner {
            height: var(--tukifac-top-height-desktop);
            min-height: var(--tukifac-top-height-desktop);
            max-height: var(--tukifac-top-height-desktop);
        }
    }

    .tukifac-ads-wrap {
        width: 100%;
        height: auto;
        min-height: 0;
    }

    @media (min-width: 992px) {
        .tukifac-ads-wrap {
            height: var(--tukifac-top-height-desktop);
            min-height: var(--tukifac-top-height-desktop);
            max-height: var(--tukifac-top-height-desktop);
        }
    }

    .tukifac-ads-card {
        width: 100%;
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        padding: 14px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        user-select: none;
    }

    .tukifac-ads-mobile-only .tukifac-ads-card {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #0f172a 0%, #111827 45%, #0b1220 100%);
        color: #ffffff;
    }

    .tukifac-ads-mobile-only .tukifac-ads-card::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(80% 120% at 100% 50%, rgba(16, 185, 129, 0.22) 0%, rgba(16, 185, 129, 0.00) 55%);
    }

    .tukifac-ads-mobile-only .tukifac-ads-card > * {
        position: relative;
        z-index: 1;
    }

    .tukifac-ads-mobile-only .tukifac-ads-card .tukifac-ads-card-title {
        color: #ffffff;
    }

    .tukifac-ads-mobile-only .tukifac-ads-card .tukifac-ads-card-subtitle {
        color: rgba(255, 255, 255, 0.78);
    }

    .tukifac-ads-mobile-only .tukifac-ads-card .tukifac-ads-card-badge {
        background: rgba(16, 185, 129, 0.18);
        color: #d1fae5;
    }

    .tukifac-ads-mobile-only .tukifac-ads-card .tukifac-ads-card-badge::before {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.55);
    }

    .tukifac-ads-modal-close {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 20;
        width: 40px;
        height: 40px;
        border-radius: 999px;
        background-color: rgba(17, 24, 39, 0.55);
        backdrop-filter: blur(6px);
        opacity: 1;
        filter: invert(1);
    }

    .tukifac-ads-card-title {
        font-weight: 700;
        color: #111827;
        line-height: 1.1;
        margin: 0;
    }

    .tukifac-ads-card-subtitle {
        margin: 2px 0 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.2;
    }

    .tukifac-ads-card-badge {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.2px;
        text-transform: uppercase;
        background: rgba(16, 185, 129, 0.12);
        color: #047857;
        padding: 4px 8px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .tukifac-ads-card-badge::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6);
        animation: tukifacPulse 1.4s infinite;
    }

    .tukifac-ads-carousel {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.10);
        background: #ffffff;
        height: 100%;
    }

    .tukifac-ads-media {
        width: 100%;
        background: #ffffff;
        position: relative;
    }

    .tukifac-ads-media img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    @media (max-width: 991.98px) {
        .tukifac-dashboard {
            --tukifac-top-height-mobile: 150px;
        }

        .tukifac-ads-carousel {
            height: auto;
        }

        #tukifacAdsCarouselModal .tukifac-ads-media {
            aspect-ratio: 1 / 1;
            height: auto;
            max-height: 70vh;
        }
    }

    .tukifac-ads-desktop-only .carousel-inner,
    .tukifac-ads-desktop-only .carousel-item,
    .tukifac-ads-desktop-only .tukifac-ads-slide-link,
    .tukifac-ads-desktop-only .tukifac-ads-media {
        height: 100%;
    }

    .tukifac-ads-carousel .carousel-control-prev,
    .tukifac-ads-carousel .carousel-control-next {
        z-index: 10;
        opacity: 1;
        pointer-events: auto;
    }

    .tukifac-ads-carousel .carousel-control-prev-icon,
    .tukifac-ads-carousel .carousel-control-next-icon {
        width: 2.4rem;
        height: 2.4rem;
        background-size: 1.1rem 1.1rem;
        background-color: rgba(17, 24, 39, 0.55);
        border-radius: 999px;
        background-position: center;
        backdrop-filter: blur(6px);
    }

    .tukifac-ads-carousel .carousel-indicators {
        z-index: 10;
    }

    .tukifac-ads-slide-link {
        display: block;
        position: relative;
        color: inherit;
        text-decoration: none;
    }

    .tukifac-ads-slide-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .tukifac-ads-pulse {
        width: 84px;
        height: 84px;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.92);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: tukifacPulse 1.4s infinite;
    }

    .tukifac-ads-pulse svg {
        width: 34px;
        height: 34px;
        color: #ffffff;
    }

    @keyframes tukifacPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.55);
        }
        70% {
            box-shadow: 0 0 0 18px rgba(16, 185, 129, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    .tukifac-ads-desktop-only {
        display: none;
    }

    .tukifac-ads-mobile-only {
        display: block;
    }

    @media (min-width: 992px) {
        .tukifac-ads-desktop-only {
            display: block;
            height: 100%;
        }
        .tukifac-ads-mobile-only {
            display: none;
        }
    }
</style>
@endpush

@section('content')

@php
    $waLink = 'https://wa.link/4d7rjm';
@endphp

<div class="tukifac-dashboard">
    <!-- OPTIMIZACIÓN LCP: Hero banner con carga optimizada -->
    <div class="tukifac-top-grid">
        <div class="tukifac-hero-banner" style="background: #f4f4f4 url('{{ asset('storage/EMPRENDEDOR-TK1.webp') }}'); background-size: cover; background-repeat: no-repeat; background-position: center;">
            <div class="tukifac-hero-content">
                <span class="tukifac-hero-title">Aprende a utilizar Tukifac <br> con <span class="tukifac-hero-highlight">nuestros tutoriales</span></span>
            </div>
            <a class="tukifac-play-button" href="{{route('tenant.dashboard.soporte')}}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </a>
        </div>

        <div class="tukifac-ads-wrap">
            <div class="tukifac-ads-desktop-only">
                <div id="tukifacAdsCarousel" class="carousel slide tukifac-ads-carousel" data-bs-ride="carousel" data-bs-interval="4500">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#tukifacAdsCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Publicidad 1"></button>
                        <button type="button" data-bs-target="#tukifacAdsCarousel" data-bs-slide-to="1" aria-label="Publicidad 2"></button>
                        <button type="button" data-bs-target="#tukifacAdsCarousel" data-bs-slide-to="2" aria-label="Publicidad 3"></button>
                    </div>
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <a class="tukifac-ads-slide-link" href="{{ $waLink }}" target="_blank" rel="noopener">
                                <div class="tukifac-ads-media">
                                    <img src="{{ asset('storage/slider1.png') }}" alt="Publicidad 1" loading="lazy" decoding="async">
                                    <div class="tukifac-ads-slide-overlay">
                                        <div class="tukifac-ads-pulse" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"></path>
                                                <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="carousel-item">
                            <a class="tukifac-ads-slide-link" href="{{ $waLink }}" target="_blank" rel="noopener">
                                <div class="tukifac-ads-media">
                                    <img src="{{ asset('storage/slider2.png') }}" alt="Publicidad 2" loading="lazy" decoding="async">
                                    <div class="tukifac-ads-slide-overlay">
                                        <div class="tukifac-ads-pulse" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"></path>
                                                <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="carousel-item">
                            <a class="tukifac-ads-slide-link" href="{{ $waLink }}" target="_blank" rel="noopener">
                                <div class="tukifac-ads-media">
                                    <img src="{{ asset('storage/slider3.png') }}" alt="Publicidad 3" loading="lazy" decoding="async">
                                    <div class="tukifac-ads-slide-overlay">
                                        <div class="tukifac-ads-pulse" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"></path>
                                                <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#tukifacAdsCarousel" data-bs-slide="prev" aria-label="Anterior">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#tukifacAdsCarousel" data-bs-slide="next" aria-label="Siguiente">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            </div>

            <div class="tukifac-ads-mobile-only">
                <div class="tukifac-ads-card" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#tukifacAdsModal" aria-label="Abrir publicidad">
                    <div>
                        <p class="tukifac-ads-card-title">¿Quieres ver promociones?</p>
                        <p class="tukifac-ads-card-subtitle">Toca para verlas</p>
                    </div>
                    <span class="tukifac-ads-card-badge">Ver</span>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tukifacAdsModal" tabindex="-1" aria-labelledby="tukifacAdsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-body p-0 position-relative">
                    <button type="button" class="btn-close tukifac-ads-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    <div id="tukifacAdsCarouselModal" class="carousel slide tukifac-ads-carousel" data-bs-ride="carousel" data-bs-interval="4500">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#tukifacAdsCarouselModal" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Publicidad 1"></button>
                            <button type="button" data-bs-target="#tukifacAdsCarouselModal" data-bs-slide-to="1" aria-label="Publicidad 2"></button>
                            <button type="button" data-bs-target="#tukifacAdsCarouselModal" data-bs-slide-to="2" aria-label="Publicidad 3"></button>
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <a class="tukifac-ads-slide-link" href="{{ $waLink }}" target="_blank" rel="noopener">
                                    <div class="tukifac-ads-media">
                                        <img src="{{ asset('storage/slidermovil1.png') }}" alt="Publicidad 1" loading="lazy" decoding="async">
                                        <div class="tukifac-ads-slide-overlay">
                                            <div class="tukifac-ads-pulse" aria-hidden="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"></path>
                                                    <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="carousel-item">
                                <a class="tukifac-ads-slide-link" href="{{ $waLink }}" target="_blank" rel="noopener">
                                    <div class="tukifac-ads-media">
                                        <img src="{{ asset('storage/slidermovil2.png') }}" alt="Publicidad 2" loading="lazy" decoding="async">
                                        <div class="tukifac-ads-slide-overlay">
                                            <div class="tukifac-ads-pulse" aria-hidden="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"></path>
                                                    <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="carousel-item">
                                <a class="tukifac-ads-slide-link" href="{{ $waLink }}" target="_blank" rel="noopener">
                                    <div class="tukifac-ads-media">
                                        <img src="{{ asset('storage/slidermovil3.png') }}" alt="Publicidad 3" loading="lazy" decoding="async">
                                        <div class="tukifac-ads-slide-overlay">
                                            <div class="tukifac-ads-pulse" aria-hidden="true">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9z"></path>
                                                    <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#tukifacAdsCarouselModal" data-bs-slide="prev" aria-label="Anterior">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#tukifacAdsCarouselModal" data-bs-slide="next" aria-label="Siguiente">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="tukifac-section-headers">
        <span class="tukifac-section-title">Selecciona una de las opciones</span>
        <span class="tukifac-section-title">Otras herramientas</span>
    </div>
    
    <div class="tukifac-tools-grid">
        <span class="tukifac-mobile-section-title">Selecciona una de las opciones</span>
        
        <div class="tukifac-primary-tools">
            <a class="tukifac-tool-card tukifac-tool-sales" href="/pos">
                <div class="tukifac-tool-content">
                    <div class="tukifac-tool-info">
                        <span class="tukifac-tool-badge">Herramienta</span> 
                        <span class="tukifac-tool-title-primary">Realiza una</span>
                        <span class="tukifac-tool-title-secondary">Venta rápida</span>
                    </div>
                    <div class="tukifac-tool-image">
                        <img alt="Venta rápida" loading="lazy" width="150" height="150" decoding="async" src="{{ asset('storage/venta-rapida-tukifac-img.webp') }}">
                    </div>
                    <div class="tukifac-tool-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 13 13" width="10" height="10">
                            <path d="M469.333 0H234.665c-11.781 0-21.332 9.551-21.332 21.332v.003c0 11.781 9.551 21.332 21.332 21.332h204.503L228.799 253.036a21.328 21.328 0 0 0 0 30.165 21.325 21.325 0 0 0 30.165 0L469.333 72.832v204.503c0 11.781 9.551 21.332 21.332 21.332h.003c11.781 0 21.332-9.551 21.332-21.332V42.667C512 19.136 492.864 0 469.333 0Z" transform="matrix(.04206 0 0 .04206 -8.973 0)"></path>
                        </svg>
                    </div>
                </div>
            </a>
            
            <a class="tukifac-tool-card tukifac-tool-products" href="/items">
                <div class="tukifac-tool-content">
                    <div class="tukifac-tool-image-left">
                        <img alt="Gestión de productos" loading="lazy" width="150" height="150" decoding="async" src="{{ asset('storage/stock-tukifac-img.webp') }}">
                    </div>
                    <div class="tukifac-tool-info-right">
                        <span class="tukifac-tool-badge">Herramienta</span>
                        <span class="tukifac-tool-title-primary">Ver o agregar</span>
                        <span class="tukifac-tool-title-secondary">Productos</span>
                    </div>
                </div>
                <div class="tukifac-tool-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 13 13" width="10" height="10">
                        <path d="M469.333 0H234.665c-11.781 0-21.332 9.551-21.332 21.332v.003c0 11.781 9.551 21.332 21.332 21.332h204.503L228.799 253.036a21.328 21.328 0 0 0 0 30.165 21.325 21.325 0 0 0 30.165 0L469.333 72.832v204.503c0 11.781 9.551 21.332 21.332 21.332h.003c11.781 0 21.332-9.551 21.332-21.332V42.667C512 19.136 492.864 0 469.333 0Z" transform="matrix(.04206 0 0 .04206 -8.973 0)"></path>
                    </svg>
                </div>
            </a>
        </div>
        
        <span class="tukifac-mobile-section-title">Otras herramientas</span>
        
        <div class="tukifac-secondary-tools">
            <a class="tukifac-tool-card tukifac-tool-documents" href="/documents">
                <div class="tukifac-tool-content-column">
                    <div class="tukifac-tool-info-center">
                        <span class="tukifac-tool-badge">Herramienta</span>
                        <span class="tukifac-tool-title-primary">Buscar</span>
                        <span class="tukifac-tool-title-secondary">Documentos</span>
                    </div>
                    <div class="tukifac-tool-image-bottom">
                        <img alt="Búsqueda de documentos" loading="lazy" width="150" height="130" decoding="async" src="{{ asset('storage/busqueda-doc-tukifac-img.webp') }}">
                    </div>
                </div>
                <div class="tukifac-tool-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 13 13" width="10" height="10">
                        <path d="M469.333 0H234.665c-11.781 0-21.332 9.551-21.332 21.332v.003c0 11.781 9.551 21.332 21.332 21.332h204.503L228.799 253.036a21.328 21.328 0 0 0 0 30.165 21.325 21.325 0 0 0 30.165 0L469.333 72.832v204.503c0 11.781 9.551 21.332 21.332 21.332h.003c11.781 0 21.332-9.551 21.332-21.332V42.667C512 19.136 492.864 0 469.333 0Z" transform="matrix(.04206 0 0 .04206 -8.973 0)"></path>
                    </svg>
                </div>
            </a>
            
            <a class="tukifac-tool-card tukifac-tool-reports" href="/list-reports">
                <div class="tukifac-tool-content-column">
                    <div class="tukifac-tool-info-center">
                        <span class="tukifac-tool-badge">Herramienta</span>
                        <span class="tukifac-tool-title-primary">Consultar</span>
                        <span class="tukifac-tool-title-secondary">Reportes</span>
                    </div>
                    <div class="tukifac-tool-image-bottom">
                        <img alt="Reportes y documentos" loading="lazy" width="150" height="130" decoding="async" src="{{ asset('storage/docs-tukifac-img.webp') }}">
                    </div>
                </div>
                <div class="tukifac-tool-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 13 13" width="10" height="10">
                        <path d="M469.333 0H234.665c-11.781 0-21.332 9.551-21.332 21.332v.003c0 11.781 9.551 21.332 21.332 21.332h204.503L228.799 253.036a21.328 21.328 0 0 0 0 30.165 21.325 21.325 0 0 0 30.165 0L469.333 72.832v204.503c0 11.781 9.551 21.332 21.332 21.332h.003c11.781 0 21.332-9.551 21.332-21.332V42.667C512 19.136 492.864 0 469.333 0Z" transform="matrix(.04206 0 0 .04206 -8.973 0)"></path>
                    </svg>
                </div>
            </a>
            
            <a class="tukifac-tool-card tukifac-tool-cash" href="/cash">
                <div class="tukifac-tool-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 13 13" width="10" height="10">
                        <path d="M469.333 0H234.665c-11.781 0-21.332 9.551-21.332 21.332v.003c0 11.781 9.551 21.332 21.332 21.332h204.503L228.799 253.036a21.328 21.328 0 0 0 0 30.165 21.325 21.325 0 0 0 30.165 0L469.333 72.832v204.503c0 11.781 9.551 21.332 21.332 21.332h.003c11.781 0 21.332-9.551 21.332-21.332V42.667C512 19.136 492.864 0 469.333 0Z" transform="matrix(.04206 0 0 .04206 -8.973 0)"></path>
                    </svg>
                </div>
                <div class="tukifac-tool-content-full">
                    <div class="tukifac-tool-info-center">
                        <span class="tukifac-tool-badge">Herramienta</span>
                        <span class="tukifac-tool-title-primary">Apertura o cierre</span>
                        <span class="tukifac-tool-title-secondary">de cajas</span>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
