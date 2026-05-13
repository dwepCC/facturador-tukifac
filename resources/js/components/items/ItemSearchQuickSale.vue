<template>
    <div class="m-0 p-0 content-products">
        <el-select
            ref="selectBarcode"
            v-model="item_id"
            :loading="loading_search"
            :remote-method="searchRemoteItems"
            remote
            filterable
            placeholder="Buscar/Agregar producto"
            @change="changeItem"
            @keyup.native="changeInputEnter"
            @keyup.enter.native="searchRemoteItemsWithEnter"
            :disabled="!is_mounted"
            popper-class="list-result"
            :reserve-keyword="false">
            <el-option
                class="item-result"
                v-for="row in items"
                :key="row.id"
                :label="itemOptionDescriptionView(row)"
                :value="row.id">
                <div class="row">
                    <div class="col-1 p-1 align-self-center">
                        <img
                            class="custom-image"
                            :src="row.image_url"
                            :alt="row.description"
                        >
                    </div>
                    <div class="col full-description align-self-center">
                            {{ itemOptionDescriptionView(row) }}<br>
                            <b class="custom-price pt-1 pb-1 text-primary">{{ row.currency_type_symbol }} {{ itemSetSaleUnitPrice(row) }}</b>
                    </div>
                    <div class="col-4 text-right">
                        <b :class="{'text-danger': row.stock <= 0, 'text-success': row.stock > 0}" class="py-1 mx-3">
                                <i class="fas fa-cube"></i> {{ parseStock(row.stock) }}
                            </b>
                        <div class="d-flex justify-content-end">
                            <button v-if="showDetailButton" type="button" class="el-button el-button--default el-button--mini m-1 btn-warning" @click.stop.prevent="clickDetail(row.id)">
                                <span>Ver Detalle</span>
                            </button>
                            <button type="button" class="el-button el-button--default el-button--mini m-1 btn-primary" @click.stop.prevent="clickStock(row)">
                                <span>Ver stock</span>
                            </button>
                        </div>
                    </div>
                </div>
            </el-option>
        </el-select>

        <div class="w-100 pl-0 pt-2">
            <el-checkbox v-model="searchOnEnter" @change="changeSearchOnEnter" >Buscar solo al presionar Enter</el-checkbox>
            <el-checkbox ref="searchItemCheckbox" v-model="search_item_by_barcode" @change="focusInputSearch(); saveSearchByBarcodeSetting()">Buscar por código de barras</el-checkbox>
        </div>
        

        <warehouses-stock
            :showDialog.sync="showDialogStock"
            :warehouses="warehouses"
            :itemName="itemName">
        </warehouses-stock>

        <!-- <item-detail-form
            :recordId="dialogItemId"
            :showDialog.sync="showDialogItem"
            :onlyShowAllDetails="showDetailButton"
        >
        </item-detail-form> -->

    </div>
</template>

<style>
.list-result {
    margin: 0px !important;
    margin-top: 10px !important;
    margin-bottom: 10px;
}


</style>

