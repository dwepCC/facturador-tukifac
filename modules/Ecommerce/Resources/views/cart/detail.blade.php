@extends('ecommerce::layouts.layout_ecommerce_cart.index')

@section('title', 'Carrito de compras')

@section('content')

@php
    $configurationModel = \App\Models\Tenant\Configuration::first();
    $defaultImage = $configurationModel->product_default_image ?? 'imagen-no-disponible.jpg';
    $defaultImagePath = $defaultImage === 'imagen-no-disponible.jpg'
        ? asset('logo/imagen-no-disponible.jpg')
        : asset('storage/defaults/' . $defaultImage);
    $itemsBasePath = asset('storage/uploads/items');
@endphp

<div class="tuki_cart ecom-cart-page" id="app">

    <div class="row tuki_cart__grid">
        <div class="col-lg-8 tuki_cart__col-main">
            <div class="tuki_cart__card cart-table-container tuki_cart__card--lines-only">

                <div v-if="!records.length" class="tuki_cart__empty">
                    <div class="tuki_cart__empty-icon" aria-hidden="true">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3 class="tuki_cart__empty-title">Tu carrito está vacío</h3>
                    <p class="tuki_cart__empty-text">Explora la tienda y agrega productos para verlos aquí.</p>
                    <a href="{{ route('tenant.ecommerce.index') }}" class="btn tuki_cart__btn tuki_cart__btn--primary-solid">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        Ir a la tienda
                    </a>
                </div>

                <div v-else class="tuki_cart__lines-wrap">
                    <div class="tuki_cart__list-head" aria-hidden="true">
                        <span class="tuki_cart__list-head-thumb"></span>
                        <span class="tuki_cart__list-head-product">Producto</span>
                        <span class="tuki_cart__list-head-unit">Precio</span>
                        <span class="tuki_cart__list-head-qty">Cantidad</span>
                        <span class="tuki_cart__list-head-sub">Subtotal</span>
                        <span class="tuki_cart__list-head-action"></span>
                    </div>

                    <ul class="tuki_cart__list list-unstyled">
                        <li v-for="(row, index) in records" :key="'cart-line-' + index + '-' + row.id" class="tuki_cart__line">
                            <div class="tuki_cart__line-media">
                                <a :href="'/ecommerce/item/' + row.id" class="tuki_cart__line-img-wrap">
                                    <img class="tuki_cart__line-img"
                                        :src="(row.image && row.image !== 'imagen-no-disponible.jpg') ? '{{ $itemsBasePath }}' + '/' + row.image : '{{ $defaultImagePath }}'"
                                        :alt="row.description || 'Producto'" loading="lazy" decoding="async" width="96" height="96">
                                </a>
                            </div>
                            <div class="tuki_cart__line-body">
                                <a :href="'/ecommerce/item/' + row.id" class="tuki_cart__line-title">@{{ row.description }}</a>
                                <div class="tuki_cart__line-unit tuki_cart__line-unit--mob">
                                    <span class="tuki_cart__line-label">Precio</span>
                                    <span class="tuki_cart__line-value">@{{ row.currency_type_symbol }} @{{ row.sale_unit_price }}</span>
                                </div>
                            </div>
                            <div class="tuki_cart__line-unit tuki_cart__line-unit--desk">
                                <span class="tuki_cart__line-value">@{{ row.currency_type_symbol }} @{{ row.sale_unit_price }}</span>
                            </div>
                            <div class="tuki_cart__line-qty">
                                <span class="tuki_cart__line-label tuki_cart__line-label--qty">Cantidad</span>
                                <div class="tuki_cart__qty-stepper">
                                    <button type="button" class="tuki_cart__qty-btn" @click.prevent="bumpQty(row, -1)"
                                        :aria-label="'Disminuir cantidad de ' + (row.description || 'producto')">
                                        <i class="fas fa-minus" aria-hidden="true"></i>
                                    </button>
                                    <input class="form-control input_quantity tuki_cart__qty-input" :data-product="row.id"
                                        type="text" inputmode="decimal" autocomplete="off"
                                        :aria-label="'Cantidad de ' + (row.description || 'producto')">
                                    <button type="button" class="tuki_cart__qty-btn" @click.prevent="bumpQty(row, 1)"
                                        :aria-label="'Aumentar cantidad de ' + (row.description || 'producto')">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="tuki_cart__line-sub">
                                <span class="tuki_cart__line-label">Subtotal</span>
                                <span class="tuki_cart__line-subtotal">S/ @{{ row.sub_total }}</span>
                            </div>
                            <div class="tuki_cart__line-remove">
                                <button type="button" @click="deleteItem(row.id, index)" class="tuki_cart__remove"
                                    :title="'Quitar ' + (row.description || '')"
                                    :aria-label="'Quitar ' + (row.description || 'producto') + ' del carrito'">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                </button>
                            </div>
                        </li>
                    </ul>

                    <div class="tuki_cart__list-foot">
                        <a href="{{ route('tenant.ecommerce.index') }}" class="btn tuki_cart__btn tuki_cart__btn--outline">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            Seguir comprando
                        </a>
                        <a href="#" @click.prevent="clearShoppingCart"
                            class="btn tuki_cart__btn tuki_cart__btn--outline tuki_cart__btn--danger-outline btn-clear-cart">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            Vaciar carrito
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 tuki_cart__col-aside">
            <div class="cart-summary tuki_cart__card">
                <h3 class="tuki_cart__card-title tuki_cart__card-title--sm">
                    <i class="fas fa-phone" aria-hidden="true"></i>
                    Datos de contacto y envío
                </h3>

                <form autocomplete="off" action="#" class="tuki_cart__form">
                    <div class="form-group tuki_cart__field-group" :class="{'text-danger': errors.telefono}">
                        <label class="tuki_cart__label" for="cart_phone">Teléfono</label>
                        <div class="tuki_cart__input-wrap">
                            <span class="tuki_cart__input-icon" aria-hidden="true"><i class="fas fa-phone"></i></span>
                            <input id="cart_phone" v-model="form_contact.telephone" type="text" autocomplete="off" class="form-control tuki_cart__field" placeholder="Ej. 999 999 999" name="teléfono">
                        </div>
                        <small class="form-control-feedback" v-if="errors.telefono" v-text="errors.telefono[0]"></small>
                    </div>
                    <div class="form-group tuki_cart__field-group" :class="{'text-danger': errors.address}">
                        <label class="tuki_cart__label" for="cart_address">Dirección de envío</label>
                        <div class="tuki_cart__input-wrap tuki_cart__input-wrap--textarea">
                            <span class="tuki_cart__input-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea id="cart_address" v-model="form_contact.address" class="form-control tuki_cart__field" placeholder="Calle, distrito, referencias…" rows="2" cols="10"></textarea>
                        </div>
                        <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                    </div>
                </form>
            </div>

            <div class="cart-summary tuki_cart__card">
                <h3 class="tuki_cart__card-title tuki_cart__card-title--sm">
                    <i class="fas fa-file-invoice" aria-hidden="true"></i>
                    Tipo de comprobante
                </h3>

            <div class="form-group tuki_cart__field-group" :class="{'text-danger': errors.codigo_tipo_documento}">
                <label class="tuki_cart__label" for="cart_doc_type">Comprobante</label>
                <select id="cart_doc_type" v-model="form_document.codigo_tipo_documento" class="form-control tuki_cart__field tuki_cart__select" @change="optionDocument">
                    <option value="" disabled>Tipo de comprobante</option>
                    <option value="01">Factura</option>
                    <option value="03">Boleta</option>
                    <option value="80">Nota de venta</option>
                </select>
                <small class="form-control-feedback" v-if="errors.codigo_tipo_documento">El campo Comprobante es obligatorio.</small>
            </div>
            <div class="form-group tuki_cart__field-group" :class="{'text-danger': errors.codigo_tipo_documento_identidad}">
                <label class="tuki_cart__label" for="cart_id_type">Tipo de documento de identidad</label>
                <select id="cart_id_type" v-model="typeDocuments" class="form-control tuki_cart__field tuki_cart__select">
                    <option value="" disabled>Seleccionar</option>
                    <option v-for="item in typeDocumentList" :value="item.id" :label="item.name">@{{ item.name }}</option>
                </select>
                <small class="form-control-feedback" v-if="errors.codigo_tipo_documento_identidad" v-text="errors.codigo_tipo_documento_identidad[0]"></small>
            </div>
            <div class="form-group tuki_cart__field-group" :class="{'text-danger': errors.numero_documento}">
                <label class="tuki_cart__label" for="cart_id_number">Número de documento</label>
                <div class="tuki_cart__input-wrap">
                    <span class="tuki_cart__input-icon" aria-hidden="true"><i class="fas fa-id-card"></i></span>
                    <input id="cart_id_number" v-model="numberDocument" :maxlength="maxLength" type="text" class="form-control tuki_cart__field" placeholder="Según tipo elegido" autocomplete="off">
                </div>
                <small class="form-control-feedback" v-if="errors.numero_documento" v-text="errors.numero_documento[0]"></small>
            </div>

            </div>

            <div class="cart-summary tuki_cart__card">
                <h3 class="tuki_cart__card-title tuki_cart__card-title--sm">
                    <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                    Resumen
                </h3>
            <div class="tuki_cart__summary">
                <div v-if="summary.total_exonerated > 0" class="tuki_cart__summary-row">
                    <span class="tuki_cart__summary-key">Operaciones exoneradas</span>
                    <span class="tuki_cart__summary-val">S/ @{{ summary.total_exonerated }}</span>
                </div>
                <div v-if="summary.total_taxed > 0" class="tuki_cart__summary-row">
                    <span class="tuki_cart__summary-key">Operación gravada</span>
                    <span class="tuki_cart__summary-val">S/ @{{ summary.total_taxed }}</span>
                </div>
                <div v-if="summary.total_igv > 0" class="tuki_cart__summary-row">
                    <span class="tuki_cart__summary-key">IGV (18%)</span>
                    <span class="tuki_cart__summary-val">S/ @{{ summary.total_igv }}</span>
                </div>
                <div class="tuki_cart__summary-row tuki_cart__summary-row--total">
                    <span class="tuki_cart__summary-key">Total a pagar</span>
                    <span class="tuki_cart__summary-total">S/ @{{ summary.total }}</span>
                </div>
            </div>

            <div class="checkout-methods text-center tuki_cart__checkout">

                @guest('ecommerce')
                <a href="{{ route('tenant_ecommerce_login') }}" class="btn btn-block btn-sm btn-primary login-link culqi tuki_cart__pay-btn">
                    <i class="fas fa-credit-card" aria-hidden="true"></i>
                    Pagar con tarjeta (Visa)
                </a>
                <a href="{{ route('tenant_ecommerce_login') }}" class="btn btn-block btn-sm btn-primary login-link tuki_cart__pay-btn">
                    <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                    Pagar en efectivo
                </a>
                <a href="{{ route('tenant_ecommerce_login') }}" class="btn btn-block btn-sm login-link tuki_cart__paypal-wrap">
                    <img src="{{ asset('porto-ecommerce/assets/images/btn_buynowCC_LG.gif') }}" alt="Pagar con PayPal" class="tuki_cart__paypal-img" width="145" height="47" loading="lazy">
                </a>

                @elseauth('ecommerce')
                <button type="button" class="btn btn-block btn-sm btn-primary login-link culqi tuki_cart__pay-btn" onclick="execCulqi()">
                    <i class="fas fa-credit-card" aria-hidden="true"></i>
                    Pagar con tarjeta (Visa)
                </button>

                <button type="button" @click="payment_cash.clicked = !payment_cash.clicked" class="btn btn-block btn-sm btn-primary login-link-pay tuki_cart__pay-btn">
                    <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                    Pagar en efectivo
                </button>
                <div v-show="payment_cash.clicked" class="form-group tuki_cart__cash-panel">
                    <div class="input-group mb-0">
                        <div class="input-group-prepend">
                            <span class="input-group-text">S/</span>
                        </div>
                        <input readonly placeholder="0.0" v-model="payment_cash.amount" type="text"
                            onkeypress="return isNumberKey(event)" maxlength="14" class="form-control"
                            aria-label="Monto a pagar en efectivo">
                        <div class="input-group-append">
                            <button type="button" @click="paymentCash" class="btn btn-success tuki_cart__cash-ok">
                                <i class="fas fa-check" aria-hidden="true"></i>
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>


                @if (! empty($configuration->script_paypal))

                    {!! html_entity_decode($configuration->script_paypal) !!}

                @endif


                @endauth

            </div>
        </div>
    </div>
    </div>
