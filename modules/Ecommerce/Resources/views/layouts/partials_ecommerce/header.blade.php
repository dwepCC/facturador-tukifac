
<style>
.header-dropdown a img {
    border-radius: 8px;
    padding: 4px;
}

@media (max-width: 768px) {
    .header-dropdown {
        min-width: 100px !important;
    }
}

.search_input {
    margin-bottom: 0.1rem;
    border-radius: 20px !important;
}

.search_input:focus {
    background-color: #fff;
    border-color: #fff;
    box-shadow: none;
}

.header-contact span {
    font-weight: normal;
}

div.cart-dropdown {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: transparent;
}

.header .dropdown-toggle {
    color: #fff;
    font-size: 10px;
    background-color: #1f1f39;
    height: 35px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 20px;
    padding: 0 10px;
}

.dropdown-toggle .cart-count {
    background-color: transparent !important;
    color: white !important;
    margin-top: 12px;
    margin-right: 27px;
}

.search_input:focus {
    border: 1px solid var(--background-color) !important;
    background-color: transparent !important;
}

.search_input {
    width: 100%;
    height: 38px !important;
    border-radius: 20px !important;
    background-color: #eff0f6 !important;
}

.header-dropdown-inside {
    position: relative; 
}

.header-dropdown-inside .search-icon {
    position: absolute;
    left: 10px; 
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.header-dropdown-inside .search_input {
    padding-left: 40px !important; 
    padding-right: 40px !important;
    width: 100%;
}

.header-dropdown-inside .clear-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    cursor: pointer;
    display: none;
}
.header-dropdown-inside input:focus + .clear-icon,
.header-dropdown-inside input:not(:placeholder-shown) + .clear-icon {
    display: inline-block; /* Muestra el ícono */
}

.header-middle .container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.header-left,
.header-right,
#header_bar {
    min-width: 0;
}

#header_bar {
    flex: 1 1 auto;
}

.header-left {
    flex: 0 0 auto;
}

.header-right {
    flex: 0 0 auto;
}

.ecom-search {
    min-width: 0;
    width: 100%;
    max-width: 680px;
}

