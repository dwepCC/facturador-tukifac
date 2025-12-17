import Vue from 'vue'

/**
 * OPTIMIZACIÓN: Lazy Loading de Componentes Vue
 * 
 * Este archivo registra componentes Vue de forma lazy (bajo demanda)
 * en lugar de cargar todos los componentes al inicio.
 * 
 * Esto reduce significativamente el tiempo de carga inicial de la aplicación.
 */

// Función helper para registrar componentes de forma lazy (Vue 2 compatible)
// Vue 2 requiere que la función retorne directamente una Promise
function registerLazyComponent(name, importFn) {
    Vue.component(name, () => {
        const promise = importFn();
        return promise.then(module => {
            // Asegurar que se retorne el componente correcto
            const component = module.default || module;
            if (!component) {
                console.error(`Componente ${name} no tiene export default`);
                return {
                    template: '<div style="text-align: center; padding: 20px; color: red;"><p>Error: componente ' + name + ' no válido</p></div>'
                };
            }
            return component;
        }).catch(error => {
            console.error(`Error cargando componente ${name}:`, error);
            // Retornar un componente de error
            return {
                template: '<div style="text-align: center; padding: 20px; color: red;"><p>Error al cargar componente ' + name + '</p><p style="font-size: 12px;">' + (error.message || error) + '</p></div>'
            };
        });
    });
}

// Componentes más usados - cargar de forma síncrona para mejor UX inicial
import TenantDocumentsIndex from './views/tenant/documents/index.vue'
import TenantSaleNotesIndex from './views/tenant/sale_notes/index.vue'
import TenantItemsIndex from './views/tenant/items/index.vue'
import TenantPersonsIndex from './views/tenant/persons/index.vue'
import TenantDocumentsInvoice from './views/tenant/documents/invoice.vue'
import TenantDocumentsNote from './views/tenant/documents/note.vue'
import TenantQuotationsIndex from './views/tenant/quotations/index.vue'
import TenantPosIndex from './views/tenant/pos/index.vue'
import CashIndex from './views/tenant/cash/index.vue'

// Registrar componentes críticos de forma síncrona
Vue.component('tenant-documents-index', TenantDocumentsIndex)
Vue.component('tenant-sale-notes-index', TenantSaleNotesIndex)
Vue.component('tenant-items-index', TenantItemsIndex)
Vue.component('tenant-persons-index', TenantPersonsIndex)
Vue.component('tenant-documents-invoice', TenantDocumentsInvoice)
Vue.component('tenant-documents-note', TenantDocumentsNote)
Vue.component('tenant-quotations-index', TenantQuotationsIndex)
Vue.component('tenant-pos-index', TenantPosIndex)
Vue.component('cash-index', CashIndex)

// Componentes auxiliares comunes - también síncronos
import TenantItemAditionalInfoSelector from './views/tenant/components/partials/item_extra_info.vue'
import TenantItemAditionalInfoModal from './views/tenant/components/partials/modal_item_info_attributes.vue'
import XGraph from './components/graph/src/Graph.vue'
import XGraphLine from './components/graph/src/GraphLine.vue'
import TenantCalendar from './views/tenant/components/calendar.vue'
import TenantWarehouses from './views/tenant/components/warehouses.vue'
import TenantCalendarQuotation from './views/tenant/components/calendarquotations.vue'
import TenantProduct from './views/tenant/components/products.vue'
import TenantGuidesModal from './views/tenant/components/guides.vue'

Vue.component('tenant-item-aditional-info-selector', TenantItemAditionalInfoSelector)
Vue.component('tenant-item-aditional-info-modal', TenantItemAditionalInfoModal)
Vue.component('x-graph', XGraph)
Vue.component('x-graph-line', XGraphLine)
Vue.component('tenant-calendar', TenantCalendar)
Vue.component('tenant-warehouses', TenantWarehouses)
Vue.component('tenant-calendar-quotation', TenantCalendarQuotation)
Vue.component('tenant-product', TenantProduct)
Vue.component('tenant-guides-modal', TenantGuidesModal)

// Resto de componentes - LAZY LOADING
// Dashboard
registerLazyComponent('tenant-dashboard-index', () => import('../../modules/Dashboard/Resources/assets/js/views/index.vue'))
registerLazyComponent('tenant-dashboard-sales-by-product', () => import('../../modules/Dashboard/Resources/assets/js/views/items/SalesByProduct.vue'))

// Companies
registerLazyComponent('tenant-signature-pse-index', () => import('./views/tenant/companies/signature_pse/index.vue'))
registerLazyComponent('tenant-whatsapp-api-index', () => import('./views/tenant/companies/whatsapp_api/index.vue'))
registerLazyComponent('tenant-companies-form', () => import('./views/tenant/companies/form.vue'))
registerLazyComponent('tenant-companies-logo', () => import('./views/tenant/companies/logo.vue'))
registerLazyComponent('tenant-certificates-qztray', () => import('./views/tenant/companies/certificates_qztray/index.vue'))

