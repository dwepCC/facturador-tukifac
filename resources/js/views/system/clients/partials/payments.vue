<template>
    <el-dialog width="70%" :title="title" :visible="showDialog" @close="close" @open="getData">
        <div class="form-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="small font-weight-bold d-block mb-1">Fin de ciclo de facturación (central)</label>
                    <p class="text-muted small mb-2">
                        Se aplica al guardar un pago pendiente. Si cambia respecto al valor anterior, se actualizan las órdenes
                        de pago del sistema pendientes con la misma fecha de vencimiento y los pagos vinculados a esas órdenes.
                    </p>
                    <el-date-picker
                        v-model="clientEndingCycle"
                        type="date"
                        format="dd/MM/yyyy"
                        value-format="yyyy-MM-dd"
                        placeholder="Sin fecha"
                        clearable
                        class="w-100"
                        style="max-width: 280px"
                    />
                </div>
            </div>

            <div class="row">
                <div class="col-md-12" v-if="records.length > 0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Fecha de pago</th>
                                <th>Método de pago</th>
                                <th>Tarjeta</th>
                                <th>Referencia</th>
                                <th>Monto</th>
                                <th>Pagar</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in records" :key="row.id ? 'p' + row.id : 'n' + index">
                                <template v-if="row.state">
                                    <td>{{ row.date_of_payment }}</td>
                                    <td>{{ row.payment_method_type_description }}</td>
                                    <td v-if="row.card_brand">{{ row.card_brand.description }}</td>
                                    <td v-else>-</td>
                                    <td>{{ row.reference }}</td>
                                    <td>S/ {{ row.payment }}</td>
                                    <td>Pagado</td>
                                    <td class="series-table-actions text-right">—</td>
                                </template>
                                <template v-else>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.date_of_payment}">
                                            <el-date-picker
                                                v-model="row.date_of_payment_iso"
                                                type="date"
                                                :clearable="false"
                                                format="dd/MM/yyyy"
                                                value-format="yyyy-MM-dd"
                                            />
                                            <small class="form-control-feedback" v-if="row.errors.date_of_payment" v-text="row.errors.date_of_payment[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.payment_method_type_id}">
                                            <el-select v-model="row.payment_method_type_id" @change="changePaymentMethodType(row)">
                                                <el-option v-for="option in payment_method_types" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="row.errors.payment_method_type_id" v-text="row.errors.payment_method_type_id[0]"></small>
                                        </div>
                                    </td>
                                    <td v-if="rowNeedsCard(row)">
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.card_brand_id}">
                                            <el-select v-model="row.card_brand_id">
                                                <el-option v-for="option in card_brands" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="row.errors.card_brand_id" v-text="row.errors.card_brand_id[0]"></small>
                                        </div>
                                    </td>
                                    <td v-else></td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.reference}">
                                            <el-input v-model="row.reference"></el-input>
                                            <small class="form-control-feedback" v-if="row.errors.reference" v-text="row.errors.reference[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-group mb-0" :class="{'has-danger': row.errors.payment}">
                                            <el-input v-model="row.payment">
                                                <template slot="prepend">S/ </template>
                                            </el-input>
                                            <small class="form-control-feedback" v-if="row.errors.payment" v-text="row.errors.payment[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickCancelPayment(row.id)">
                                            Pagar
                                        </button>
                                    </td>
                                    <td class="series-table-actions text-right">
                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickSubmit(index)" title="Guardar">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        <button v-if="!row.id" type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickCancel(index)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <button v-else type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">
                                            Eliminar
                                        </button>
                                    </td>
                                </template>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="6" class="text-right">TOTAL PAGADO</td>
                                <td class="text-right">S/ {{ client.total_paid }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-right">TOTAL A PAGAR</td>
                                <td class="text-right">S/ {{ client.total }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-right">PENDIENTE DE PAGO</td>
                                <td class="text-right">S/ {{ client.total_difference }}</td>
                                <td></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="col-md-12 text-center pt-2">
                    <el-button type="primary" icon="el-icon-plus" @click="clickAddRow">Programar pago</el-button>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script>
    export default {
        props: ['showDialog', 'clientId'],

        data() {
            return {
                title: null,
                resource: 'client_payments',
                records: [],
                payment_method_types: [],
                card_brands: [],
                client: {},
                clientEndingCycle: null,
            }
        },
        async created() {
            await this.initForm();
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.payment_method_types = response.data.payment_method_types;
                    this.card_brands = response.data.card_brands;
                });
        },
        methods: {
            rowNeedsCard(row) {
                if (!row.payment_method_type_id) {
                    return false;
                }
                const pm = _.find(this.payment_method_types, { id: row.payment_method_type_id });
                return !!(pm && pm.has_card);
            },
            changePaymentMethodType(row) {
                if (!this.rowNeedsCard(row)) {
                    row.card_brand_id = null;
                }
            },
            initForm() {
                this.records = [];
            },
            normalizeRecord(row) {
                return Object.assign({ errors: {} }, row);
            },
            async getData() {
                this.initForm();
                await this.$http.get(`/${this.resource}/client/${this.clientId}`)
                    .then(response => {
                        this.client = response.data;
                        this.clientEndingCycle = response.data.ending_billing_cycle || null;
                        this.title = 'Programación de pagos del cliente: ' + this.client.name;
                    });
                await this.$http.get(`/${this.resource}/records/${this.clientId}`)
                    .then(response => {
                        const rows = response.data.data || [];
                        this.records = rows.map(r => this.normalizeRecord(r));
                    });
            },
            async clickCancelPayment(client_payment_id) {
                await this.$http.get(`/${this.resource}/cancel_payment/${client_payment_id}`)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message);
                            this.getData();
                        } else {
                            this.$message.error(response.data.message);
                        }
                    });
            },
            clickAddRow() {
                this.records.push(this.normalizeRecord({
                    id: null,
                    date_of_payment_iso: moment().format('YYYY-MM-DD'),
                    payment_method_type_id: null,
                    card_brand_id: null,
                    reference: null,
                    payment: this.client.pricing,
                    state: false,
                }));
            },
            clickCancel(index) {
                this.records.splice(index, 1);
            },
            clickSubmit(index) {
                const row = this.records[index];
                if (this.rowNeedsCard(row) && (row.card_brand_id == null || row.card_brand_id === '')) {
                    this.$message.error('Elija una tarjeta.');
                    return;
                }
                const form = {
                    id: row.id,
                    client_id: this.clientId,
                    date_of_payment: row.date_of_payment_iso,
                    payment_method_type_id: row.payment_method_type_id,
                    card_brand_id: row.card_brand_id,
                    reference: row.reference,
                    payment: row.payment,
                    ending_billing_cycle: this.clientEndingCycle || null,
                };
                this.$http.post(`/${this.resource}`, form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message);
                            this.getData();
                        } else {
                            this.$message.error(response.data.message);
                        }
                    })
                    .catch(error => {
                        if (error.response && error.response.status === 422) {
                            this.$set(this.records[index], 'errors', error.response.data.errors || {});
                            const msg = error.response.data.message || 'Datos no válidos';
                            this.$message.error(msg);
                        } else {
                            console.log(error);
                            this.$message.error('Error al guardar el pago');
                        }
                    });
            },
            close() {
                this.$emit('update:showDialog', false);
            },
            clickDelete(id) {
                this.$confirm('¿Desea eliminar el registro?', 'Eliminar', {
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    type: 'warning',
                }).then(() => {
                    this.$http.delete(`/${this.resource}/${id}`)
                        .then(res => {
                            if (res.data.success) {
                                this.$message.success(res.data.message);
                                this.getData();
                            } else {
                                this.$message.error(res.data.message);
                            }
                        })
                        .catch(err => {
                            const msg = _.get(err, 'response.data.message', 'Error al intentar eliminar');
                            this.$message.error(msg);
                        });
                }).catch(() => {});
            },
        },
    };
</script>
