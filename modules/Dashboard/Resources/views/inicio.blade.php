@extends('tenant.layouts.app')

@section('content')

<div class="tukifac-dashboard">
    <!-- OPTIMIZACIÓN LCP: Hero banner con carga optimizada -->
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