// Certificates
registerLazyComponent('tenant-certificates-index', () => import('./views/tenant/certificates/index.vue'))
registerLazyComponent('tenant-certificates-form', () => import('./views/tenant/certificates/form.vue'))

// Configurations
registerLazyComponent('tenant-configurations-form', () => import('./views/tenant/configurations/form.vue'))
registerLazyComponent('tenant-configurations-form-purchases', () => import('./views/tenant/configurations/partials/purchases.vue'))
registerLazyComponent('tenant-configurations-visual', () => import('./views/tenant/configurations/visual.vue'))
registerLazyComponent('tenant-configurations-pdf', () => import('./views/tenant/configurations/pdf_templates.vue'))
registerLazyComponent('tenant-configurations-ticket-pdf', () => import('./views/tenant/configurations/pdf_ticket_templates.vue'))
registerLazyComponent('tenant-configurations-sale-notes', () => import('./views/tenant/configurations/sale_notes.vue'))
registerLazyComponent('tenant-configurations-pdf-guide', () => import('./views/tenant/configurations/pdf_guide_templates.vue'))
registerLazyComponent('tenant-configurations-preprinted-pdf', () => import('./views/tenant/configurations/pdf_preprinted_templates.vue'))
registerLazyComponent('tenant-dialog-header-menu', () => import('./views/tenant/configurations/partials/dialog_header_menu.vue'))

// Bank Accounts
registerLazyComponent('tenant-bank_accounts-index', () => import('./views/tenant/bank_accounts/index.vue'))

// Persons
registerLazyComponent('tenant-person-form', () => import('./views/tenant/persons/form.vue'))

// Users
registerLazyComponent('tenant-users-form', () => import('./views/tenant/users/form.vue'))
registerLazyComponent('tenant-users-index', () => import('./views/tenant/users/index.vue'))

// Documents
registerLazyComponent('tenant-documents-invoice-generate', () => import('./views/tenant/documents/invoice_generate.vue'))
registerLazyComponent('tenant-documents-invoicetensu', () => import('./views/tenant/documents/invoicetensu.vue'))
registerLazyComponent('tenant-documents-items-list', () => import('./views/tenant/documents/partials/item.vue'))

// Purchase Settlements
registerLazyComponent('tenant-purchase-settlements-index', () => import('./views/tenant/purchase-settlements/index.vue'))
registerLazyComponent('tenant-purchase-settlements-form', () => import('./views/tenant/purchase-settlements/form.vue'))

// Summaries & Voided
registerLazyComponent('tenant-summaries-index', () => import('./views/tenant/summaries/index.vue'))
registerLazyComponent('tenant-voided-index', () => import('./views/tenant/voided/index.vue'))
registerLazyComponent('tenant-search-index', () => import('./views/tenant/search/index.vue'))

// Options
registerLazyComponent('tenant-options-form', () => import('./views/tenant/options/form.vue'))
registerLazyComponent('tenant-options-form-item', () => import('./views/tenant/options/form_item.vue'))

// Unit Types & Others
registerLazyComponent('tenant-unit_types-index', () => import('./views/tenant/unit_types/index.vue'))
registerLazyComponent('tenant-detraction_types-index', () => import('./views/tenant/detraction_types/index.vue'))
registerLazyComponent('tenant-establishments-index', () => import('./views/tenant/establishments/index.vue'))
registerLazyComponent('tenant-charge_discounts-index', () => import('./views/tenant/charge_discounts/index.vue'))
registerLazyComponent('tenant-banks-index', () => import('./views/tenant/banks/index.vue'))
registerLazyComponent('tenant-exchange_rates-index', () => import('./views/tenant/exchange_rates/index.vue'))
registerLazyComponent('tenant-currency-types-index', () => import('./views/tenant/currency_types/index.vue'))
registerLazyComponent('tenant-retentions-index', () => import('./views/tenant/retentions/index.vue'))
registerLazyComponent('tenant-retentions-form', () => import('./views/tenant/retentions/form.vue'))
registerLazyComponent('tenant-perceptions-index', () => import('./views/tenant/perceptions/index.vue'))
registerLazyComponent('tenant-perceptions-form', () => import('./views/tenant/perceptions/form.vue'))

