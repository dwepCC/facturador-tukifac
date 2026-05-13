<template>
    <div>
        <header class="page-header">
            <h2>
                <a href="/dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-package" style="margin-top: -5px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12v9" /><path d="M12 12l-8 -4.5" /><path d="M16 5.25l-8 4.5" /></svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Paquetes de Comprobantes</span>
                </li>
            </ol>
        </header>

        <div class="row">
            <div class="col-lg-4">
                <section class="card">
                    <header class="card-header bg-info">
                        <div class="card-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-users"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                        </div>
                        <h2 class="card-title">Clientes</h2>
                    </header>
                    <div class="card-body">
                        <el-input placeholder="Buscar por nombre, RUC, correo o dominio" v-model="searchQuery" clearable @input="scheduleClientSearch"></el-input>
                        <div class="table-responsive mt-3" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>RUC/DNI</th>
                                        <th>Plan</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, idx) in records" :key="row.id">
                                        <td>{{ idx + 1 }}</td>
                                        <td>{{ row.name }}</td>
                                        <td>{{ row.number }}</td>
                                        <td>{{ row.plan }}</td>
                                        <td class="text-right">
                                            <el-button type="primary" size="mini" @click="selectClient(row.id)">Gestionar</el-button>
                                        </td>
                                    </tr>
                                    <tr v-if="records.length === 0">
                                        <td colspan="5" class="text-center text-muted">Sin resultados</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-lg-8">
                <section class="card" v-loading="loading">
                    <header class="card-header bg-success">
                        <div class="card-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-text"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 9l1 0" /><path d="M9 13l6 0" /><path d="M9 17l6 0" /></svg>
                        </div>
                        <h2 class="card-title">Gestión de Paquetes</h2>
                    </header>
                    <div class="card-body">
                        <div v-if="!clientId" class="alert alert-info">Selecciona un cliente para gestionar sus paquetes.</div>

                        <div v-else>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <div><strong>Ciclo:</strong> {{ summary.cycle_start_at }} → {{ summary.cycle_end_at }}</div>
                                        <div><strong>Saldo total:</strong> {{ summary.remaining_units }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <label>Unidades</label>
                                        <el-select v-model="form.units" placeholder="Seleccionar">
                                            <el-option v-for="opt in packageOptions" :key="opt.units" :label="opt.label" :value="opt.units"></el-option>
                                        </el-select>
                                        <label class="mt-2">Precio</label>
                                        <el-input :value="formatCurrency(form.price)" disabled></el-input>
                                        <div class="mt-2">
                                            <el-checkbox v-model="form.include_sale_notes">Incluir Notas de Venta</el-checkbox>
                                        </div>
                                        <div class="mt-3">
                                            <el-button type="primary" @click="createPackage">Crear paquete</el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Unidades</th>
                                            <th>Precio</th>
                                            <th>Consumidas</th>
                                            <th>Restantes</th>
                                            <th>NV</th>
                                            <th>Estado</th>
                                            <th>Creado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(p, idx) in summary.packages" :key="p.id">
                                            <td>{{ idx + 1 }}</td>
                                            <td>{{ p.units_total }}</td>
                                            <td>{{ formatCurrency(p.price) }}</td>
                                            <td>{{ p.units_consumed }}</td>
                                            <td>{{ p.units_remaining }}</td>
                                            <td>{{ p.include_sale_notes ? 'Sí' : 'No' }}</td>
                                            <td>{{ p.status }}</td>
                                            <td>{{ p.created_at }}</td>
                                            <td class="text-right">
                                                <el-button type="danger" size="mini" :disabled="p.status !== 'active'" @click="cancelPackage(p.id)">Cancelar</el-button>
                                            </td>
                                        </tr>
                                        <tr v-if="summary.packages.length === 0">
                                            <td colspan="8" class="text-center text-muted">Sin paquetes activos en el ciclo</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            resource: 'clients',
            loading: false,
            records: [],
            clientSearchTimer: null,
            searchQuery: '',
            clientId: null,
            summary: {
                cycle_start_at: '',
                cycle_end_at: '',
                remaining_units: 0,
                packages: []
            },
            form: {
                units: 50,
                price: 10,
                include_sale_notes: false
            },
            priceMap: {
                50: 10,
                100: 15,
                200: 30
            }
        }
    },
    created() {
        this.fetchClients();
    },
    beforeDestroy() {
        if (this.clientSearchTimer) {
            clearTimeout(this.clientSearchTimer);
            this.clientSearchTimer = null;
        }
    },
    watch: {
        'form.units'() {
            this.form.price = this.priceMap[this.form.units] || 0;
        }
    },
    computed: {
        packageOptions() {
            return [
                { units: 50, price: this.priceMap[50], label: `50 comprobantes - ${this.formatCurrency(this.priceMap[50])}` },
                { units: 100, price: this.priceMap[100], label: `100 comprobantes - ${this.formatCurrency(this.priceMap[100])}` },
                { units: 200, price: this.priceMap[200], label: `200 comprobantes - ${this.formatCurrency(this.priceMap[200])}` }
            ];
        }
    },
    methods: {
        formatCurrency(value) {
            const num = typeof value === 'number' ? value : parseFloat(value);
            const safe = Number.isFinite(num) ? num : 0;
            return `S/ ${safe.toFixed(2)}`;
        },
        scheduleClientSearch() {
            if (this.clientSearchTimer) {
                clearTimeout(this.clientSearchTimer);
            }
            this.clientSearchTimer = setTimeout(() => {
                this.clientSearchTimer = null;
                this.fetchClients();
            }, 350);
        },
        async fetchClients() {
            this.loading = true;
            try {
                const params = {};
                const s = (this.searchQuery || '').trim();
                if (s) {
                    params.search = s;
                }
                const response = await this.$http.get(`/${this.resource}/records-lite`, { params });
                this.records = response.data.data || [];
            } catch (e) {
                this.$message.error('Error al cargar clientes');
            } finally {
                this.loading = false;
                const clientId = this.getClientIdFromQuery();
                if (clientId && !this.clientId) {
                    await this.selectClient(clientId);
                }
            }
        },
        getClientIdFromQuery() {
            if (!window || !window.location) return null;
            const params = new URLSearchParams(window.location.search || '');
            const raw = params.get('client_id');
            const id = raw ? parseInt(raw, 10) : 0;
            return id > 0 ? id : null;
        },
        async selectClient(id) {
            this.clientId = id;
            await this.fetchSummary();
        },
        async fetchSummary() {
            if (!this.clientId) return;
            this.loading = true;
            try {
                const response = await this.$http.get(`/${this.resource}/document-packages/summary/${this.clientId}`);
                if (response.data && response.data.success) {
                    this.summary = response.data.data;
                } else {
                    this.$message.error((response.data && response.data.message) ? response.data.message : 'No se pudo obtener el resumen');
                }
            } catch (e) {
                this.$message.error('Error al obtener el resumen');
            } finally {
                this.loading = false;
            }
        },
        async createPackage() {
            if (!this.clientId) {
                this.$message.warning('Selecciona un cliente');
                return;
            }
            try {
                const payload = {
                    client_id: this.clientId,
                    units_total: this.form.units,
                    include_sale_notes: this.form.include_sale_notes
                };
                const response = await this.$http.post(`/${this.resource}/document-packages`, payload);
                if (response.data && response.data.success) {
                    this.$message.success(response.data.message || 'Paquete creado');
                    await this.fetchSummary();
                } else {
                    this.$message.error((response.data && response.data.message) ? response.data.message : 'No se pudo crear el paquete');
                }
            } catch (e) {
                this.$message.error('Error al crear el paquete');
            }
        },
        async cancelPackage(packageId) {
            try {
                const response = await this.$http.post(`/${this.resource}/document-packages/cancel/${packageId}`);
                if (response.data && response.data.success) {
                    this.$message.success(response.data.message || 'Paquete cancelado');
                    await this.fetchSummary();
                } else {
                    this.$message.error((response.data && response.data.message) ? response.data.message : 'No se pudo cancelar el paquete');
                }
            } catch (e) {
                this.$message.error('Error al cancelar el paquete');
            }
        }
    }
}
</script>
