<template>
    <div>
        <div class="row ">

            <div class="col-md-12 col-lg-12 col-xl-12 filter-container">
                <div class="btn-filter-content">
                    <el-button
                        type="secondary"
                        class="btn-show-filter mb-2"
                        :class="{ shift: isVisible }"
                        @click="toggleInformation"
                    >
                        {{ isVisible ? "Ocultar filtros" : "Mostrar filtros" }}
                    </el-button>
                </div>                
                <div class="row" v-if="applyFilter && isVisible">
                    <div class="col-12 pb-3">Filtrar por:</div>
                    <div class="col-lg-2 col-md-4 col-sm-12 pb-2">
                        <div class="d-flex">
                            <el-select v-model="search.column"  placeholder="Select">
                                <el-option v-for="(label, key) in columns" :key="key" :value="key" :label="label"></el-option>
                            </el-select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-12 pb-2">
                        <template v-if="search.column=='date_of_issue' || search.column=='date_of_due' || search.column=='date_of_payment'">
                            <el-date-picker
                                v-model="search.value"
                                type="date"
                                style="width: 100%;"
                                placeholder="Buscar"
                                value-format="yyyy-MM-dd">
                            </el-date-picker>
                        </template>
                        <template v-else>
                            <el-input placeholder="Nombre del cliente"
                                v-model="search.value"
                                style="width: 100%;">
                            </el-input>
                        </template>
                    </div>
                    <div class="col-lg-2 col-md-2 form-group">
                        <el-select placeholder="Serie" v-model="search.series" filterable clearable>
                            <el-option v-for="option in series" :key="option.number" :value="option.number" :label="option.number"></el-option>
                        </el-select>
                    </div>
                    <div class="col-lg-2 col-md-2 form-group"  >
                            <el-input placeholder="Número"
                                      v-model="search.number">
                            </el-input>
                    </div>

                    <div class="col-lg-2 col-md-3 form-group">
                        <el-select placeholder="Estado de pago" v-model="search.total_canceled" clearable>
                            <el-option :value="1" label="Pagado"></el-option>
                            <el-option  :value="0" label="Pendiente"></el-option>
                        </el-select>
                    </div>
                    <div class="col-lg-2 col-md-2 form-group">
                        <el-input v-model="search.purchase_order" placeholder="Orden de compra" clearable></el-input>
                    </div>
                    <div class="col-lg-4 col-md-4 form-group">
                        <el-input v-model="search.observations" placeholder="Observaciones" clearable></el-input>
                    </div>
                    <el-checkbox v-model="search_by_plate" :disabled="recordItem != null">
                        Filtrar por placa
                    </el-checkbox>
                    <div v-if="search_by_plate" class="col-lg-2 col-md-2 col-sm-12 pb-2">
                        <div class="form-group"  >
                            <el-input v-model="search.license_plate"  placeholder="Placa" clearable></el-input>
                        </div>
                    </div>
                    <div class="col-lg-1 col-md-2 form-group">
                        <el-button class="w-100" type="primary" @click="getRequestData">
                            <i class="fa fa-search"></i>
                        </el-button>
                    </div>
                </div>

            </div>


            <div class="col-md-12 position-relative">
                <div class="scroll-shadow shadow-left" v-show="showLeftShadow"></div>
                <div class="scroll-shadow shadow-right" v-show="showRightShadow"></div>
                <div class="table-responsive" :style="{ overflowX: 'auto', position: 'relative', minHeight: (loading_table && !loading_pagination) ? '400px' : 'auto', maxHeight: (loading_table && !loading_pagination) ? '70vh' : 'none' }" ref="scrollContainer">
                    <!-- Overlay con imagen PNG para carga inicial -->
                    <div v-if="loading_table && !loading_pagination" class="table-loading-overlay">
                        <div class="table-loader-content">
                            <img :src="loaderImageUrl" alt="TUKIFAC" class="table-loader-image">
                            <p class="table-loader-text">Cargando...</p>
                        </div>
                    </div>
                    
                    <!-- Overlay sutil para cambio de página (fade) -->
                    <div v-if="loading_pagination" class="table-loading-fade"></div>
                    
                    <table class="table" :class="{ 
                        'table-fade': loading_pagination,
                        'table-loading': loading_table && !loading_pagination
                    }">
                        <thead>
                        <slot name="heading"></slot>
                        </thead>
                        <tbody v-if="!loading_table || loading_pagination">
                        <slot v-for="(row, index) in records" :row="row" :index="customIndex(index)"></slot>
                        </tbody>
                    </table>

                    <div class="row mb-5">
                        <div class="col-md-4 text-center">Total notas de venta en soles S/ {{totals.total_pen}}</div>
                        <div class="col-md-4 text-center">Total pagado en soles S/ {{totals.total_paid_pen}}</div>
                        <div class="col-md-4 text-center">Total por cobrar en soles S/ {{totals.total_pending_paid_pen}}</div>
                    </div>

                    <div v-if="!loading_table && pagination.total > 0">
                        <el-pagination
                                @current-change="getRecords"
                                layout="total, prev, pager, next"
                                :total="pagination.total"
                                :current-page.sync="pagination.current_page"
                                :page-size="pagination.per_page"
                                :disabled="loading_pagination">
                        </el-pagination>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>