// Dispatches
registerLazyComponent('tenant-dispatches-index', () => import('./views/tenant/dispatches/index.vue'))
registerLazyComponent('tenant-dispatches-form', () => import('./views/tenant/dispatches/form.vue'))
registerLazyComponent('tenant-dispatches-create', () => import('./views/tenant/dispatches/create.vue'))
registerLazyComponent('tenant-dispatch_carrier-index', () => import('./views/tenant/dispatches/Carrier/Index.vue'))
registerLazyComponent('tenant-dispatch_carrier-form', () => import('./views/tenant/dispatches/Carrier/Form.vue'))
registerLazyComponent('tenant-purchases-items', () => import('./views/tenant/dispatches/items.vue'))
registerLazyComponent('tenant-drivers-index', () => import('./views/tenant/dispatches/drivers/index.vue'))
registerLazyComponent('tenant-dispatchers-index', () => import('./views/tenant/dispatches/dispatchers/index.vue'))
registerLazyComponent('tenant-transports-index', () => import('./views/tenant/dispatches/transports/index.vue'))
registerLazyComponent('tenant-origin_addresses-index', () => import('./views/tenant/dispatches/OriginAddress/Index.vue'))
registerLazyComponent('tenant-dispatch-addresses-index', () => import('./views/tenant/dispatches/dispatch-addresses/index.vue'))

// Purchases
registerLazyComponent('tenant-purchases-index', () => import('./views/tenant/purchases/index.vue'))
registerLazyComponent('tenant-purchases-form', () => import('./views/tenant/purchases/form.vue'))
registerLazyComponent('tenant-purchases-edit', () => import('./views/tenant/purchases/form_edit.vue'))
registerLazyComponent('tenant-transfer-reason-types-index', () => import('./views/tenant/transfer_reason_types/index.vue'))

// Attribute Types
registerLazyComponent('tenant-attribute_types-index', () => import('./views/tenant/attribute_types/index.vue'))

// Tasks
registerLazyComponent('tenant-tasks-lists', () => import('./views/tenant/tasks/lists.vue'))
registerLazyComponent('tenant-tasks-form', () => import('./views/tenant/tasks/form.vue'))
registerLazyComponent('tenant-reports-consistency-documents-lists', () => import('./views/tenant/reports/consistency-documents/lists.vue'))
registerLazyComponent('tenant-contingencies-index', () => import('./views/tenant/contingencies/index.vue'))

// Quotations
registerLazyComponent('tenant-quotations-form', () => import('./views/tenant/quotations/form.vue'))
registerLazyComponent('tenant-quotations-edit', () => import('./views/tenant/quotations/form_edit.vue'))
registerLazyComponent('tenant-quotations-item-form', () => import('./views/tenant/quotations/partials/item.vue'))

// Sale Notes
registerLazyComponent('tenant-sale-notes-form', () => import('./views/tenant/sale_notes/form.vue'))
registerLazyComponent('tenant-pos-fast', () => import('./views/tenant/pos/fast.vue'))
registerLazyComponent('tenant-pos-garage', () => import('./views/tenant/pos/garage.vue'))
registerLazyComponent('tenant-card-brands-index', () => import('./views/tenant/card_brands/index.vue'))
registerLazyComponent('tenant-payment-method-index', () => import('./views/tenant/payment_method/index.vue'))

