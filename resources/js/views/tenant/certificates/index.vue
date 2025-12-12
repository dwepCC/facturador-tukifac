<template>
    <div class="card card-config">
        <div class="card-header bg-info">
            <h3 class="my-0">Certificado</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive" v-if="record">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Vence</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{ record }}</td>
                        <td>
                            <el-date-picker v-model="certificate_due_edit" type="date" placeholder="Selecciona fecha" format="yyyy-MM-dd" value-format="yyyy-MM-dd"></el-date-picker>
                        </td>
                        <td class="text-right">
                            <el-button type="primary" size="mini" @click.prevent="saveDue" :disabled="saving_due">Guardar</el-button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger ms-2"
                                    @click.prevent="clickDelete">Eliminar</button>
                        </td> 
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="row" v-else>
                <div class="col-md-12 text-center">
                    <el-button  :disabled="!config_system_env" type="primary" icon="el-icon-plus" @click="clickCreate">Subir</el-button>
                </div>
            </div>
        </div>
        <certificates-form :showDialog.sync="showDialog"
                           :recordId="recordId"></certificates-form>
    </div>
</template>

<script>

    import CertificatesForm from './form.vue'
    import {deletable} from '../../../mixins/deletable'

    export default {
        mixins: [deletable],
        components: {CertificatesForm},
        data() {
            return {
                showDialog: false,
                resource: 'certificates',
                recordId: null,
                record: {},
                certificate_due: null,
                certificate_due_edit: null,
                saving_due: false,
                config_system_env: false
            }
        },
        created() {
            this.$eventHub.$on('reloadData', () => {
                this.getData()
            })
            this.getData()
        },
        methods: {
            getData() {
                this.$http.get(`/${this.resource}/record`)
                    .then(response => {
                        this.record = response.data.certificate
                        this.certificate_due = response.data.certificate_due
                        this.certificate_due_edit = response.data.certificate_due
                        this.config_system_env = response.data.config_system_env
                    })
            },
            saveDue() {
                this.saving_due = true
                this.$http.post('/certificates/save-due', { certificate_due: this.certificate_due_edit })
                    .then(response => {
                        if (response.data && response.data.success) {
                            this.$message.success(response.data.message || 'Guardado')
                            this.certificate_due = this.certificate_due_edit
                        } else {
                            this.$message.error(response.data.message || 'Error')
                        }
                    })
                    .catch(() => this.$message.error('Error'))
                    .then(() => { this.saving_due = false })
            },
            clickCreate() {
                this.showDialog = true
            },
            clickDelete() {
                this.destroy(`/${this.resource}`).then(() => {

                    this.$eventHub.$emit('reloadData')
                    this.$eventHub.$emit('reloadDataCompany')
                    
                })
            }
        }
    }
</script>