<script>

    import moment from 'moment'
    import queryString from 'query-string'

    export default {
        props: {
            resource: String,
            applyFilter:{
                type: Boolean,
                default: true,
                required: false
            }
        },
        data () {
            return {
                loading_table: false, // Estado de carga específico para la tabla
                loading_pagination: false, // Estado de carga para cambio de página (más sutil)
                search: {
                    column: null,
                    value: null,
                    series: null,
                    total_canceled: null,
                    observations: null
                },
                totals: {
                    total_pen: 0,
                    total_paid_pen: 0,
                    total_pending_paid_pen: 0
                },
                columns: [],
                records: [],
                pagination: {},
                series: [],
                isVisible: false,
                search_by_plate:false,
                recordItem: null,
                showLeftShadow: false,
                showRightShadow: false,
            }
        },
        computed: {
            // URL de la imagen del loader (se resuelve en runtime, no en build time)
            loaderImageUrl() {
                return '/logo/tuki-load.webp';
            },
        },
        created() {
            this.$eventHub.$on('reloadData', () => {
                this.loading_table = true;
                this.getRecords().then(() => {
                    this.getTotals();
                });
            })
        },
        async mounted () {
            let column_resource = _.split(this.resource, '/')
           // console.log(column_resource)
            await this.$http.get(`/${_.head(column_resource)}/columns`).then((response) => {
                this.columns = response.data
                this.search.column = _.head(Object.keys(this.columns))
            });

            await this.$http.get(`/${_.head(column_resource)}/columns2`).then((response) => {
                this.series = response.data.series
            });

            // Activar loading antes de cargar los registros (carga inicial)
            this.loading_table = true;
            // Pequeño delay para asegurar que el DOM esté listo
            await this.$nextTick();
            await this.getRecords()
            await this.getTotals()

            this.$nextTick(() => {
                const el = this.$refs.scrollContainer;
                if (el) {
                    el.addEventListener('scroll', this.checkScrollShadows);
                    this.checkScrollShadows();
                }
            });
        },
        methods: {
            checkScrollShadows() {
                const el = this.$refs.scrollContainer;
                if (!el) return;

                const scrollLeft = el.scrollLeft;
                const scrollRight = el.scrollWidth - el.clientWidth - scrollLeft;

                const threshold = 2;

                this.showLeftShadow = scrollLeft > threshold;
                this.showRightShadow = scrollRight > threshold;
            },
            toggleInformation() {
                this.isVisible = !this.isVisible;
            },
            getTotals(){

                this.$http.get(`/${this.resource}/totals?${this.getQueryParameters()}`)
                    .then((response) => {
                        this.totals = response.data
                    });

            },
            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            },
            getRecords() {
                // Si ya hay datos, usar efecto de fade (cambio de página)
                // Si no hay datos, usar overlay con imagen (carga inicial)
                const isPagination = this.records.length > 0;
                
                if (isPagination) {
                    this.loading_pagination = true;
                } else {
                    // Solo activar loading_table si no está ya activo (evita parpadeo)
                    if (!this.loading_table) {
                        this.loading_table = true;
                    }
                }
                
                // Pequeño delay para que el efecto sea visible
                const startTime = Date.now();
                const minDelay = isPagination ? 150 : 300; // 300ms mínimo para carga inicial, 150ms para paginación
                
                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    const elapsed = Date.now() - startTime;
                    const remainingDelay = Math.max(0, minDelay - elapsed);
                    
                    // Aplicar delay mínimo para que el efecto sea visible
                    return new Promise((resolve) => {
                        setTimeout(() => {
                            this.records = response.data.data
                            this.pagination = response.data.meta
                            this.pagination.per_page = parseInt(response.data.meta.per_page)
                            this.loading_table = false
                            this.loading_pagination = false
                            resolve();
                        }, remainingDelay);
                    });
                }).catch((error) => {
                    this.loading_table = false
                    this.loading_pagination = false
                    console.error('Error al cargar registros:', error);
                });
            },
            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...this.search
                })
            },
            async changeClearInput(){
                this.search.value = ''
                await this.getRecords()
                await this.getTotals()

            },
            async getRequestData()
            {
                // Al filtrar, siempre usar overlay completo (es como una nueva búsqueda)
                this.loading_table = true;
                try {
                    await this.getRecords()
                    await this.getTotals()
                } finally {
                    // getRecords ya maneja el loading_table, pero lo aseguramos aquí
                    if (this.records.length === 0) {
                        this.loading_table = false;
                    }
                }
            }
        }
    }
</script>
<style>
/* Estilos para el efecto de carga de la tabla con imagen */
.table-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.98);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    min-height: 400px;
    max-height: 70vh;
    height: 100%;
    backdrop-filter: blur(3px);
    animation: fadeIn 0.3s ease-in;
    pointer-events: none;
}

.table-loader-content {
    text-align: center;
    padding: 3rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.table-loader-image {
    width: 150px;
    max-width: 90%;
    height: auto;
    object-fit: contain;
    animation: pulse-image 1.5s ease-in-out infinite;
    margin-bottom: 1.5rem;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
}

.table-loader-text {
    color: #666;
    font-size: 1.1rem;
    font-weight: 500;
    font-family: 'Montserrat', sans-serif;
    animation: fadeInOut 2s ease-in-out infinite;
    letter-spacing: 0.5px;
}

@keyframes pulse-image {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}

@keyframes fadeInOut {
    0%, 100% {
        opacity: 0.6;
    }
    50% {
        opacity: 1;
    }
}

.table-loading {
    opacity: 0.6;
    pointer-events: none;
    user-select: none;
}

/* Efecto fade para cambio de página */
.table-loading-fade {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.6);
    z-index: 5;
    pointer-events: none;
    animation: fadeIn 0.2s ease-in;
}

.table-fade {
    opacity: 0.7;
    transition: opacity 0.2s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.table-responsive {
    position: relative;
}
</style>