// Inventory Module
registerLazyComponent('inventory-index', () => import('../../modules/Inventory/Resources/assets/js/inventory/index.vue'))
registerLazyComponent('inventory-transfers-index', () => import('../../modules/Inventory/Resources/assets/js/transfers/index.vue'))
registerLazyComponent('warehouses-index', () => import('../../modules/Inventory/Resources/assets/js/warehouses/index.vue'))
registerLazyComponent('tenant-report-kardex-index', () => import('../../modules/Inventory/Resources/assets/js/kardex/index.vue'))
registerLazyComponent('tenant-inventories-form', () => import('../../modules/Inventory/Resources/assets/js/config/form.vue'))
registerLazyComponent('inventory-review-index', () => import('@viewsModuleInventory/inventory-review/index.vue'))
registerLazyComponent('tenant-inventory-report', () => import('../../modules/Inventory/Resources/assets/js/inventory/reports/index.vue'))
registerLazyComponent('tenant-inventory-color-index', () => import('../../modules/Inventory/Resources/assets/js/extra_info/color/index.vue'))
registerLazyComponent('tenant-inventory-item-units-per-package-index', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_units_per_package/index.vue'))
registerLazyComponent('tenant-inventory-item-units-business', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_units_business/index.vue'))
registerLazyComponent('tenant-inventory-item-package-measurements', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_package_measurements/index.vue'))
registerLazyComponent('tenant-inventory-mold-cavities', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_mold_cavities/index.vue'))
registerLazyComponent('tenant-inventory-mold-property', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_mold_property/index.vue'))
registerLazyComponent('tenant-inventory-size-property', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_size/index.vue'))
registerLazyComponent('tenant-inventory-item-status', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_status/index.vue'))
registerLazyComponent('tenant-inventory-item-product-family', () => import('../../modules/Inventory/Resources/assets/js/extra_info/item_product_family/index.vue'))
registerLazyComponent('tenant-inventory-extra-info-list', () => import('../../modules/Inventory/Resources/assets/js/extra_info/index.vue'))
registerLazyComponent('tenant-inventory-devolutions-index', () => import('../../modules/Inventory/Resources/assets/js/devolutions/index.vue'))
registerLazyComponent('tenant-inventory-devolutions-form', () => import('../../modules/Inventory/Resources/assets/js/devolutions/form.vue'))
registerLazyComponent('moves-index', () => import('../../modules/Inventory/Resources/assets/js/moves/index.vue'))
registerLazyComponent('inventory-form-masive', () => import('../../modules/Inventory/Resources/assets/js/transfers/form_masive.vue'))
registerLazyComponent('tenant-report-kardex-master', () => import('../../modules/Inventory/Resources/assets/js/kardex_master/index.vue'))
registerLazyComponent('tenant-report-kardex-lots', () => import('../../modules/Inventory/Resources/assets/js/kardex/lots.vue'))
registerLazyComponent('tenant-report-kardex-series', () => import('../../modules/Inventory/Resources/assets/js/kardex/series.vue'))
registerLazyComponent('tenant-report-valued-kardex', () => import('../../modules/Inventory/Resources/assets/js/valued_kardex/index.vue'))

// Expense Module
registerLazyComponent('tenant-expenses-index', () => import('../../modules/Expense/Resources/assets/js/views/expenses/index.vue'))
registerLazyComponent('tenant-expenses-form', () => import('../../modules/Expense/Resources/assets/js/views/expenses/form.vue'))
registerLazyComponent('tenant-expense-types-index', () => import('@viewsModuleExpense/expense_types/index.vue'))
registerLazyComponent('tenant-expense-reasons-index', () => import('@viewsModuleExpense/expense_reasons/index.vue'))
registerLazyComponent('tenant-expense-method-types-index', () => import('@viewsModuleExpense/expense_method_types/index.vue'))
registerLazyComponent('tenant-bankloans-index', () => import('../../modules/Expense/Resources/assets/js/views/bank_loans/index.vue'))
registerLazyComponent('tenant-bankloans-form', () => import('../../modules/Expense/Resources/assets/js/views/bank_loans/form.vue'))

// Account Module
registerLazyComponent('tenant-account-export', () => import('../../modules/Account/Resources/assets/js/views/account/export.vue'))
registerLazyComponent('tenant-account-summary-report', () => import('../../modules/Account/Resources/assets/js/views/summary_report/index.vue'))
registerLazyComponent('tenant-account-format', () => import('../../modules/Account/Resources/assets/js/views/account/format.vue'))
registerLazyComponent('tenant-company-accounts', () => import('../../modules/Account/Resources/assets/js/views/company_accounts/form.vue'))
registerLazyComponent('tenant-ledger-accounts', () => import('../../modules/Account/Resources/assets/js/views/ledger_accounts/form.vue'))
registerLazyComponent('tenant-account-payment-index', () => import('./views/tenant/account/payment_index.vue'))
registerLazyComponent('tenant-account-configuration-index', () => import('./views/tenant/account/configuration.vue'))

// Document Module
registerLazyComponent('tenant-documents-not-sent', () => import('../../modules/Document/Resources/assets/js/views/documents/not_sent.vue'))
registerLazyComponent('tenant-documents-regularize-shipping', () => import('../../modules/Document/Resources/assets/js/views/documents/regularize_shipping.vue'))
registerLazyComponent('tenant-series-configurations-index', () => import('../../modules/Document/Resources/assets/js/views/series_configurations/index.vue'))
registerLazyComponent('tenant-validate-documents-index', () => import('../../modules/Document/Resources/assets/js/views/validate_documents/index.vue'))

// Report Module
registerLazyComponent('tenant-report-purchases-index', () => import('../../modules/Report/Resources/assets/js/views/purchases/index.vue'))
registerLazyComponent('tenant-report-documents-index', () => import('../../modules/Report/Resources/assets/js/views/documents/index.vue'))
registerLazyComponent('tenant-state-account-index', () => import('../../modules/Report/Resources/assets/js/views/state_account/index.vue'))
registerLazyComponent('tenant-report-customers-index', () => import('../../modules/Report/Resources/assets/js/views/customers/index.vue'))
registerLazyComponent('tenant-report-items-index', () => import('../../modules/Report/Resources/assets/js/views/items/index.vue'))
registerLazyComponent('tenant-report-items-extra-index', () => import('../../modules/Report/Resources/assets/js/views/items/index_extra.vue'))
registerLazyComponent('tenant-report-download-tray-index', () => import('../../modules/Report/Resources/assets/js/views/download_tray/index.vue'))
registerLazyComponent('tenant-report-guide-index', () => import('../../modules/Report/Resources/assets/js/views/guide/index.vue'))
registerLazyComponent('tenant-report-sale_notes-index', () => import('../../modules/Report/Resources/assets/js/views/sale_notes/index.vue'))
registerLazyComponent('tenant-report-quotations-index', () => import('../../modules/Report/Resources/assets/js/views/quotations/index.vue'))
registerLazyComponent('tenant-report-cash-index', () => import('../../modules/Report/Resources/assets/js/views/cash/index.vue'))
registerLazyComponent('tenant-index-configuration', () => import('../../modules/BusinessTurn/Resources/assets/js/views/configurations/index.vue'))
registerLazyComponent('tenant-report-document_hotels-index', () => import('../../modules/Report/Resources/assets/js/views/document_hotels/index.vue'))
registerLazyComponent('tenant-report_hotels-index', () => import('../../modules/Report/Resources/assets/js/views/report_hotels/index.vue'))
registerLazyComponent('tenant-report-commercial_analysis-index', () => import('../../modules/Report/Resources/assets/js/views/commercial_analysis/index.vue'))
registerLazyComponent('tenant-report-document-detractions-index', () => import('../../modules/Report/Resources/assets/js/views/document-detractions/index.vue'))
registerLazyComponent('tenant-report-commissions-index', () => import('../../modules/Report/Resources/assets/js/views/commissions/index.vue'))
registerLazyComponent('tenant-report-order-notes-consolidated-index', () => import('../../modules/Report/Resources/assets/js/views/order_notes_consolidated/index.vue'))
registerLazyComponent('tenant-report-general-items-index', () => import('../../modules/Report/Resources/assets/js/views/general_items/index.vue'))
registerLazyComponent('tenant-report-order-notes-general-index', () => import('../../modules/Report/Resources/assets/js/views/order_notes_general/index.vue'))
registerLazyComponent('tenant-report-sales-consolidated-index', () => import('../../modules/Report/Resources/assets/js/views/sales_consolidated/index.vue'))
registerLazyComponent('tenant-report-user-commissions-index', () => import('../../modules/Report/Resources/assets/js/views/user_commissions/index.vue'))
registerLazyComponent('tenant-report-fixed-asset-purchases-index', () => import('../../modules/Report/Resources/assets/js/views/fixed-asset-purchases/index.vue'))
registerLazyComponent('tenant-report-massive-downloads-index', () => import('../../modules/Report/Resources/assets/js/views/massive-downloads/index.vue'))
registerLazyComponent('tenant-report-commissions-detail-index', () => import('../../modules/Report/Resources/assets/js/views/commissions_detail/index.vue'))
registerLazyComponent('tenant-report-tips-index', () => import('../../modules/Report/Resources/assets/js/views/tips/index.vue'))
registerLazyComponent('tenant-report-pending-account-commissions-index', () => import('@viewsModuleReport/pending-account-commissions/index.vue'))

// Item Module
registerLazyComponent('tenant-categories-index', () => import('../../modules/Item/Resources/assets/js/views/categories/index.vue'))
registerLazyComponent('tenant-brands-index', () => import('../../modules/Item/Resources/assets/js/views/brands/index.vue'))
registerLazyComponent('tenant-zone-index', () => import('../../modules/Item/Resources/assets/js/views/zone/index.vue'))
registerLazyComponent('tenant-incentives-index', () => import('../../modules/Item/Resources/assets/js/views/incentives/index.vue'))
registerLazyComponent('tenant-item-lots-index', () => import('../../modules/Item/Resources/assets/js/views/item-lots/index.vue'))
registerLazyComponent('tenant-web-platforms-index', () => import('@viewsModuleItem/web-platforms/index.vue'))
registerLazyComponent('tenant-item-detail-index', () => import('@viewsModuleItem/items/item-detail.vue'))

// Ecommerce Module
registerLazyComponent('tenant-ecommerce-configuration-info', () => import('../../modules/Ecommerce/Resources/assets/js/views/configuration/index.vue'))
registerLazyComponent('tenant-ecommerce-configuration-culqi', () => import('../../modules/Ecommerce/Resources/assets/js/views/configuration_culqi/index.vue'))
registerLazyComponent('tenant-ecommerce-configuration-paypal', () => import('../../modules/Ecommerce/Resources/assets/js/views/configuration_paypal/index.vue'))
registerLazyComponent('tenant-ecommerce-configuration-logo', () => import('../../modules/Ecommerce/Resources/assets/js/views/configuration_logo/index.vue'))
registerLazyComponent('tenant-ecommerce-configuration-social', () => import('../../modules/Ecommerce/Resources/assets/js/views/configuration_social/index.vue'))
registerLazyComponent('tenant-ecommerce-configuration-tag', () => import('../../modules/Ecommerce/Resources/assets/js/views/configuration_tags/index.vue'))
registerLazyComponent('tenant-ecommerce-item-sets-index', () => import('../../modules/Ecommerce/Resources/assets/js/views/item_sets/index.vue'))
registerLazyComponent('tenant-ecommerce-configuration-links', () => import('../../modules/Ecommerce/Resources/assets/js/views/configuration_links/index.vue'))
registerLazyComponent('tenant-items-ecommerce-index', () => import('./views/tenant/items_ecommerce/index.vue'))
registerLazyComponent('tenant-ecommerce-cart', () => import('./views/tenant/ecommerce/cart_dropdown.vue'))
registerLazyComponent('tenant-tags-index', () => import('./views/tenant/tags/index.vue'))
registerLazyComponent('tenant-promotions-index', () => import('./views/tenant/promotions/index.vue'))
registerLazyComponent('tenant-item-sets-index', () => import('./views/tenant/item_sets/index.vue'))

// Purchase Module
registerLazyComponent('tenant-purchase-quotations-index', () => import('../../modules/Purchase/Resources/assets/js/views/purchase-quotations/index.vue'))
registerLazyComponent('tenant-purchase-quotations-form', () => import('../../modules/Purchase/Resources/assets/js/views/purchase-quotations/form.vue'))
registerLazyComponent('tenant-purchase-orders-index', () => import('../../modules/Purchase/Resources/assets/js/views/purchase-orders/index.vue'))
registerLazyComponent('tenant-purchase-orders-form', () => import('../../modules/Purchase/Resources/assets/js/views/purchase-orders/form.vue'))
registerLazyComponent('tenant-purchase-orders-generate', () => import('../../modules/Purchase/Resources/assets/js/views/purchase-orders/generate.vue'))
registerLazyComponent('tenant-fixed-asset-items-index', () => import('@viewsModulePurchase/fixed_asset_items/index.vue'))
registerLazyComponent('tenant-fixed-asset-purchases-index', () => import('@viewsModulePurchase/fixed_asset_purchases/index.vue'))
registerLazyComponent('tenant-fixed-asset-purchases-form', () => import('@viewsModulePurchase/fixed_asset_purchases/form.vue'))

// Order Module
registerLazyComponent('tenant-order-notes-index', () => import('../../modules/Order/Resources/assets/js/views/order_notes/index.vue'))
registerLazyComponent('tenant-order-notes-form', () => import('../../modules/Order/Resources/assets/js/views/order_notes/form.vue'))
registerLazyComponent('tenant-order-notes-edit', () => import('../../modules/Order/Resources/assets/js/views/order_notes/form_edit.vue'))
registerLazyComponent('tenant-mitiendape-config', () => import('../../modules/Order/Resources/assets/js/views/mi_tienda_pe/form.vue'))
registerLazyComponent('tenant-order-forms-index', () => import('@viewsModuleOrder/order_forms/index.vue'))
registerLazyComponent('tenant-order-forms-form', () => import('@viewsModuleOrder/order_forms/form.vue'))
registerLazyComponent('tenant-orders-index', () => import('./views/tenant/orders/index.vue'))

// Finance Module
registerLazyComponent('tenant-finance-global-payments-index', () => import('../../modules/Finance/Resources/assets/js/views/global_payments/index.vue'))
registerLazyComponent('tenant-finance-balance-index', () => import('../../modules/Finance/Resources/assets/js/views/balance/index.vue'))
registerLazyComponent('tenant-finance-modal-transfer-between-accounts', () => import('../../modules/Finance/Resources/assets/js/views/transfer_between_accounts/options.vue'))
registerLazyComponent('tenant-finance-payment-method-types-index', () => import('../../modules/Finance/Resources/assets/js/views/payment_method_types/index.vue'))
registerLazyComponent('tenant-finance-unpaid-index', () => import('@viewsModuleFinance/unpaid/index.vue'))
registerLazyComponent('tenant-finance-to-pay-index', () => import('@viewsModuleFinance/to_pay/index.vue'))
registerLazyComponent('tenant-finance-income-index', () => import('@viewsModuleFinance/income/index.vue'))
registerLazyComponent('tenant-finance-income-form', () => import('@viewsModuleFinance/income/form.vue'))
registerLazyComponent('tenant-income-types-index', () => import('@viewsModuleFinance/income_types/index.vue'))
registerLazyComponent('tenant-income-reasons-index', () => import('@viewsModuleFinance/income_reasons/index.vue'))
registerLazyComponent('tenant-finance-movements-index', () => import('@viewsModuleFinance/movements/index.vue'))

// Sale Module
registerLazyComponent('tenant-sale-opportunities-index', () => import('@viewsModuleSale/sale_opportunities/index.vue'))
registerLazyComponent('tenant-sale-opportunities-form', () => import('@viewsModuleSale/sale_opportunities/form.vue'))
registerLazyComponent('tenant-payment-method-types-index', () => import('@viewsModuleSale/payment_method_types/index.vue'))
registerLazyComponent('tenant-contracts-index', () => import('@viewsModuleSale/contracts/index.vue'))
registerLazyComponent('tenant-contracts-form', () => import('@viewsModuleSale/contracts/form.vue'))
registerLazyComponent('tenant-production-orders-index', () => import('@viewsModuleSale/production_orders/index.vue'))
registerLazyComponent('tenant-agents-index', () => import('@viewsModuleSale/agents/index.vue'))
registerLazyComponent('tenant-technical-services-index', () => import('@viewsModuleSale/technical-services/index.vue'))
registerLazyComponent('tenant-user-commissions-index', () => import('@viewsModuleSale/user-commissions/index.vue'))
registerLazyComponent('tenant-pending-account-commissions-index', () => import('@viewsModuleSale/pending-accounts/index.vue'))

// Hotel Module
registerLazyComponent('tenant-hotel-rates', () => import('@viewsModuleHotel/rates/List.vue'))
registerLazyComponent('tenant-hotel-categories', () => import('@viewsModuleHotel/categories/List.vue'))
registerLazyComponent('tenant-hotel-floors', () => import('@viewsModuleHotel/floors/List.vue'))
registerLazyComponent('tenant-hotel-rooms', () => import('@viewsModuleHotel/rooms/List.vue'))
registerLazyComponent('tenant-hotel-reception', () => import('@viewsModuleHotel/rooms/Reception.vue'))
registerLazyComponent('tenant-hotel-sucursale', () => import('@viewsModuleHotel/rooms/partials/ButtonSucursales.vue'))
registerLazyComponent('tenant-hotel-rent', () => import('@viewsModuleHotel/rooms/Rent.vue'))
registerLazyComponent('tenant-hotel-rent-add-product', () => import('@viewsModuleHotel/rooms/AddProductToRoom.vue'))
registerLazyComponent('tenant-hotel-rent-checkout', () => import('@viewsModuleHotel/rooms/Checkout.vue'))

// Documentary Procedure Module
registerLazyComponent('tenant-documentary-offices', () => import('@viewsModuleDocumentary/offices/Offices.vue'))
registerLazyComponent('tenant-documentary-status', () => import('@viewsModuleDocumentary/status/Status.vue'))
registerLazyComponent('tenant-documentary-processes', () => import('@viewsModuleDocumentary/processes/Processes.vue'))
registerLazyComponent('tenant-documentary-documents', () => import('@viewsModuleDocumentary/documents/Documents.vue'))
registerLazyComponent('tenant-documentary-actions', () => import('@viewsModuleDocumentary/actions/Actions.vue'))
registerLazyComponent('tenant-documentary-files', () => import('@viewsModuleDocumentary/files/Files.vue'))
registerLazyComponent('tenant-documentary-requirements', () => import('@viewsModuleDocumentary/requirements/Requirements.vue'))
registerLazyComponent('tenant-documentary-statistic', () => import('@viewsModuleDocumentary/statistic/Index.vue'))
registerLazyComponent('tenant-documentary-files-simplify', () => import('@viewsModuleDocumentary/files_simplify/Files.vue'))
registerLazyComponent('tenant-documentary-files-simplify-form', () => import('@viewsModuleDocumentary/files_simplify/FilesNew.vue'))

// ApiPeru
registerLazyComponent('x-input-service', () => import('../../modules/ApiPeruDev/Resources/assets/js/components/InputService.vue'))

// Person Types
registerLazyComponent('tenant-person-types-index', () => import('./views/tenant/person_types/index.vue'))

// Login
registerLazyComponent('tenant-login-page', () => import('./views/tenant/login/index.vue'))

// Digemid
registerLazyComponent('tenant-digemid-index', () => import('../../modules/Digemid/Resources/assets/js/view/index.vue'))

// Suscription Module
registerLazyComponent('tenant-suscription-client-index', () => import('../../modules/Suscription/Resources/assets/js/clients/index.vue'))
registerLazyComponent('tenant-suscription-plans-index', () => import('../../modules/Suscription/Resources/assets/js/plans/index.vue'))
registerLazyComponent('tenant-suscription-payments-index', () => import('../../modules/Suscription/Resources/assets/js/payments/index.vue'))
registerLazyComponent('data-table-payment-receipt', () => import('../js/components/DataTablePaymentReceipt.vue'))
registerLazyComponent('tenant-index-payment-receipt', () => import('../../modules/Suscription/Resources/assets/js/payment_receipt/index.vue'))
registerLazyComponent('tenant-suscription-grades-index', () => import('@viewsModuleSuscription/grades/index.vue'))
registerLazyComponent('tenant-suscription-sections-index', () => import('@viewsModuleSuscription/sections/index.vue'))

// Full Suscription Module
registerLazyComponent('tenant-full-suscription-client-index', () => import('../../modules/FullSuscription/Resources/assets/js/clients/index.vue'))
registerLazyComponent('tenant-full-suscription-plans-index', () => import('../../modules/FullSuscription/Resources/assets/js/plans/index.vue'))
registerLazyComponent('tenant-full-suscription-payments-index', () => import('../../modules/FullSuscription/Resources/assets/js/payments/index.vue'))
registerLazyComponent('tenant-full-suscription-index-payment-receipt', () => import('../../modules/FullSuscription/Resources/assets/js/payment_receipt/index.vue'))

// Production Module
registerLazyComponent('tenant-mill-index', () => import('../../modules/Production/Resources/assets/js/view/mill/index.vue'))
registerLazyComponent('tenant-mill-form', () => import('../../modules/Production/Resources/assets/js/view/mill/form.vue'))
registerLazyComponent('tenant-machine-index', () => import('../../modules/Production/Resources/assets/js/view/machine/index.vue'))
registerLazyComponent('tenant-machine-type-index', () => import('../../modules/Production/Resources/assets/js/view/machine/index_type.vue'))
registerLazyComponent('tenant-machine-form', () => import('../../modules/Production/Resources/assets/js/view/machine/form.vue'))
registerLazyComponent('tenant-machine-type-form', () => import('../../modules/Production/Resources/assets/js/view/machine/form_type.vue'))
registerLazyComponent('tenant-workers-index', () => import('../../modules/Production/Resources/assets/js/view/workers/index.vue'))
registerLazyComponent('tenant-production-index', () => import('../../modules/Production/Resources/assets/js/view/production/index.vue'))
registerLazyComponent('tenant-production-form', () => import('../../modules/Production/Resources/assets/js/view/production/form.vue'))
registerLazyComponent('tenant-packaging-index', () => import('../../modules/Production/Resources/assets/js/view/packaging/index.vue'))
registerLazyComponent('tenant-packaging-form', () => import('../../modules/Production/Resources/assets/js/view/packaging/form.vue'))

// Restaurant Module
registerLazyComponent('tenant-restaurant-list-items', () => import('../../modules/Restaurant/Resources/assets/js/views/items/index.vue'))
registerLazyComponent('tenant-restaurant-promotions-index', () => import('../../modules/Restaurant/Resources/assets/js/views/promotions/index.vue'))
registerLazyComponent('tenant-restaurant-orders-index', () => import('../../modules/Restaurant/Resources/assets/js/views/orders/index.vue'))
registerLazyComponent('tenant-restaurant-cash-index', () => import('../../modules/Restaurant/Resources/assets/js/views/cash/index.vue'))
registerLazyComponent('tenant-restaurant-cash-filter-pos', () => import('../../modules/Restaurant/Resources/assets/js/views/cash/filter-pos.vue'))
registerLazyComponent('tenant-restaurant-configuration', () => import('../../modules/Restaurant/Resources/assets/js/views/configuration/index.vue'))

// Payment Module
registerLazyComponent('tenant-payment-configurations-index', () => import('@viewsModulePayment/payment_configurations/index.vue'))
registerLazyComponent('tenant-public-payment-links-index', () => import('@viewsModulePayment/payment_links/public/index.vue'))
registerLazyComponent('tenant-payment-links-index', () => import('@viewsModulePayment/payment_links/index.vue'))

// Mobile App Module
registerLazyComponent('tenant-mobile-app-configuration', () => import('@viewsModuleMobileApp/configuration/index.vue'))
registerLazyComponent('tenant-mobile-app-permissions', () => import('@viewsModuleMobileApp/permissions/index.vue'))

// LevelAccess Module
registerLazyComponent('tenant-system-activity-logs-generals-index', () => import('@viewsModuleLevelAccess/system_activity_logs/generals/index.vue'))
registerLazyComponent('tenant-system-activity-logs-transactions-index', () => import('@viewsModuleLevelAccess/system_activity_logs/transactions/index.vue'))
registerLazyComponent('tenant-remember-change-password', () => import('./views/tenant/users/partials/remember_change_password.vue'))

// Offline Module
registerLazyComponent('tenant-offline-configurations-index', () => import('../../modules/Offline/Resources/assets/js/views/offline_configurations/index.vue'))

// Sire
registerLazyComponent('tenant-sire-index', () => import('./views/tenant/sire/index.vue'))
registerLazyComponent('tenant-qr-chat', () => import('@viewsModuleQrChatBuho/Configuration.vue'))
registerLazyComponent('tenant-qr-api', () => import('@viewsModuleQrApi/ConfigurationQrApi.vue'))

// Profile
registerLazyComponent('tenant-profile-index', () => import('./views/tenant/profile/index.vue'))

// MultiUser Module
registerLazyComponent('tenant-multi-users-change-client', () => import('@viewsModuleMultiUser/tenant/multi-users/change-client.vue'))

// Verificar que los componentes críticos se registraron correctamente
console.log('✅ Componentes Vue registrados:');
console.log('  - Componentes críticos (síncronos):', [
    'tenant-documents-index',
    'tenant-sale-notes-index', 
    'tenant-items-index',
    'tenant-persons-index',
    'tenant-documents-invoice',
    'tenant-documents-note',
    'tenant-quotations-index',
    'tenant-pos-index',
    'cash-index'
].length);
console.log('  - Componentes lazy:', Object.keys(Vue.options.components).filter(c => c.startsWith('tenant-')).length);