</div>

<input type="hidden" id="total_amount" data-total="0.0">

@endsection

@push('scripts')
<!-- script src="https://checkout.culqi.com/js/v3"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@7.31.1/dist/sweetalert2.all.min.js"></script>
<script src="https://momentjs.com/downloads/moment.min.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script -->


<script type="text/javascript">
    var app_cart = new Vue({
        el: '#app',
        data: {
            form_contact: {
                address:   '',
                telephone:   '',
            },
            payment_cash: {
                amount: '',
                clicked: false
            },
            response_search: {},
            text_search: '',
            loading_search: false,
            identity_document_types: [{
                id: '1',
                description: 'DNI'
            }, {
                id: '6',
                description: 'RUC'
            }],
            formIdentity: {
                identity_document_type_id: ''
            },
            records: [],
            records_old: [],
            order_generated: {},
            summary: {
                subtotal: '0.0',
                tax: '0.0',
                total: '0.0'
            },
            aux_totals: {},
            form_document: {},
            user: {},
            typeDocumentSelected: '',
            response_order_total:0,
            errors: {},
            exchange_rate_sale: '',
            typeDocuments: '',
            typeDocumentList: [],
            numberDocument: '',
            phone_whatsapp: {!! json_encode($configuration->phone_whatsapp ) !!},
            all_identity_document_types : [{id: '6', name: 'RUC'}, {id: '0', name: 'DOC'},{id: '4', name: 'CE'},{id: '1', name: 'DNI'}]
        },
        computed: {
            maxLength: function () {

                if (this.typeDocuments === '6') {
                    return 11
                }
                if (this.typeDocuments === '1') {
                    return 8
                }

                return 15
            }
        },
        async mounted() {
          await this.changeExchangeRate(moment().format("YYYY-MM-DD"))

          let exchange_rate_sale = this.exchange_rate_sale
          let contex = this

          $(document).off('change.tukiCartQty').on('change.tukiCartQty', '.input_quantity', function (e) {
            let value = parseFloat($(this).val())
            if (isNaN(value) || value < 1) {
              value = 1
              $(this).val(value)
            }
            let id = $(this).data('product')
            let row = contex.records.find(x => x.id == id)
            if (!row) return

            if(row.currency_type_id === 'USD') {
              row.sub_total = ((parseFloat(row.sale_unit_price) * value) * exchange_rate_sale).toFixed(2)
            } else {
              row.sub_total = (parseFloat(row.sale_unit_price) * value).toFixed(2)
            }

            row.cantidad = value
            contex.calculateSummary()
            contex.persistCartLocalStorage()
            if (typeof jQuery !== 'undefined') {
              jQuery(document).trigger('tukiProductsCartChanged')
            }
          })

          this.records.forEach(function (item) {
            if(item.currency_type_id === 'USD') {
              item.sub_total = (parseFloat(item.sub_total) * exchange_rate_sale).toFixed(2)
              item.exchange_rate_sale = exchange_rate_sale
            }
            item.sale_unit_price = parseFloat(item.sale_unit_price).toFixed(2)
          })

          this.calculateSummary()
          this.$nextTick(function () {
            contex.syncQuantityInputs()
          })
        },
        created() {
            let array = []
            try {
                const raw = localStorage.getItem('products_cart')
                const parsed = raw ? JSON.parse(raw) : []
                array = Array.isArray(parsed) ? parsed : []
            } catch (e) {
                array = []
                try {
                    localStorage.setItem('products_cart', JSON.stringify([]))
                } catch (e2) {}
            }
            this.records = array.map(function (item) {
                let obj = Object.assign({}, item)
                if (obj.cantidad == null || obj.cantidad === '' || isNaN(parseFloat(obj.cantidad))) {
                    obj.cantidad = 1
                } else {
                    obj.cantidad = parseFloat(obj.cantidad)
                }
                if (obj.sub_total != null && obj.sub_total !== '' && !isNaN(parseFloat(obj.sub_total))) {
                    obj.sub_total = parseFloat(obj.sub_total).toFixed(2)
                } else {
                    obj.sub_total = (parseFloat(obj.sale_unit_price) * obj.cantidad).toFixed(2)
                }
                obj.exchange_rate_sale = ''
                return obj
            })
            this.initForm();

        },
        methods: {
            persistCartLocalStorage: function () {
                try {
                    localStorage.setItem('products_cart', JSON.stringify(this.records))
                } catch (e) {}
            },
            syncQuantityInputs: function () {
                this.records.forEach(function (item) {
                    var $el = $('.input_quantity[data-product="' + item.id + '"]')
                    if ($el.length) {
                        $el.val(item.cantidad != null ? item.cantidad : 1)
                    }
                })
            },
            bumpQty: function (row, delta) {
                var $inp = $('.input_quantity[data-product="' + row.id + '"]')
                if (!$inp.length) return
                var v = parseFloat($inp.val())
                if (isNaN(v) || v < 1) {
                    v = parseFloat(row.cantidad) || 1
                }
                v = Math.max(1, v + delta)
                $inp.val(String(v))
                $inp.trigger('change')
            },
            async changeExchangeRate(exchange_rate_date){
                var response = await axios.get(`/exchange_rate/ecommence/${exchange_rate_date}`)
                this.exchange_rate_sale = parseFloat(response.data.sale)
            },
            optionDocument() {
                this.typeDocumentList = []
                this.typeDocuments = null
                // let voucher = [{id: '6', name: 'RUC'}]
                // let ticket = [{id: '0', name: 'DOC'},{id: '4', name: 'CE'},{id: '1', name: 'DNI'}]

                //   if(this.formIdentity.identity_document_type_id === '6') {
                //     this.typeDocumentList = voucher
                //   }else if (this.formIdentity.identity_document_type_id === '1' && this.payment_cash.amount >= 700) {
                //     this.typeDocumentList = [{id: '1', name: 'DNI'}]
                //     this.typeDocuments = ''
                //   } 
                //   else {
                //     this.typeDocumentList = ticket
                //   }

                if(this.form_document.codigo_tipo_documento == '01')
                {
                    this.typeDocumentList = this.getIdentityDocumentTypes(['6'])
                }
                else if (this.form_document.codigo_tipo_documento == '03' && this.payment_cash.amount >= 700) 
                {
                    this.typeDocumentList = this.getIdentityDocumentTypes(['1'])
                }
                else if (this.form_document.codigo_tipo_documento == '80') 
                {
                    this.typeDocumentList = (this.payment_cash.amount >= 700) ? this.getIdentityDocumentTypes(['6', '1']) : this.getIdentityDocumentTypes()
                } 
                else {
                    this.typeDocumentList = this.getIdentityDocumentTypes(['0', '1', '4'])
                }

            },
            getIdentityDocumentTypes(identity_document_types_id = null){

                if(!identity_document_types_id) return this.all_identity_document_types

                return this.all_identity_document_types.filter((item) => {
                    return identity_document_types_id.includes(item.id)
                })

            },
            refreshSetDataCustomer()
            {

                this.form_document.datos_del_cliente_o_receptor.direccion = this.form_contact.address
                this.form_document.datos_del_cliente_o_receptor.telefono = this.form_contact.telephone
                this.form_document.datos_del_cliente_o_receptor.codigo_tipo_documento_identidad = this.typeDocuments
                this.form_document.datos_del_cliente_o_receptor.numero_documento = this.numberDocument
                // this.form_document.datos_del_cliente_o_receptor.identity_document_type_id = this.formIdentity.identity_document_type_id
                this.form_document.datos_del_cliente_o_receptor.identity_document_type_id = this.typeDocuments
                
            },
            async getFormPaymentCash() {
                
                this.refreshSetDataCustomer()

                let precio = Math.round(Number(this.summary.total) * 100).toFixed(2);
                let precio_culqi = Number(this.summary.total)
                return {
                    producto: 'Compras Ecommerce Facturador Pro',
                    precio: precio,
                    precio_culqi: precio_culqi,
                    customer: this.form_document.datos_del_cliente_o_receptor,
                    items: this.records,
                    purchase: await this.getDocument()
                }
            },
            showSwalMessage(title, text, type){

                swal({
                    title: title,
                    text: text,
                    type: type
                })

            },
            async paymentCash() {

                if(!this.form_document.codigo_tipo_documento) {
                    return this.showSwalMessage('Ocurrió un error!', 'El campo tipo de comprobante es obligatorio', 'error')
                }

                // verifica si tiene productos seleccionado
                let product = []
                try {
                    const raw = localStorage.getItem('products_cart')
                    const parsed = raw ? JSON.parse(raw) : []
                    product = Array.isArray(parsed) ? parsed : []
                } catch (e) {
                    product = []
                }

                if (product.length < 1){
                    swal({
                        title: "No se han encontrado productos",
                        text: "Por favor seleccione algún producto de la tienda.",
                        type: "error"
                    })
                    return
                }

                swal({
                    title: "Estamos generando el Pago.",
                    text: `Por favor no cierre esta ventana hasta que el proceso termine.`,
                    focusConfirm: false,
                    onOpen: () => {
                        Swal.showLoading()
                    }
                });

                let url_finally = '{{ route("tenant_ecommerce_payment_cash")}}';
                let response = await axios.post(url_finally, await this.getFormPaymentCash(), this.getHeaderConfig()).then(response => {
                        if (response.data.success) {
                            this.saveContactDataUser()
                            this.clearShoppingCart()
                            this.response_order_total = response.data.order.total
                            swal({
                                title: "Gracias por su pago!",
                                text: "En breve le enviaremos un correo electronico con los detalles de su compra.",
                                type: "success"
                            }).then((x) => {
                              app_cart.order_generated = order
                                //askedDocument(response.data.order);
                            })
                        }
                    }).catch(error => {
                        swal("Pago No realizado", 'Sucedio algo inesperado.', "error");
                        if (error.response.status === 422) {
                          this.errors = error.response.data;
                        } else {
                          console.log(error);
                        }
                    });

            },
            redirectHome() {
                window.location = "{{ route('tenant.ecommerce.index') }}";
            },
            getHeaderConfig() {
                let token = this.user.api_token
                let axiosConfig = {
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${token}`
                    }
                };
                return axiosConfig;
            },
            async getDocument() {
                this.form_document.items = await this.getItemsDocument()
                this.form_document.totales = await this.getTotales()

                // if (this.formIdentity.identity_document_type_id === '6') {
                //     this.form_document.serie_documento = 'F001'
                //     this.form_document.codigo_tipo_documento = '01'
                // }
                // if (this.formIdentity.identity_document_type_id === '1') {
                //     this.form_document.serie_documento = 'B001'
                //     this.form_document.codigo_tipo_documento = '03'
                // }
                
                if (this.form_document.codigo_tipo_documento == '01')
                {
                    this.form_document.serie_documento = 'F001'
                }else if (this.form_document.codigo_tipo_documento == '03') 
                {
                    this.form_document.serie_documento = 'B001'
                }else
                {
                    this.form_document.serie_documento = null
                }


                return this.form_document
            },
            async getTotales() {

                let totals = await {
                    "total_exportacion": 0.00,
                    "total_operaciones_gravadas": this.aux_totals.total_taxed,
                    "total_operaciones_inafectas": 0.00,
                    "total_operaciones_exoneradas": this.aux_totals.total_exonerated,
                    "total_operaciones_gratuitas": 0.00,
                    "total_igv": this.aux_totals.total_igv,
                    "total_impuestos": this.aux_totals.total_igv,
                    "total_valor": this.aux_totals.total_value,
                    "total_venta": this.aux_totals.total
                }

                return totals
            },
            async getItemsDocument() {

                let rec = await this.records.map((item) => {

                    let sale_unit_price = 0
                    let total_exonerated = 0
                    let total_igv = 0
                    let total_val = 0
                    let total = 0
                    let percentage_igv = 18
                    let nombre_producto_pdf = item.promotion_id ? item.description : null

                    if (item.sale_affectation_igv_type_id === '10') {

                        if(item.currency_type_id === 'USD') {
                            sale_unit_price = (parseFloat(item.sale_unit_price) * this.exchange_rate_sale).toFixed(2)
                        } else {
                            sale_unit_price = item.sale_unit_price
                        }

                        unit_value = sale_unit_price / (1 + percentage_igv / 100)
                        total_igv = item.cantidad * parseFloat(sale_unit_price - unit_value)
                        total = (item.cantidad * sale_unit_price)
                        //sale_unit_price = parseFloat(item.sale_unit_price)
                        total_val = (unit_value * item.cantidad)

                        return {
                            "codigo_interno": (item.internal_id) ? item.internal_id:"",
                            "descripcion": item.description,
                            "codigo_producto_sunat": "",
                            "unidad_de_medida": item.unit_type_id,
                            "cantidad": item.cantidad,
                            "valor_unitario": unit_value,
                            "codigo_tipo_precio": "01",
                            "precio_unitario": sale_unit_price,
                            "codigo_tipo_afectacion_igv": "10",
                            "total_base_igv": total_val,
                            "porcentaje_igv": percentage_igv,
                            "total_igv": total_igv,
                            "total_impuestos": total_igv,
                            "total_valor_item": total_val,
                            "total_item": total,
                            "actualizar_descripcion": false,
                            "nombre_producto_pdf": nombre_producto_pdf
                        }

                    }

                    if (item.sale_affectation_igv_type_id === '20') {

                        if(item.currency_type_id === 'USD') {
                            sale_unit_price = (parseFloat(item.sale_unit_price) * this.exchange_rate_sale).toFixed(2)
                        } else {
                            sale_unit_price = item.sale_unit_price
                        }

                        unit_value = parseFloat(sale_unit_price)
                        total_igv = 0
                        total = (parseFloat(item.cantidad) * parseFloat(sale_unit_price))
                        //sale_unit_price = parseFloat(item.sale_unit_price)
                        total_val = (parseFloat(unit_value) * parseFloat(item.cantidad))

                        return {
                            "codigo_interno": (item.internal_id) ? item.internal_id:"",
                            "descripcion": item.description,
                            "codigo_producto_sunat": "",
                            "unidad_de_medida": item.unit_type_id,
                            "cantidad": item.cantidad,
                            "valor_unitario": unit_value,
                            "codigo_tipo_precio": "01",
                            "precio_unitario": sale_unit_price,
                            "codigo_tipo_afectacion_igv": "20",
                            "total_base_igv": total_val,
                            "porcentaje_igv": percentage_igv,
                            "total_igv": 0,
                            "total_impuestos": 0,
                            "total_valor_item": total_val,
                            "total_item": total,
                            "actualizar_descripcion": false,
                            "nombre_producto_pdf": nombre_producto_pdf
                        }

                    }

                })

                return rec
            },
            initForm() {
              this.errors = {}
                this.user = JSON.parse('{!! json_encode( Auth::guard("ecommerce")->user() ) !!}')
                if(!this.user){
                    return false
                }

                this.form_document = {
                    "acciones": {
                        "enviar_email": true,
                        "formato_pdf": "a4"
                    },
                    "serie_documento": "",
                    "numero_documento": "#",
                    "fecha_de_emision": moment().format('YYYY-MM-DD'),
                    "hora_de_emision": moment().format('HH:mm:ss'),
                    "codigo_tipo_operacion": "0101",
                    "codigo_tipo_documento": "03",
                    "codigo_tipo_moneda": "PEN",
                    "fecha_de_vencimiento": moment().format('YYYY-MM-DD'),
                    "datos_del_cliente_o_receptor": {
                        "codigo_tipo_documento_identidad": "0",
                        "numero_documento": "0",
                        "apellidos_y_nombres_o_razon_social": this.user.name,
                        "codigo_pais": "PE",
                        "ubigeo": "150101",
                        "direccion": this.user.address,
                        "correo_electronico": this.user.email,
                        "telefono": this.user.telephone
                    },
                    "totales": {},
                    "items": [],
                }


                // this.formIdentity = {
                //     identity_document_type_id: ''
                // }

                this.form_contact.address =  this.user.address
                this.form_contact.telephone =  this.user.telephone

                this.optionDocument()
            },
            deleteItem(id, index) {
                this.records.splice(index, 1)
                let array = []
                try {
                    const raw = localStorage.getItem('products_cart')
                    const parsed = raw ? JSON.parse(raw) : []
                    array = Array.isArray(parsed) ? parsed : []
                } catch (e) {
                    array = []
                }
                let indexFound = array.findIndex(x => x.id == id)
                if (indexFound >= 0) {
                    array.splice(indexFound, 1)
                }
                localStorage.setItem('products_cart', JSON.stringify(array))
                this.calculateSummary()
                if (typeof jQuery !== 'undefined') {
                    jQuery(document).trigger('tukiProductsCartChanged')
                }
            },
            clearShoppingCart() {
              this.errors = {}
                this.records_old = this.records
                this.records = []
                localStorage.setItem('products_cart', JSON.stringify([]))
                // this.calculateSummary()

                this.summary = {
                    subtotal: '0.0',
                    tax: '0.0',
                    total: '0.00',
                    total_taxed: '0.0',
                    total_value: '0.0',
                    total_exonerated: '0.0',
                    total_igv: '0.0'
                }
                this.payment_cash.amount = '0.00'
                location.reload()
            },
            calculateSummary() {

                //let subtotal = 0.00
                let total_taxed = 0
                let total_value = 0
                let total_exonerated = 0
                let total_igv = 0
                let total = 0

                this.records.forEach(function (item) {

                    //subtotal += parseFloat(item.sub_total)

                    let unit_price = item.sub_total
                    let unit_value = unit_price
                    let percentage_igv = 18

                    if (item.sale_affectation_igv_type_id === '10') {
                        unit_value = item.sub_total / (1 + percentage_igv / 100)
                        total_taxed += parseFloat(unit_value)
                        total_igv += parseFloat(unit_price - unit_value)
                    }
                    if (item.sale_affectation_igv_type_id === '20') {
                        total_exonerated += parseFloat(unit_value)
                    }

                    total_value = total_taxed + total_exonerated
                    total += parseFloat(unit_price)
                })

                // console.log(total_taxed, total_exonerated, total_igv)

                this.summary.total_taxed = total_taxed.toFixed(2)
                this.summary.total_exonerated = total_exonerated.toFixed(2)
                this.summary.total_igv = total_igv.toFixed(2)
                this.summary.total_value = total_value.toFixed(2)
                this.summary.total = total.toFixed(2)
                this.aux_totals = this.summary
                // console.log(this.summary)


                $("#total_amount").data('total', this.summary.total);

                // this.formIdentity.identity_document_type_id = ''
                this.form_document.codigo_tipo_documento = null
                this.optionDocument()

                this.payment_cash.amount = this.summary.total;

                // let x =
                // console.log(x)

                // let subtotal = 0.00
                // this.records.forEach(function (item) {
                //     //console.log(item)
                //     subtotal += parseFloat(item.sub_total)
                // })

                // this.summary.subtotal = subtotal.toFixed(2)
                // let tax = (subtotal * 0.18)
                // this.summary.tax = tax.toFixed(2)
                // this.summary.total = (subtotal + tax).toFixed(2)
                // $("#total_amount").data('total', this.summary.total);

                // this.payment_cash.amount = this.summary.total
            },
            saveContactDataUser()
            {
                let url_finally = '{{ route("tenant_ecommerce_user_data")}}';
                axios.post(url_finally, this.form_contact, this.getHeaderConfig())
                    .then(response => {
                       console.log(response.data)
                    })
                    .catch(error => {

                    });
            },
            clickSendWhatsapp(order_id) {

                window.open(`https://wa.me/51${this.phone_whatsapp}?text=Se ha generado un nuevo pedido con código nro. ${order_id}`, '_blank');

            }
        }
    })

</script>

<script>
    Culqi.publicKey = {!! json_encode($configuration->token_public_culqui ) !!};
    if(!Culqi.publicKey)
    {
      $('.culqi').hide()
/*
        swal({
            title: "Culqi configuración",
            text: "El pago con visa aun no esta disponible. Intente con efectivo.",
            type: "error",
            position: 'top-end',
            icon: 'warning',
        })
*/
    }
    Culqi.options({
        installments: true
    });

    async function askedDocument(order) {
        app_cart.order_generated = order
        $('#modal_ask_document').modal('show')
    }

    async function execCulqi() {

       console.log( 'errores', app_cart.errors)

       //app_cart.errors = 'demo'

    //   console.log( 'errores22', app_cart.errors)


        let precio = Math.round((Number($("#total_amount").data('total')) * 100).toFixed(2));
        if (precio > 0) {
            Culqi.settings({
                title: "Productos Ecommerce",
                currency: 'PEN',
                description: 'Compras Ecommerce Facturador Pro',
                amount: precio
            });
            Culqi.open();
        }
    }


    async function culqi() {
        if (Culqi.token) {

            swal({
                title: "Estamos hablando con su banco",
                text: `Por favor no cierre esta ventana hasta que el proceso termine.`,
                focusConfirm: false,
                onOpen: () => {
                    Swal.showLoading()
                }
            });

            let precio = Math.round((Number($("#total_amount").data('total')).toFixed(2) * 100));
            let precio_culqi = Number($("#total_amount").data('total')).toFixed(2);

            var url = "/culqi";
            var token = Culqi.token.id;
            var email = Culqi.token.email;
            var installments = Culqi.token.metadata.installments;

            const formpayment = await app_cart.getFormPaymentCash()

            var data = {
                producto: 'Compras Ecommerce Facturador Pro',
                precio: precio,
                precio_culqi: precio_culqi,
                token: token,
                email: email,
                installments: installments,
                customer: JSON.stringify(formpayment.customer),
                items: JSON.stringify(getItems()),
                purchase: JSON.stringify(formpayment.purchase),

            }

            $.ajax({
              url: "{{route('tenant_ecommerce_culqui')}}",
              method: 'post',
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              data: data,
              dataType: 'JSON',
              success: function (data) {
                if (data.success == true) {
                  app_cart.saveContactDataUser();
                  app_cart.clearShoppingCart();
                  swal({
                    title: "Gracias por su pago!",
                    text: "En breve le enviaremos un correo electronico con los detalles de su compra.",
                    type: "success"
                  }).then((x) => {
                    askedDocument(data.order);
                    //window.location = "{{ route('tenant.ecommerce.index') }}";
                  })
                } else {
                  const message = data.message
                  swal("Pago No realizado", message, "error");
                }
              },
              error: function (error_data) {
                console.log(error_data)
                if (error_data.status === 422) {
                    app_cart.errors = JSON.parse( error_data.responseText);
                }
                swal("Pago No realizado", 'Faltan completar campos', "error");
              }
            });

        } else {
            console.log(Culqi.error);
            swal("Pago No realizado", Culqi.error.user_message, "error");
        }
    };

    function getCustomer() {
        let user = JSON.parse('{!! json_encode( Auth::guard("ecommerce")->user() ) !!}')
        return {
            "codigo_tipo_documento_identidad": "0",
            "numero_documento": "0",
            "apellidos_y_nombres_o_razon_social": user.name,
            "codigo_pais": "PE",
            "ubigeo": "150101",
            "direccion": app_cart.user.address,
            "correo_electronico": user.email,
            "telefono": app_cart.user.telephone
        }
    }

    function getItems() {
        return app_cart.records
    }

    function isNumberKey(evt) {
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode != 46 && charCode > 31 &&
            (charCode < 48 || charCode > 57))
            return false;
        return true;
    }

</script>

@endpush