@media (max-width: 991.98px) {
    /* flex-wrap lo refina ecommerce_tuki_shim.css (.tuki_storefront_header) */
    .header-middle .container {
        flex-wrap: wrap;
        gap: 10px;
    }
    .header-left .logo {
        max-width: 130px !important;
    }
    .ecom-search {
        max-width: none;
    }
    .header-contact {
        display: none;
    }
}

 </style>

 <header class="header tuki_storefront_header">

     <div class="header-middle">
         <div   class="container">
             <div class="header-left">
                 <a href="{{ route("tenant.ecommerce.index") }}" class="logo" style="max-width: 180px">
                    @if(isset($information->logo))
                        <img src="{{ asset('storage/uploads/logos/'.$information->logo) }}" alt="Logo" />
                    @else
                        <img src="{{asset('logo/tulogo.png')}}" alt="Logo" />
                    @endif
                 </a>
             </div><!-- End .header-left -->
             
             
             <div id="header_bar" class="header-center header-dropdowns">

                 <div class="header-dropdown header-dropdown-inside ecom-search tuki_ecom_search">
                    <div
                        class="tuki_ecom_search_backdrop"
                        v-show="searchPanelVisible"
                        @click="closeSearchPanel"
                        aria-hidden="true"
                    ></div>
                    <div class="tuki_ecom_search_inner">
                        <img src="{{ asset('images/search.svg') }}" alt="" class="search-icon" width="18" height="18" role="presentation">
                        <input
                            type="search"
                            enterkeyhint="search"
                            autocomplete="off"
                            placeholder="Buscar..."
                            class="search_input form-control form-control-lg"
                            v-model="value"
                            @keyup="autoComplete"
                            @input="autoComplete"
                            @focus="onSearchFocus"
                            @keydown.esc.prevent="closeSearchPanel"
                        />
                        <img src="{{ asset('images/circle-xmark.svg') }}" alt="Limpiar búsqueda" class="clear-icon" width="18" height="18" @click.prevent="clearInput">
                        <transition name="tuki-ecom-search">
                            <div
                                v-show="searchPanelVisible"
                                class="header-menu tuki_ecom_search_panel"
                                role="listbox"
                                :aria-label="'Resultados: ' + results.length"
                            >
                                <ul class="tuki_ecom_search_list list-unstyled mb-0">
                                    <li v-for="result in results" :key="result.id" class="tuki_ecom_search_item" role="option">
                                        <a :href="'/ecommerce/item/' + result.id" class="tuki_ecom_search_hit" @click="onSelectResult">
                                            <span class="tuki_ecom_search_hit__thumb">
                                                <img :src="result.image_url_small" :alt="result.description" loading="lazy" width="52" height="52">
                                            </span>
                                            <span class="tuki_ecom_search_hit__main">
                                                <span class="tuki_ecom_search_hit__title">@{{ result.description }}</span>
                                            </span>
                                            <span class="tuki_ecom_search_hit__price">@{{ result.sale_unit_price }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </transition>
                    </div>
                 </div><!-- End .header-dropown -->


             </div><!-- End .headeer-center -->

             <div class="header-right">
                 <button class="mobile-menu-toggler" type="button" aria-label="Abrir menú">
                     <i class="fas fa-bars"></i>
                 </button>
                 <div class="header-contact">
                     <span> Atención al</span>
                     <i class="fab fa-whatsapp"></i> <a href="#"><strong>{{$information->information_contact_phone}}</strong></a>
                 </div><!-- End .header-contact -->
                @include('ecommerce::layouts.partials_ecommerce.cart_dropdown')
                @include('ecommerce::partials.headers.session')

             </div><!-- End .header-right -->
         </div><!-- End .container -->
     </div><!-- End .header-middle -->

     <div class="header-bottom sticky-header">
        <div class="container d-flex">
            <nav class="main-nav flex-grow-1">

             </nav>
         </div><!-- End .header-bottom -->
     </div><!-- End .header-bottom -->
 </header><!-- End .header -->

 @push('scripts')
 <script type="text/javascript">
    var app = new Vue({
        el: '#header_bar',
        data: {
            value: '',
            suggestions: [],
            resource: 'ecommerce',
            results: [],
            searchPanelDismissed: false,
        },
        computed: {
            searchPanelVisible() {
                return this.results.length > 0 && !this.searchPanelDismissed;
            },
        },
        created() {
            this.getItems();
        },
        mounted() {
            var self = this;
            this._tukiSearchOnDocDown = function (e) {
                var root = self.$el;
                if (!root || !e.target) {
                    return;
                }
                if (!root.contains(e.target)) {
                    self.closeSearchPanel();
                }
            };
            document.addEventListener('mousedown', this._tukiSearchOnDocDown, true);
            document.addEventListener('touchstart', this._tukiSearchOnDocDown, true);
        },
        beforeDestroy() {
            if (this._tukiSearchOnDocDown) {
                document.removeEventListener('mousedown', this._tukiSearchOnDocDown, true);
                document.removeEventListener('touchstart', this._tukiSearchOnDocDown, true);
            }
        },
        methods: {
            closeSearchPanel() {
                this.searchPanelDismissed = true;
            },
            onSearchFocus() {
                this.searchPanelDismissed = false;
                this.autoComplete();
            },
            onSelectResult() {
                this.closeSearchPanel();
            },
            // Método para limpiar el campo de texto
            clearInput() {
                this.value = '';
                this.results = [];
                this.searchPanelDismissed = false;
            },

            // Método para manejar la autocompletación
            autoComplete() {
                this.searchPanelDismissed = false;
                if (this.value) {
                    let val = this.value.toUpperCase();
                    this.results = this.suggestions.filter((obj) => {
                        let desc = obj.description.toUpperCase();
                        let internal_id = obj.internal_id ? obj.internal_id.toUpperCase() : '';
                        return desc.includes(val) || internal_id.includes(val);
                    });
                } else {
                    this.results = [];
                }
            },

            // Método para obtener las sugerencias desde el backend
            getItems() {
                let contex = this;
                fetch(`/${this.resource}/items_bar`)
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (myJson) {
                        contex.suggestions = myJson.data;
                    });
            },

            // Método para manejar el clic en una sugerencia
            suggestionClick(item) {
                this.results = [];
                this.value = item.description;
            }
        }
    });
</script>
 @endpush
