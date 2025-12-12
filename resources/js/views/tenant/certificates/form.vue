<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" class="certificate-form">
        <div class="form-body">
            <div class="row">
                <div class="col-md-7">
                    <div class="form-group" :class="{'has-danger': errors.password}">
                        <label class="control-label">Contraseña</label>
                        <el-input v-model="form.password"></el-input>
                        <small class="form-control-feedback" v-if="errors.password" v-text="errors.password[0]"></small>
                    </div>
                </div>
                <div class="col-md-5"> 
                    <div class="form-group" :class="{'has-danger': errors.certificate}">
                        <label class="control-label">Certificado pfx</label>
                        <el-upload
                                   ref="upload"
                                   :headers="headers"
                                   :data="{'password': form.password, 'certificate_due': form.certificate_due}"
                                   action="/certificates/uploads"
                                   :show-file-list="false"
                                   :auto-upload="false"
                                   :multiple="false"
                                   :on-error="errorUpload"
                                   :on-success="successUpload">
                            <el-button slot="trigger" type="primary">Selecciona un archivo</el-button>
                        </el-upload>
                        <small class="form-control-feedback" v-if="errors.certificate" v-text="errors.certificate[0]"></small>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="form-group" :class="{'has-danger': errors.certificate_due}">
                        <label class="control-label">Fecha de vencimiento</label>
                        <el-date-picker v-model="form.certificate_due" type="date" placeholder="Selecciona fecha" format="yyyy-MM-dd" value-format="yyyy-MM-dd"></el-date-picker>
                        <small class="form-control-feedback" v-if="errors.certificate_due" v-text="errors.certificate_due[0]"></small>
                    </div>
                </div>
                <div class="col-md-12 text-right">
                    <el-button @click.prevent="close()">Cancelar</el-button>
                    <el-button type="primary" @click.prevent="clickUpload" :loading="loading_submit">Aceptar</el-button>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script>

    import {EventBus} from '../../../helpers/bus'

    export default {
        props: ['showDialog', 'recordId'],
        data() {
            return {
                loading_submit: false,
                headers: headers_token,
                titleDialog: null,
                resource: 'items',
                errors: {},
                form: {}
            }
        },
        created() {
            this.initForm()
        },
        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    certificate: null,
                    password: null,
                    certificate_due: null,
                }
            },
            create() {
                this.titleDialog = 'Generar Certificado PEM'
            },
//            submit() {
//                this.loading_submit = true
//                this.$http.post(`/${this.resource}`, this.form)
//                    .then(response => {
//                        if (response.data.success) {
//                            this.$message.success(response.data.message)
//                            this.$eventHub.$emit('reloadData')
//                            this.close()
//                        } else {
//                            this.$message.error(response.data.message)
//                        }
//                    })
//                    .catch(error => {
//                        if (error.response.status === 422) {
//                            this.errors = error.response.data.errors
//                        } else {
//                            console.log(error)
//                        }
//                    })
//                    .then(() => {
//                        this.loading_submit = false
//                    })
//            },
            clickUpload() {
                this.$refs.upload.submit();
            },
            close() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            successUpload(response, file, fileList) {
                if (response.success) {

                    this.$message.success(response.message)
                    this.$eventHub.$emit('reloadData')
                    this.$eventHub.$emit('reloadDataCompany')
                    this.close()
                    
                } else {
                    this.$message({message:response.message, type: 'error'})
                }
            },
            errorUpload(response) {
                console.log(response)
            }
        }
    }
</script>
