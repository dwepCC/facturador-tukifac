@extends('tenant.layouts.app')

@section('content')
    <div class="tutorial-container">
        <div class="section-header">
            <div class="section-header-content">
                <h2><i class="fas fa-graduation-cap"></i> Centro de Aprendizaje</h2>
                <p>Domina nuestro sistema de facturación electrónica con tutoriales paso a paso</p>
            </div>
        </div>
        
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar tutoriales...">
            </div>
        </div>
                
        <div class="tutorial-columns" id="tutorial-columns">
            <!-- Las categorías se cargarán dinámicamente aquí -->
        </div>
        
        <!-- Modal para reproducir videos -->
        <div id="videoModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="video-container">
                    <iframe id="videoPlayer" width="100%" height="400" src="" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="video-info-modal">
                    <h3 id="videoTitle"></h3>
                    <p id="videoDescription"></p> 
                </div>
            </div>
        </div>
    </div> 
    
@push('scripts')
    <script>
        console.log("tutoriales");
        // Datos de ejemplo para las categorías y videos con información manual
        const tutorialCategories = [
            {
                id: 1,
                title: "Primeros pasos",
                icon: "fas fa-play-circle",
                videos: [
                    { 
                        id: 1, 
                        title: "Ubicación de herramientas", /* listo */
                        url: "https://www.youtube.com/watch?v=F8a7q0irX0A",
                        thumbnail: "https://img.youtube.com/vi/F8a7q0irX0A/mqdefault.jpg",
                        duration: "1:10",
                        views: 1250,
                        publishedDate: "2025-10-14"
                    },
                    { 
                        id: 2, 
                        title: "Cómo actualizo los datos de mi comprobante", /* listo */
                        url: "https://www.youtube.com/watch?v=Re9BssDPli0",
                        thumbnail: "https://img.youtube.com/vi/Re9BssDPli0/mqdefault.jpg",
                        duration: "1:33",
                        views: 890,
                        publishedDate: "2025-10-14"
                    },
                    { 
                        id: 3, 
                        title: "Cómo usar el dashboard", /* listo */
                        url: "https://www.youtube.com/watch?v=oxT_aAeQTGk",
                        thumbnail: "https://img.youtube.com/vi/oxT_aAeQTGk/mqdefault.jpg",
                        duration: "1:43",
                        views: 2100,
                        publishedDate: "2025-10-14"
                    }
                ]
            },
            {
                id: 2,
                title: "Pre-venta",
                icon: "fas fa-file-signature",
                videos: [
                    { 
                        id: 4, 
                        title: "Cotización", /* listo */
                        url: "https://www.youtube.com/watch?v=L0wfIySQWCU",
                        thumbnail: "https://img.youtube.com/vi/L0wfIySQWCU/mqdefault.jpg",
                        duration: "1:48",
                        views: 1560,
                        publishedDate: "2025-10-14"
                    },
                    { 
                        id: 5, 
                        title: "Contrato",  /* listo */
                        url: "https://www.youtube.com/watch?v=Lohw1VB6078",
                        thumbnail: "https://img.youtube.com/vi/Lohw1VB6078/mqdefault.jpg",
                        duration: "01:20",
                        views: 980,
                        publishedDate: "2025-10-14"
                    }
                ]
            },
            {
                id: 3,
                title: "Ventas",
                icon: "fas fa-shopping-cart",
                videos: [
                    { 
                        id: 6, 
                        title: "Nuevo comprobante", /* listo */
                        url: "https://www.youtube.com/watch?v=YZdyeWMdFmk",
                        thumbnail: "https://img.youtube.com/vi/YZdyeWMdFmk/mqdefault.jpg",
                        duration: "02:04",
                        views: 3200,
                        publishedDate: "2025-10-14"
                    },
                    { 
                        id: 7, 
                        title: "Consulta de comprobante",  /* listo */
                        url: "https://www.youtube.com/watch?v=i1RAHAOYd28",
                        thumbnail: "https://img.youtube.com/vi/i1RAHAOYd28/mqdefault.jpg",
                        duration: "01:36",
                        views: 1850,
                        publishedDate: "2025-10-14"
                    },
                    { 
                        id: 8, 
                        title: "Nota de venta", /* listo */
                        url: "https://www.youtube.com/watch?v=fH7_RlsAOmg",
                        thumbnail: "https://img.youtube.com/vi/fH7_RlsAOmg/mqdefault.jpg",
                        duration: "1:48",
                        views: 1430,
                        publishedDate: "2025-10-14"
                    },
                    { 
                        id: 9, 
                        title: "Punto de venta",  /* listo */
                        url: "https://www.youtube.com/watch?v=PyWk7ZAinpg",
                        thumbnail: "https://img.youtube.com/vi/PyWk7ZAinpg/mqdefault.jpg",
                        duration: "01:26",
                        views: 2100,
                        publishedDate: "2025-10-24"
                    },
                    { 
                        id: 10, 
                        title: "Venta rápida", /* listo */
                        url: "https://www.youtube.com/watch?v=Hv7hgHGgO8s",
                        thumbnail: "https://img.youtube.com/vi/Hv7hgHGgO8s/mqdefault.jpg",
                        duration: "2:02",
                        views: 1680,
                        publishedDate: "2025-10-14"
                    }
                ]
            },
            {
                id: 4,
                title: "Productos/Servicios",
                icon: "fas fa-boxes",
                videos: [
                    { 
                        id: 11, 
                        title: "Lista de productos", /* tukifac */
                        url: "https://www.youtube.com/watch?v=i6Y6sEnguYQ",
                        thumbnail: "https://img.youtube.com/vi/i6Y6sEnguYQ/mqdefault.jpg",
                        duration: "02:22",
                        views: 1920,
                        publishedDate: "2025-10-23"
                    },
                    { 
                        id: 12, 
                        title: "Creación básica", /* tukifac */
                        url: "https://www.youtube.com/watch?v=oHTDpl0ElxU",
                        thumbnail: "https://img.youtube.com/vi/oHTDpl0ElxU/mqdefault.jpg",
                        duration: "01:47",
                        views: 1450,
                        publishedDate: "2025-10-28"
                    },
                    { 
                        id: 13, 
                        title: "Creación avanzada", /* tukifac */
                        url: "https://www.youtube.com/watch?v=9YR60NJ--DM",
                        thumbnail: "https://img.youtube.com/vi/9YR60NJ--DM/mqdefault.jpg",
                        duration: "02:48",
                        views: 1230,
                        publishedDate: "2025-10-23"
                    },
                    { 
                        id: 14, 
                        title: "Importación masiva",  /* tukifac */
                        url: "https://www.youtube.com/watch?v=HRy0_iJyi7M",
                        thumbnail: "https://img.youtube.com/vi/HRy0_iJyi7M/mqdefault.jpg",
                        duration: "02:55",
                        views: 2100,
                        publishedDate: "2025-10-28"
                    },
                    { 
                        id: 15, 
                        title: "Importación de productos con imagen masiva", /* tukifac */
                        url: "https://www.youtube.com/watch?v=KLscFDJs0Dc",
                        thumbnail: "https://img.youtube.com/vi/KLscFDJs0Dc/mqdefault.jpg",
                        duration: "01:55",
                        views: 1870,
                        publishedDate: "2025-10-24"
                    },
                    { 
                        id: 16, 
                        title: "Importar lista de precios", 
                        url: "https://www.youtube.com/watch?v=iDoKxWUobxo",
                        thumbnail: "https://img.youtube.com/vi/iDoKxWUobxo/mqdefault.jpg",
                        duration: "01:21",
                        views: 1650,
                        publishedDate: "2025-11-14"
                    },
                    { 
                        id: 17, 
                        title: "Actualización de precios de productos", /* LISTO */
                        url: "https://www.youtube.com/watch?v=5u3opD-orPY",
                        thumbnail: "https://img.youtube.com/vi/5u3opD-orPY/mqdefault.jpg",
                        duration: "01:25",
                        views: 1320,
                        publishedDate: "2025-10-24"
                    },
                    { 
                        id: 18, 
                        title: "Armar pack y promociones", 
                        url: "https://www.youtube.com/watch?v=fb84vxCVk4s",
                        thumbnail: "https://img.youtube.com/vi/fb84vxCVk4s/mqdefault.jpg",
                        duration: "01:52",
                        views: 1540,
                        publishedDate: "2025-10-28"
                    },
                    { 
                        id: 19, 
                        title: "Exportar masivamente",  /* tukifac */
                        url: "https://www.youtube.com/watch?v=cykngl6J8UE",
                        thumbnail: "https://img.youtube.com/vi/cykngl6J8UE/mqdefault.jpg",
                        duration: "01:15",
                        views: 1180,
                        publishedDate: "2025-10-24"
                    },
                    { 
                        id: 20, 
                        title: "Creación de Servicios", /* tukifac */
                        url: "https://www.youtube.com/watch?v=cdGvI1SMuhk",
                        thumbnail: "https://img.youtube.com/vi/cdGvI1SMuhk/mqdefault.jpg",
                        duration: "01:13",
                        views: 980,
                        publishedDate: "2025-10-28"
                    },
                    { 
                        id: 21, 
                        title: "Categorías/Marcas", /* listo */
                        url: "https://www.youtube.com/watch?v=JkEDVuLABMQ",
                        thumbnail: "https://img.youtube.com/vi/JkEDVuLABMQ/mqdefault.jpg",
                        duration: "1:23",
                        views: 1120,
                        publishedDate: "2025-10-28"
                    }
                ]
            },
            {
                id: 5,
                title: "Compra",
                icon: "fas fa-shopping-bag",
                videos: [
                    { 
                        id: 22, 
                        title: "Nueva compra", 
                        url: "https://www.youtube.com/watch?v=aFMs4CRyxV8",
                        thumbnail: "https://img.youtube.com/vi/aFMs4CRyxV8/mqdefault.jpg",
                        duration: "02:12",
                        views: 1780,
                        publishedDate: "2025-11-15"
                    },
                    { 
                        id: 23, 
                        title: "Lista de compra", 
                        url: "https://www.youtube.com/watch?v=27-GSjVBkbE",
                        thumbnail: "https://img.youtube.com/vi/27-GSjVBkbE/mqdefault.jpg",
                        duration: "01:34",
                        views: 1420,
                        publishedDate: "2025-10-31"
                    },
                    { 
                        id: 24, 
                        title: "Orden de compra", 
                        url: "https://www.youtube.com/watch?v=DhIRZQb0_AI",
                        thumbnail: "https://img.youtube.com/vi/DhIRZQb0_AI/mqdefault.jpg",
                        duration: "01:52",
                        views: 1650,
                        publishedDate: "2025-10-31"
                    },
                    { 
                        id: 25, 
                        title: "Gasto diverso", 
                        url: "https://www.youtube.com/watch?v=CiHOQsL0-io",
                        thumbnail: "https://img.youtube.com/vi/CiHOQsL0-io/mqdefault.jpg",
                        duration: "02:02",
                        views: 1180,
                        publishedDate: "2025-10-31"
                    },
                    { 
                        id: 26, 
                        title: "Proveedores: Creación individual", 
                        url: "https://www.youtube.com/watch?v=bfLKv2Zso24",
                        thumbnail: "https://img.youtube.com/vi/bfLKv2Zso24/mqdefault.jpg",
                        duration: "01:42",
                        views: 1320,
                        publishedDate: "2025-10-31"
                    },
                    { 
                        id: 27, 
                        title: "Proveedores: Importación masiva", 
                        url: "https://www.youtube.com/watch?v=B2gMFJSJSQY",
                        thumbnail: "https://img.youtube.com/vi/B2gMFJSJSQY/mqdefault.jpg",
                        duration: "01:24",
                        views: 1520,
                        publishedDate: "2025-10-31"
                    },
                    { 
                        id: 28, 
                        title: "Solicitar cotización", 
                        url: "https://www.youtube.com/watch?v=7vLt1uHOAcM",
                        thumbnail: "https://img.youtube.com/vi/7vLt1uHOAcM/mqdefault.jpg",
                        duration: "01:18",
                        views: 980,
                        publishedDate: "2025-10-31"
                    },
                    { 
                        id: 29, 
                        title: "Activos fijos", 
                        url: "https://www.youtube.com/watch?v=m32K9PggEds",
                        thumbnail: "https://img.youtube.com/vi/m32K9PggEds/mqdefault.jpg",
                        duration: "01:44",
                        views: 1420,
                        publishedDate: "2025-10-04"
                    },
                    { 
                        id: 30, 
                        title: "Compra de activo", 
                        url: "https://www.youtube.com/watch?v=Sx33Bb6COPo",
                        thumbnail: "https://img.youtube.com/vi/Sx33Bb6COPo/mqdefault.jpg",
                        duration: "01:58",
                        views: 1250,
                        publishedDate: "2025-10-04"
                    }
                ]
            },
            {
                id: 6,
                title: "Control de inventario",
                icon: "fas fa-warehouse",
                videos: [
                    { 
                        id: 31, 
                        title: "Movimientos", 
                        url: "https://www.youtube.com/watch?v=V2QhSzD5mOE",
                        thumbnail: "https://img.youtube.com/vi/V2QhSzD5mOE/mqdefault.jpg",
                        duration: "00:56",
                        views: 1580,
                        publishedDate: "2025-11-16"
                    },
                    { 
                        id: 32, 
                        title: "Traslados", 
                        url: "https://www.youtube.com/watch?v=zRC0R-2XlYM",
                        thumbnail: "https://img.youtube.com/vi/zRC0R-2XlYM/mqdefault.jpg",
                        duration: "00:51",
                        views: 1580,
                        publishedDate: "2025-11-15"
                    },
                    { 
                        id: 33, 
                        title: "Reporte Kardex", 
                        url: "https://www.youtube.com/watch?v=6JcP-xx0GrI",
                        thumbnail: "https://img.youtube.com/vi/6JcP-xx0GrI/mqdefault.jpg",
                        duration: "01:09",
                        views: 1320,
                        publishedDate: "2025-10-04"
                    },
                    { 
                        id: 34, 
                        title: "Devolución a proveedor", 
                        url: "https://www.youtube.com/watch?v=TyzlQLtwqys",
                        thumbnail: "https://img.youtube.com/vi/TyzlQLtwqys/mqdefault.jpg",
                        duration: "01:41",
                        views: 1120,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 35, 
                        title: "Reporte de inventario", 
                        url: "https://www.youtube.com/watch?v=NOMKuLEAWWA",
                        thumbnail: "https://img.youtube.com/vi/NOMKuLEAWWA/mqdefault.jpg",
                        duration: "01:53",
                        views: 1450,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 36, 
                        title: "Reporte de Kardex valorizado", 
                        url: "https://www.youtube.com/watch?v=syq6sTZtYpA",
                        thumbnail: "https://img.youtube.com/vi/syq6sTZtYpA/mqdefault.jpg",
                        duration: "01:39",
                        views: 1680,
                        publishedDate: "2025-11-06"
                    }
                ]
            },
            {
                id: 7,
                title: "Clientes",
                icon: "fas fa-users",
                videos: [
                    { 
                        id: 37, 
                        title: "Creación individual", 
                        url: "https://www.youtube.com/watch?v=pUztdgxX870",
                        thumbnail: "https://img.youtube.com/vi/pUztdgxX870/mqdefault.jpg",
                        duration: "01:22",
                        views: 1920,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 38, 
                        title: "Importación masiva", 
                        url: "https://www.youtube.com/watch?v=mme5tyPm2qg",
                        thumbnail: "https://img.youtube.com/vi/mme5tyPm2qg/mqdefault.jpg",
                        duration: "01:22",
                        views: 1580,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 39, 
                        title: "Exportar clientes", 
                        url: "https://www.youtube.com/watch?v=oOJQRtEEKN8",
                        thumbnail: "https://img.youtube.com/vi/oOJQRtEEKN8/mqdefault.jpg",
                        duration: "01:20",
                        views: 1230,
                        publishedDate: "2025-11-17"
                    },
                    { 
                        id: 40, 
                        title: "Tipos de clientes", 
                        url: "https://www.youtube.com/watch?v=7CZnaXF9_IA",
                        thumbnail: "https://img.youtube.com/vi/7CZnaXF9_IA/mqdefault.jpg",
                        duration: "00:55",
                        views: 1450,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 41, 
                        title: "Cómo elegir un cliente por defecto", 
                        url: "https://www.youtube.com/watch?v=w-mUxJBxrgk",
                        thumbnail: "https://img.youtube.com/vi/w-mUxJBxrgk/mqdefault.jpg",
                        duration: "0:46",
                        views: 890,
                        publishedDate: "2025-11-14"
                    }
                ]
            },
            {
                id: 8,
                title: "Guías Remisión/Transportista",
                icon: "fas fa-truck",
                videos: [
                    { 
                        id: 42, 
                        title: "Generar guía de remisión", 
                        url: "https://www.youtube.com/watch?v=OVAAUQYN7tg",
                        thumbnail: "https://img.youtube.com/vi/OVAAUQYN7tg/mqdefault.jpg",
                        duration: "03:38",
                        views: 1780,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 43, 
                        title: "Generar guía de transportista", 
                        url: "https://www.youtube.com/watch?v=5ZujrmhU2k4",
                        thumbnail: "https://img.youtube.com/vi/5ZujrmhU2k4/mqdefault.jpg",
                        duration: "01:58",
                        views: 1520,
                        publishedDate: "2025-11-22"
                    },
                    { 
                        id: 44, 
                        title: "Creación de transportistas", 
                        url: "https://www.youtube.com/watch?v=jm3mCHSAS0Q",
                        thumbnail: "https://img.youtube.com/vi/jm3mCHSAS0Q/mqdefault.jpg",
                        duration: "01:14",
                        views: 1320,
                        publishedDate: "2025-11-19"
                    },
                    { 
                        id: 45, 
                        title: "Creación de conductores", 
                        url: "https://www.youtube.com/watch?v=6hhcXi7R-E4",
                        thumbnail: "https://img.youtube.com/vi/6hhcXi7R-E4/mqdefault.jpg",
                        duration: "6:15",
                        views: 1120,
                        publishedDate: "2025-11-22"
                    },
                    { 
                        id: 46, 
                        title: "Creación de vehículos", 
                        url: "https://www.youtube.com/watch?v=JxssD09xyNU",
                        thumbnail: "https://img.youtube.com/vi/JxssD09xyNU/mqdefault.jpg",
                        duration: "01:14",
                        views: 1250,
                        publishedDate: "2025-11-14"
                    }
                ]
            },
            {
                id: 9,
                title: "Reportes",
                icon: "fas fa-chart-bar",
                videos: [
                    { 
                        id: 47, 
                        title: "Reporte general", 
                        url: "https://www.youtube.com/watch?v=wOl8fA2d1kY",
                        thumbnail: "https://img.youtube.com/vi/wOl8fA2d1kY/mqdefault.jpg",
                        duration: "02:50",
                        views: 1920,
                        publishedDate: "2025-11-11"
                    },
                    { 
                        id: 48, 
                        title: "Reporte ventas", 
                        url: "https://www.youtube.com/watch?v=U46LbbicAb0",
                        thumbnail: "https://img.youtube.com/vi/U46LbbicAb0/mqdefault.jpg",
                        duration: "02:00",
                        views: 1680,
                        publishedDate: "2025-11-14"
                    },
                    { 
                        id: 49, 
                        title: "Reporte compras", 
                        url: "https://www.youtube.com/watch?v=6Qes7-K886s",
                        thumbnail: "https://img.youtube.com/vi/6Qes7-K886s/mqdefault.jpg",
                        duration: "02:57",
                        views: 1420,
                        publishedDate: "2025-11-14"
                    },
                    { 
                        id: 50, 
                        title: "Pedidos", 
                        url: "https://www.youtube.com/watch?v=kOieUhES2Z4",
                        thumbnail: "https://img.youtube.com/vi/kOieUhES2Z4/mqdefault.jpg",
                        duration: "00:52",
                        views: 1320,
                        publishedDate: "2025-11-22"
                    },
                    { 
                        id: 51, 
                        title: "Guías", 
                        url: "https://www.youtube.com/watch?v=Tp0QWyrnomA",
                        thumbnail: "https://img.youtube.com/vi/Tp0QWyrnomA/mqdefault.jpg",
                        duration: "01:03",
                        views: 1180,
                        publishedDate: "2025-1-14"
                    },
                    { 
                        id: 52, 
                        title: "Utilidad Detallada", 
                        url: "https://www.youtube.com/watch?v=Lkc5_Xg_FJA",
                        thumbnail: "https://img.youtube.com/vi/Lkc5_Xg_FJA/mqdefault.jpg",
                        duration: "01:20",
                        views: 1180,
                        publishedDate: "2025-11-14"
                    }
                ]
            },
            {
                id: 10,
                title: "Finanzas",
                icon: "fas fa-coins",
                videos: [
                    { 
                        id: 53, 
                        title: "Abrir una caja y exportar reportes", 
                        url: "https://www.youtube.com/watch?v=ga_7ROkdd4o",
                        thumbnail: "https://img.youtube.com/vi/ga_7ROkdd4o/mqdefault.jpg",
                        duration: "02:59",
                        views: 1780,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 54, 
                        title: "Movimientos", 
                        url: "https://www.youtube.com/watch?v=V2QhSzD5mOE",
                        thumbnail: "https://img.youtube.com/vi/V2QhSzD5mOE/mqdefault.jpg",
                        duration: "00:56",
                        views: 1520,
                        publishedDate: "2025-11-17"
                    },
                    { 
                        id: 55, 
                        title: "Transacciones", 
                        url: "https://www.youtube.com/watch?v=uGBJ3EgcBhw",
                        thumbnail: "https://img.youtube.com/vi/uGBJ3EgcBhw/mqdefault.jpg",
                        duration: "01:29",
                        views: 1350,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 56, 
                        title: "Ingresos", 
                        url: "https://www.youtube.com/watch?v=SQjk7J8oZKs",
                        thumbnail: "https://img.youtube.com/vi/SQjk7J8oZKs/mqdefault.jpg",
                        duration: "01:10",
                        views: 1280,
                        publishedDate: "2025-11-16"
                    },
                    { 
                        id: 57, 
                        title: "Cuentas por cobrar", 
                        url: "https://www.youtube.com/watch?v=0FXL_oEV0ZM",
                        thumbnail: "https://img.youtube.com/vi/0FXL_oEV0ZM/mqdefault.jpg",
                        duration: "02:09",
                        views: 1620,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 58, 
                        title: "Cuentas por pagar", 
                        url: "https://www.youtube.com/watch?v=z4AhNOhkj9I",
                        thumbnail: "https://img.youtube.com/vi/z4AhNOhkj9I/mqdefault.jpg",
                        duration: "01:24",
                        views: 1450,
                        publishedDate: "2025-11-17"
                    },
                    { 
                        id: 59, 
                        title: "Pagos", 
                        url: "https://www.youtube.com/watch?v=tEhnNvY_YKc",
                        thumbnail: "https://img.youtube.com/vi/tEhnNvY_YKc/mqdefault.jpg",
                        duration: "01:38",
                        views: 1180,
                        publishedDate: "2025-11-06"
                    },
                    { 
                        id: 60, 
                        title: "Balance", 
                        url: "https://www.youtube.com/watch?v=OvVNJ9u50zk",
                        thumbnail: "https://img.youtube.com/vi/OvVNJ9u50zk/mqdefault.jpg",
                        duration: "00:54",
                        views: 1780,
                        publishedDate: "2025-11-19"
                    }
                ]
            },
            {
                id: 11,
                title: "Comprobantes avanzado",
                icon: "fas fa-file-invoice",
                videos: [
                    { 
                        id: 61, 
                        title: "Envío por resumen", 
                        url: "https://www.youtube.com/watch?v=CJByTUP9JJg",
                        thumbnail: "https://img.youtube.com/vi/CJByTUP9JJg/mqdefault.jpg",
                        duration: "01:32",
                        views: 1920,
                        publishedDate: "2025-11-22"
                    },
                    { 
                        id: 62, 
                        title: "Validación de comprobantes", 
                        url: "https://www.youtube.com/watch?v=pDuI17yJ4HE",
                        thumbnail: "https://img.youtube.com/vi/pDuI17yJ4HE/mqdefault.jpg",
                        duration: "01:14",
                        views: 1920,
                        publishedDate: "2025-11-22"
                    },
                    { 
                        id: 63, 
                        title: "Documentos con retención", 
                        url: "https://www.youtube.com/watch?v=hzFQ4QViWNY",
                        thumbnail: "https://img.youtube.com/vi/hzFQ4QViWNY/mqdefault.jpg",
                        duration: "01:37",
                        views: 1920,
                        publishedDate: "2025-11-19"
                    },
                    { 
                        id: 64, 
                        title: "Documentos de percepción", 
                        url: "https://www.youtube.com/watch?v=03Gqn_yvjqg",
                        thumbnail: "https://img.youtube.com/vi/03Gqn_yvjqg/mqdefault.jpg",
                        duration: "01:20",
                        views: 1650,
                        publishedDate: "2025-11-22"
                    },
                    { 
                        id: 65, 
                        title: "Liquidaciones de compra", 
                        url: "https://www.youtube.com/watch?v=d0yod1l8JD0",
                        thumbnail: "https://img.youtube.com/vi/d0yod1l8JD0/mqdefault.jpg",
                        duration: "01:13",
                        views: 1420,
                        publishedDate: "2025-11-24"
                    },
                    { 
                        id: 66, 
                        title: "Comprobantes de contingencia", 
                        url: "https://www.youtube.com/watch?v=T4T54Pye5LQ",
                        thumbnail: "https://img.youtube.com/vi/T4T54Pye5LQ/mqdefault.jpg",
                        duration: "02:42",
                        views: 1280,
                        publishedDate: "2025-11-24"
                    }
                ]
            },
            {
                id: 12,
                title: "Contabilidad",
                icon: "fas fa-calculator",
                videos: [
                    { 
                        id: 67, 
                        title: "Exportar reporte", 
                        url: "https://www.youtube.com/watch?v=L2x7-6goLEc",
                        thumbnail: "https://img.youtube.com/vi/L2x7-6goLEc/mqdefault.jpg",
                        duration: "01:07",
                        views: 1780,
                        publishedDate: "2025-11-14"
                    },
                    { 
                        id: 68, 
                        title: "Exportar formatos - sist. contable", 
                        url: "https://www.youtube.com/watch?v=LnQo8Gi59Fw",
                        thumbnail: "https://img.youtube.com/vi/LnQo8Gi59Fw/mqdefault.jpg",
                        duration: "00:55",
                        views: 1520,
                        publishedDate: "2025-11-17"
                    },
                    { 
                        id: 69, 
                        title: "Reporte resumido de ventas", 
                        url: "https://www.youtube.com/watch?v=4mLYs5CxMAg",
                        thumbnail: "https://img.youtube.com/vi/4mLYs5CxMAg/mqdefault.jpg",
                        duration: "00:48",
                        views: 1350,
                        publishedDate: "2025-11-22"
                    },
                    { 
                        id: 70, 
                        title: "Libro mayor", 
                        url: "https://www.youtube.com/watch?v=X96ztlwvgcM",
                        thumbnail: "https://img.youtube.com/vi/X96ztlwvgcM/mqdefault.jpg",
                        duration: "11:45",
                        views: 1620,
                        publishedDate: "2023-03-28"
                    },
                    { 
                        id: 71, 
                        title: "SIRE", 
                        url: "https://www.youtube.com/watch?v=Vgw2aart8zo",
                        thumbnail: "https://img.youtube.com/vi/Vgw2aart8zo/mqdefault.jpg",
                        duration: "02:12",
                        views: 1850,
                        publishedDate: "2025-11-22"
                    }
                ]
            },
            {
                id: 13,
                title: "Impresora/Aplicaciones",
                icon: "fas fa-desktop",
                videos: [
                    { 
                        id: 72, 
                        title: "Instalacion de aplicativo", 
                        url: "https://www.youtube.com/watch?v=uPTO1KtwD1o",
                        thumbnail: "https://img.youtube.com/vi/uPTO1KtwD1o/mqdefault.jpg",
                        duration: "01:43",
                        views: 1920,
                        publishedDate: "2025-11-24"
                    },
                    { 
                        id: 73, 
                        title: " PASOS PREVIOS Y INSTALACIÓN EN PC", 
                        url: "https://www.youtube.com/watch?v=GrxujMzViR8",
                        thumbnail: "https://img.youtube.com/vi/GrxujMzViR8/mqdefault.jpg",
                        duration: "03:22",
                        views: 1920,
                        publishedDate: "2025-11-24"
                    },
                    { 
                        id: 74, 
                        title: "CONEXIÓN IMPRESOR A CELULAR VIA BLUETOOTH PT20", 
                        url: "https://www.youtube.com/watch?v=pPKkHAINCh8",
                        thumbnail: "https://img.youtube.com/vi/pPKkHAINCh8/mqdefault.jpg",
                        duration: "02:13",
                        views: 1920,
                        publishedDate: "2025-11-24"
                    },
                ]
            }
        ];

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado, iniciando soporte...');
            // Cargar las categorías y videos
            renderTutorials();
            
            // Configurar el modal
            const modal = document.getElementById('videoModal');
        });
        
        // Función para formatear el número de vistas
        function formatViews(views) {
            const num = parseInt(views);
            
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            } else {
                return num.toString();
            }
        }
        
        // Función para formatear la fecha de publicación
        function formatDate(publishedDate) {
            const date = new Date(publishedDate);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays < 7) {
                return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
            } else if (diffDays < 30) {
                const weeks = Math.floor(diffDays / 7);
                return `Hace ${weeks} semana${weeks > 1 ? 's' : ''}`;
            } else if (diffDays < 365) {
                const months = Math.floor(diffDays / 30);
                return `Hace ${months} mes${months > 1 ? 'es' : ''}`;
            } else {
                const years = Math.floor(diffDays / 365);
                return `Hace ${years} año${years > 1 ? 's' : ''}`;
            }
        }
        
        function renderTutorials(categories = tutorialCategories) {
            console.log('Renderizando tutoriales...');
            const container = document.getElementById('tutorial-columns');
            
            // Dividir las categorías en tres columnas
            const columnCount = 3;
            const itemsPerColumn = Math.ceil(categories.length / columnCount);
            const columns = [];
            
            for (let i = 0; i < columnCount; i++) {
                const start = i * itemsPerColumn;
                const end = start + itemsPerColumn;
                columns.push(categories.slice(start, end));
            }
            
            let columnsHTML = '';
            
            columns.forEach(columnCategories => {
                let columnHTML = '<div class="column">';
                
                columnCategories.forEach(category => {
                    const videoCount = category.videos.length;
                    
                    columnHTML += `
                        <div class="tf-accordion" data-category="${category.id}">
                            <div class="tf-accordion-header">
                                <button type="button" class="tf-accordion-btn">
                                    <span><i class="${category.icon}"></i> ${category.title} <span class="category-counter">${videoCount}</span></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tf-accordion-icon">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="tf-accordion-content">
                                <div class="video-list">
                    `;
                    
                    category.videos.forEach(video => {
                        const formattedViews = formatViews(video.views);
                        const formattedDate = formatDate(video.publishedDate);
                        
                        columnHTML += `
                            <div class="video-item" data-video-id="${video.id}" data-video-url="${video.url}" data-video-title="${video.title}" data-video-description="Tutorial sobre ${video.title}">
                                <div class="video-thumbnail">
                                    <img src="${video.thumbnail}" alt="${video.title}">
                                    <div class="video-duration">${video.duration}</div>
                                </div>
                                <div class="video-info">
                                    <div class="video-title">${video.title}</div>
                                    <div class="video-meta">
                                        <span>${formattedViews} vistas</span>
                                        <span>Publicado: ${formattedDate}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    columnHTML += `
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                columnHTML += '</div>';
                columnsHTML += columnHTML;
            });
            
            container.innerHTML = columnsHTML;
        }
        
        // Configurar eventos globales (Modal y Búsqueda) fuera de DOMContentLoaded para mayor robustez
        document.addEventListener('click', function(e) {
            // Delegación para items de video
            const videoItem = e.target.closest('.video-item');
            if (videoItem) {
                console.log('Click detectado en video (delegación)');
                e.preventDefault();
                
                const videoUrl = videoItem.getAttribute('data-video-url');
                const videoTitle = videoItem.getAttribute('data-video-title');
                const videoDescription = videoItem.getAttribute('data-video-description');
                
                openVideoModal(videoUrl, videoTitle, videoDescription);
                return; // Evitar que se propague a otros handlers si es necesario
            }

            // Cerrar modal al hacer clic en la X
            if (e.target.closest('.close')) {
                const modal = document.getElementById('videoModal');
                const videoPlayer = document.getElementById('videoPlayer');
                if (modal) {
                    modal.style.display = 'none';
                    if (videoPlayer) videoPlayer.src = '';
                }
            }
            
            // Cerrar modal al hacer clic fuera del contenido
            if (e.target.id === 'videoModal') {
                const modal = document.getElementById('videoModal');
                const videoPlayer = document.getElementById('videoPlayer');
                modal.style.display = 'none';
                if (videoPlayer) videoPlayer.src = '';
            }
        });
        
        // Configurar delegación de eventos para el acordeón
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.tf-accordion-btn');
            
            if (button) {
                console.log('Click detectado en botón de acordeón (delegación)');
                e.preventDefault(); // Prevenir comportamiento por defecto si lo hubiera
                
                const content = button.parentElement.nextElementSibling;
                
                // Cerrar otros acordeones si está abierto
                if (!content.classList.contains('active')) {
                    document.querySelectorAll('.tf-accordion-content.active').forEach(item => {
                        item.classList.remove('active');
                    });
                    document.querySelectorAll('.tf-accordion-btn.active').forEach(item => {
                        item.classList.remove('active');
                    });
                }
                
                // Alternar el acordeón actual
                button.classList.toggle('active');
                content.classList.toggle('active');
            }
        });

        // Configurar delegación de eventos para la búsqueda (Solución para Vue/Reemplazo de DOM)
        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'searchInput') {
                console.log('Búsqueda detectada (delegación):', e.target.value);
                filterTutorials(e.target.value);
            }
        });
        
        function openVideoModal(videoUrl, title, description) {
            const modal = document.getElementById('videoModal');
            const videoPlayer = document.getElementById('videoPlayer');
            const videoTitle = document.getElementById('videoTitle');
            const videoDescription = document.getElementById('videoDescription');
            
            // Convertir URL de YouTube a embed con autoplay
            let embedUrl = videoUrl;
            if (videoUrl.includes('youtu.be') || videoUrl.includes('youtube.com')) {
                const videoId = videoUrl.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
                if (videoId && videoId[1]) {
                    embedUrl = `https://www.youtube.com/embed/${videoId[1]}?autoplay=1`;
                }
            }
            
            videoPlayer.src = embedUrl;
            videoTitle.textContent = title;
            videoDescription.textContent = description;
            modal.style.display = 'block';
        }
        
        function filterTutorials(searchTerm) {
            console.log('Filtrando por:', searchTerm);
            if (!searchTerm || searchTerm.trim() === '') {
                renderTutorials(tutorialCategories);
                return;
            }
            
            const term = searchTerm.toLowerCase().trim();
            
            const filteredCategories = tutorialCategories.map(category => {
                const filteredVideos = category.videos.filter(video => 
                    video.title.toLowerCase().includes(term)
                );
                
                return {
                    ...category,
                    videos: filteredVideos
                };
            }).filter(category => category.videos.length > 0);
            
            console.log('Categorías encontradas:', filteredCategories.length);
            
            if (filteredCategories.length === 0) {
                const container = document.getElementById('tutorial-columns');
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; color: #ccc;"></i>
                        <h3>No se encontraron resultados</h3>
                        <p>Intenta con otros términos de búsqueda</p>
                    </div>
                `;
                return;
            }

            renderTutorials(filteredCategories);
            
            // Abrir todos los acordeones cuando se filtra
            setTimeout(() => {
                const buttons = document.querySelectorAll('.tf-accordion-btn');
                console.log('Expandiendo', buttons.length, 'acordeones');
                buttons.forEach(button => {
                    const content = button.parentElement.nextElementSibling;
                    button.classList.add('active');
                    content.classList.add('active');
                });
            }, 100);
        }
    </script>
@endpush

@endsection