<script>

    import axios from 'axios';
    import WarehousesStock from './partials/WarehousesStock.vue'
    import { ItemOptionDescription } from '@helpers/modal_item'
    import ItemDetailForm from '@views/items/form.vue'

    /** Retraso entre la última tecla y la petición al buscador remoto (reduce tormenta de requests al servidor). */
    const SEARCH_REMOTE_DEBOUNCE_MS = 350;

    export default {
        props: {
            resource: {
                type: String,
                required: true,
            },
            showDetailButton: {
                type: Boolean,
                required: false,
                default: false,
            },
            selectedOptionPrice: {
                type: Number|String,
                required: false,
                default: false,
            },
            configuration: {
                type: Object,
                required: true,
                default: false,
            }
        },
        components: {
            WarehousesStock,
            // ItemDetailForm
        },
        data() {
            return {
                item_id: null,
                loading_search: false,
                all_items: [],
                items: [],
                is_mounted: false,
                showDialogStock: false,
                warehouses: [],
                showDialogItem: false,
                itemName: null,
                dialogItemId: null,
                searchOnEnter: false,
                search_item_by_barcode:false,
                inputEnter: '',
                /** CancelToken de la petición GET /search-items en curso (evita competir con búsquedas obsoletas). */
                searchItemsCancelSource: null,
                /** Contador para no apagar `loading_search` si una respuesta vieja llega tarde. */
                _searchRequestSeq: 0,
            }
        },
        async created()
        {
            this.debouncedFetchSearchRemote = _.debounce((query) => {
                this.fetchSearchRemoteItems(query);
            }, SEARCH_REMOTE_DEBOUNCE_MS);
            await this.initialItems()
        },
        mounted()
        {
            this.is_mounted = true

            const storedSearchByBarcode = localStorage.getItem('search_item_by_barcode');
            if (storedSearchByBarcode !== null) {
                this.search_item_by_barcode = JSON.parse(storedSearchByBarcode);
            }
        },
        beforeDestroy() {
            if (this.debouncedFetchSearchRemote && typeof this.debouncedFetchSearchRemote.cancel === 'function') {
                this.debouncedFetchSearchRemote.cancel();
            }
            if (this.searchItemsCancelSource) {
                this.searchItemsCancelSource.cancel('component-destroyed');
                this.searchItemsCancelSource = null;
            }
        },
        methods:
        {
            focusInputSearch() {
                if (this.search_item_by_barcode) {
                    this.$refs.selectBarcode.focus()
                }
            },
            saveSearchByBarcodeSetting() {
                localStorage.setItem('search_item_by_barcode', JSON.stringify(this.search_item_by_barcode));
            },
            blurSelect() {
                this.search_item_by_barcode = false;
            },
            parseStock(stock)
            {
                return parseFloat(stock)
            },
            cleanValue()
            {
                this.item_id = null
            },
            clickDetail(id)
            {
                // this.dialogItemId = id
                // this.showDialogItem = true
                window.open(`/items/show-item-detail/${id}`)

            },
            clickStock(row)
            {
                this.warehouses = row.warehouses
                this.itemName = row.full_description
                this.showDialogStock = true
            },
            changeItem()
            {
                const item = { ..._.find(this.items, { id : this.item_id}) }
                this.$emit('changeItem', item)
                if(this.search_item_by_barcode){
                    this.items= [];
                    setTimeout(() => {
                        this.$refs.selectBarcode.$el.getElementsByTagName('input')[0].focus();
                    }, 200);
                }
                if(this.searchOnEnter) {
                    this.items= [];
                    this.$refs.selectBarcode.$el.getElementsByTagName('input')
                    setTimeout(() => {
                        this.$refs.selectBarcode.$el.getElementsByTagName('input')[0].blur()
                        this.$refs.selectBarcode.blur();
                    }, 200);
                    setTimeout(() => {
                        this.$refs.selectBarcode.$el.getElementsByTagName('input')[0].focus();
                    }, 200);

                }
            },
            async initialItems()
            {
                await this.$http.get(`/${this.resource}/table/items`).then((response) => {
                    this.all_items = response.data
                    this.filterItems()
                })
            },
            itemOptionDescriptionView(row)
            {
                return ItemOptionDescription(row)
            },
            /**
             * Element UI `remote-method`: se dispara en cada cambio de query.
             * Aquí solo encolamos con debounce; la petición real va en {@see fetchSearchRemoteItems}.
             */
            searchRemoteItems(input) {
                if (this.searchOnEnter) {
                    return;
                }

                const q = input === undefined || input === null ? '' : String(input).trim();

                if (q.length <= 2) {
                    if (this.debouncedFetchSearchRemote && typeof this.debouncedFetchSearchRemote.cancel === 'function') {
                        this.debouncedFetchSearchRemote.cancel();
                    }
                    if (this.searchItemsCancelSource) {
                        this.searchItemsCancelSource.cancel('query-too-short');
                        this.searchItemsCancelSource = null;
                    }
                    this.loading_search = false;
                    this.filterItems();
                    return;
                }

                this.debouncedFetchSearchRemote(q);
            },
            async fetchSearchRemoteItems(query) {
                if (this.searchOnEnter) {
                    return;
                }

                const q = (query || '').toString().trim();
                if (q.length <= 2) {
                    return;
                }

                if (this.searchItemsCancelSource) {
                    this.searchItemsCancelSource.cancel('superseded');
                    this.searchItemsCancelSource = null;
                }
                this.searchItemsCancelSource = axios.CancelToken.source();

                const reqId = ++this._searchRequestSeq;
                this.loading_search = true;

                const params = {
                    input: q,
                    search_by_barcode: (this.search_item_by_barcode) ? 1 : 0,
                    search_item_by_barcode_presentation: 0,
                    search_factory_code_items: 0,
                };

                try {
                    const response = await this.$http.get(`/${this.resource}/search-items`, {
                        params,
                        cancelToken: this.searchItemsCancelSource.token,
                    });
                    this.items = response.data.items;
                    this.enabledSearchItemsBarcode(q);
                    if (this.items.length === 0) {
                        this.filterItems();
                        this.items = [];
                    }
                } catch (e) {
                    if (axios.isCancel(e)) {
                        return;
                    }
                    throw e;
                } finally {
                    this.searchItemsCancelSource = null;
                    if (reqId === this._searchRequestSeq) {
                        this.loading_search = false;
                    }
                }
            },
            async searchRemoteItemsWithEnter(){
                if(this.inputEnter.length > 2 && this.searchOnEnter){
                    this.loading_search = true
                    const params = {
                        input: this.inputEnter,
                        search_by_barcode: (this.search_item_by_barcode)?1:0,
                        search_item_by_barcode_presentation: 0,
                        search_factory_code_items: 0
                    }
                    try {
                        const response = await this.$http.get(`/${this.resource}/search-items`, { params })
                        this.items = response.data.items
                        this.enabledSearchItemsBarcode(this.inputEnter)
                        if (this.items.length == 0){
                            this.filterItems();
                            this.items=[];
                        }
                    } catch (e) {
                        this.items = [];
                    } finally {
                        this.loading_search = false
                    }
                    return
                }
            },
            enabledSearchItemsBarcode(input) {
                if (this.search_item_by_barcode) {

                    if (this.items.length == 0){

                        this.$refs.selectBarcode.$el.getElementsByTagName('input')[0].blur()
                        this.$refs.selectBarcode.blur();
                        setTimeout(() => {
                            this.$refs.selectBarcode.$el.getElementsByTagName('input')[0].focus();
                        }, 200);
                    }

                    if (this.items.length == 1) {
                        this.item_id = this.items[0].id;
                        if (this.$refs.selectBarcode) {
                            //this.$refs.selectBarcode.$el
                            //.getElementsByTagName("input")[0]
                            //.focus();
                            this.$refs.selectBarcode.$el.getElementsByTagName('input')[0].blur()
                            this.$refs.selectBarcode.blur();
                        }
                        this.changeItem();
                    }
                }
            },
            filterItems()
            {
                if(!this.searchOnEnter) {
                    this.items = this.all_items
                }
            },
            changeSearchOnEnter() {
                if(this.searchOnEnter) {
                    this.items = []
                }
            },
            itemSetSaleUnitPrice(row)
            {
                if(!this.configuration.enable_list_product && this.selectedOptionPrice !== 1) {
                    if(row.item_unit_types.length) {
                        let first_list = row.item_unit_types[0];
                        let priceSelected = first_list[this.selectedOptionPrice];
                        return row.unit_price_value = priceSelected;
                    } else {
                        return row.unit_price_value = "0";
                    }
                }
                return  parseFloat(row.sale_unit_price).toFixed(2);
            },
            changeInputEnter() {
                let value = this.$refs.selectBarcode.$el.getElementsByTagName('input')[0].value
                this.inputEnter = value 
            }
        }
    }
</script>

<style scoped>

    .custom-image {
        width: 100%;
        max-width: 64px;
        object-fit: contain;
    }

    .full-description {
        font-size: 0.85rem;
        white-space: normal;
        line-height: 1.5rem;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    .el-select-dropdown__item{
        height: 72px !important
    }

li.el-select-dropdown__item.item-result {
    border-bottom: 1px solid #e0e6f8;
}

</style>
<style>

    .list-result .el-select-dropdown__wrap {
        max-height: 360px !important;
    }

    .list-result .el-select-dropdown__list{
        padding: 0;
    }
</style>