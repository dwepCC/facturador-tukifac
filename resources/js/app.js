import './bootstrap';

import Vue from 'vue'
import store from './store'
import ElementUI from 'element-ui'

import lang from 'element-ui/lib/locale/lang/es'
import locale from 'element-ui/lib/locale'

// Cargar Bootstrap PRIMERO
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.js'; // Incluye Popper

// Luego Element UI
import '../sass/element-ui.scss';
import 'element-ui/lib/theme-chalk/index.css';

locale.use(lang)

ElementUI.Select.computed.readonly = function () {
    const isIE = !this.$isServer && !Number.isNaN(Number(document.documentMode));
    return !(this.filterable || this.multiple || !isIE) && !this.visible;
};

export default ElementUI;

Vue.use(ElementUI, { size: 'small' })
Vue.prototype.$eventHub = new Vue()

// Tenant app: only tenant components here
// CRÍTICO: Importar componentes de forma síncrona
// En producción, Vite puede hacer que este import sea asíncrono si hay code splitting
// Por eso verificamos que se ejecute antes de inicializar Vue
import './tenant-components'

// OPTIMIZACIÓN: Eliminar logs en producción para mejorar rendimiento
if (process.env.NODE_ENV !== 'production') {
    console.log('📦 app.js: Después del import de tenant-components');
    console.log('📦 Componentes disponibles:', Object.keys(Vue.options.components).length);
}

// Importar scripts migrados desde dom-fixes.js
// OPTIMIZACIÓN: Diferir inicialización de scripts DOM para no bloquear renderizado
import { applyThemeAndShowContent, setupHeaderDomEvents, setupEcommerceAuthHandlers, updateTenantPageTitle } from './tenant/dom-fixes';

// Inicializar lógica DOM migrada después de que Vue se monte
// Esto evita bloquear el renderizado inicial
function initDomFixes() {
    if (window && window.vc_visual && window.vc_visual.sidebar_theme) {
        applyThemeAndShowContent(window.vc_visual.sidebar_theme);
    }
    setupHeaderDomEvents();
    setupEcommerceAuthHandlers();
    updateTenantPageTitle();
}

// System reports moved to system.js



import VueClipboard from 'vue-clipboard2'
Vue.use(VueClipboard)


import moment from 'moment';

Vue.mixin({
    filters: {
        toDecimals(number, decimal = 2) {
            return Number(number).toFixed(decimal);
        },
        DecimalText: function (number, decimal = 2) {
            return isNaN(parseFloat(number)) ? number : Number(number).toFixed(decimal);
        },
        toDate(date) {
            if (date) {
                return moment(date).format('DD/MM/YYYY');
            }
            return '';
        },
        toTime(time) {
            if (time) {
                if (time.length === 5) {
                    return moment(time + ':00', 'HH:mm:ss').format('HH:mm:ss');
                }
                return moment(time, 'HH:mm:ss').format('HH:mm:ss');
            }
            return '';
        },
        pad(value, fill = '', length = 3) {
            if (value) {
                return String(value).padStart(length, fill);
            }
            return value;
        }
    },
    methods: {
        axiosError(error) {
            const response = error.response;
            const status = response.status;
            if (status === 422) {
                this.errors = response.data
            }
            if (status === 500) {
                this.$message({
                    type: 'info',
                    message: response.data.message
                  });
            }
        },
        getResponseValidations(success = true, message = null)
        {
            return {
                success: success,
                message: message
            }
        },
        generalSleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms))
        }
    }
})
// OPTIMIZACIÓN: Inicialización simplificada y robusta de Vue
// Basado en mejores prácticas para Laravel + Vue 2
// Los componentes ya están registrados por el import síncrono de tenant-components
let initVueAppRetryCount = 0;
const MAX_RETRIES = 5;

function initVueApp() {
    const mainWrapper = document.getElementById('main-wrapper');
    const mainInicio = document.getElementById('main-inicio');
    const target = mainWrapper || mainInicio;

    if (target) {
        // Los componentes ya están registrados por el import síncrono de tenant-components
        // El log de tenant-components.js confirma que se registraron 279 componentes
        // Inicializar Vue directamente sin delay para máxima velocidad
        try {
            const vueInstance = new Vue({
                store: store,
                el: target.id ? `#${target.id}` : (mainWrapper ? '#main-wrapper' : '#main-inicio')
            });
            
            if (process.env.NODE_ENV !== 'production') {
                console.log('✅ Vue inicializado correctamente');
            }
            
            // OPTIMIZACIÓN: Inicializar scripts DOM después de que Vue se monte
            // Usar $nextTick para asegurar que el DOM de Vue esté listo
            vueInstance.$nextTick(() => {
                // Usar requestIdleCallback para no bloquear el renderizado
                if (window.requestIdleCallback) {
                    requestIdleCallback(initDomFixes, { timeout: 1000 });
                } else {
                    // Fallback para navegadores sin requestIdleCallback
                    setTimeout(initDomFixes, 0);
                }
            });
        } catch (error) {
            console.error('❌ Error al inicializar Vue:', error);
            console.error('Stack:', error.stack);
            // Reintentar una vez más después de un delay
            if (initVueAppRetryCount < 2) {
                initVueAppRetryCount++;
                setTimeout(initVueApp, 200);
            }
        }
    } else {
        initVueAppRetryCount++;
        if (initVueAppRetryCount >= MAX_RETRIES) {
            console.error('❌ ERROR: Elemento #main-wrapper o #main-inicio no encontrado después de', MAX_RETRIES, 'intentos');
            return;
        }
        // Si el elemento no existe aún, esperar un poco más
        setTimeout(initVueApp, 50);
    }
}

// OPTIMIZACIÓN: Inicializar Vue inmediatamente cuando el DOM esté listo
// Eliminar delays innecesarios para máxima velocidad
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVueApp);
} else {
    // DOM ya está listo, inicializar inmediatamente
    // Usar requestAnimationFrame para sincronizar con el ciclo de renderizado del navegador
    if (window.requestAnimationFrame) {
        requestAnimationFrame(initVueApp);
    } else {
        // Fallback para navegadores sin requestAnimationFrame
        initVueApp();
    }
}
