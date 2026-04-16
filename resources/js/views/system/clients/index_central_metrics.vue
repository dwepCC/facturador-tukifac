<template>
    <div class="central-metrics-page card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="mb-1 font-weight-bold text-dark">Clientes (métricas centralizadas)</h4>
                <p class="text-muted small mb-0">
                    Listado desde la base central. Columnas configurables (preferencias independientes del listado clásico).
                </p>
            </div>
            <button type="button" class="btn btn-success btn-sm mt-2 mt-md-0" @click="clickCreate">
                <i class="fas fa-plus mr-1"></i> Nuevo cliente
            </button>
        </div>

        <div class="card-body">
            <div class="filters-panel bg-light border rounded p-3 mb-4">
                <div class="row align-items-end">
                    <div class="col-12 col-md-6 col-lg-3 mb-3 mb-lg-0">
                        <label class="d-block small font-weight-bold text-muted mb-1">Buscar</label>
                        <el-input
                            v-model="filters.search"
                            placeholder="Nombre, RUC, correo, dominio"
                            clearable
                            prefix-icon="el-icon-search"
                            size="small"
                            @clear="reload"
                            @keyup.enter.native="reload"
                        />
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3 mb-lg-0">
                        <label class="d-block small font-weight-bold text-muted mb-1">Plan</label>
                        <el-select v-model="filters.plan_id" filterable size="small" class="central-filter-select" @change="reload">
                            <el-option label="Todos los planes" value="" />
                            <el-option v-for="p in plans" :key="p.id" :label="p.name" :value="p.id" />
                        </el-select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3 mb-lg-0">
                        <label class="d-block small font-weight-bold text-muted mb-1">Entorno (SOAP)</label>
                        <el-select v-model="filters.soap_type" placeholder="Todos" clearable size="small" class="central-filter-select" @change="reload">
                            <el-option label="Demo" value="01" />
                            <el-option label="Producción" value="02" />
                            <el-option label="Interno" value="03" />
                        </el-select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3 mb-lg-0">
                        <label class="d-block small font-weight-bold text-muted mb-1">Estado cuenta</label>
                        <el-select v-model="filters.locked_tenant" placeholder="Todos" clearable size="small" class="central-filter-select" @change="reload">
                            <el-option label="Activos" :value="0" />
                            <el-option label="Bloqueados" :value="1" />
                        </el-select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 mb-0">
                        <label class="d-block small font-weight-bold text-muted mb-1">Docs por fecha</label>
                        <el-date-picker
                            v-model="filters.dateRange"
                            type="daterange"
                            range-separator="a"
                            start-placeholder="Desde"
                            end-placeholder="Hasta"
                            value-format="yyyy-MM-dd"
                            unlink-panels
                            size="small"
                            class="central-filter-daterange"
                            @change="onDateChange"
                        />
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div class="text-muted small mb-2 mb-md-0">
                    <span class="badge badge-secondary font-weight-normal">{{ meta.total }} clientes</span>
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-primary btn-sm mr-2" :disabled="loading" @click="reload">
                        <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': loading }"></i> Actualizar
                    </button>
                    <el-dropdown :hide-on-click="false" trigger="click">
                        <el-button type="info" plain size="small">
                            Mostrar columnas<i class="el-icon-arrow-down el-icon--right"></i>
                        </el-button>
                        <el-dropdown-menu slot="dropdown" class="central-columns-dropdown-menu">
                            <el-dropdown-item v-for="(column, index) in columnsComputed" :key="index">
                                <el-checkbox
                                    v-if="column.title !== undefined && column.visible !== undefined"
                                    v-model="column.visible"
                                    @change="saveColumnVisibility"
                                >
                                    {{ column.title }}
                                </el-checkbox>
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </div>
            </div>

            <div class="central-table-shell border rounded bg-white position-relative">
                <div v-if="loading" class="central-loading-overlay d-flex align-items-center justify-content-center">
                    <div class="text-center text-muted">
                        <i class="fas fa-circle-notch fa-spin fa-2x mb-2 d-block text-primary"></i>
                        <span class="small">Cargando…</span>
                    </div>
                </div>

                <div ref="tableScroll" class="table-responsive central-table-scroll" @scroll="onTableScroll">
                    <table class="table table-sm table-striped table-hover table-bordered mb-0 central-metrics-table">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col" class="text-center text-muted">#</th>
                                <th scope="col" class="sticky-column" :class="{ 'scroll-active': tableScrollActive }">Hostname</th>
                                <th v-if="columns.bloquear_cuenta.visible" scope="col" class="text-center">Bloquear cuenta</th>
                                <th v-if="columns.nombre.visible" scope="col">Nombre</th>
                                <th v-if="columns.ruc.visible" scope="col">RUC</th>
                                <th v-if="columns.plan.visible" scope="col">Plan</th>
                                <th v-if="columns.correo.visible" scope="col">Correo</th>
                                <th v-if="columns.entorno.visible" scope="col">Entorno</th>
                                <th v-if="columns.total_comprobantes.visible" scope="col" class="text-center">Total comprobantes</th>
                                <th v-if="columns.notificaciones.visible" scope="col" class="text-center">Notificaciones</th>
                                <th v-if="columns.otras_notificaciones.visible" scope="col" class="text-center">Otras notif.</th>
                                <th v-if="columns.inicio_ciclo.visible" scope="col" class="text-center">Inicio ciclo fact.</th>
                                <th v-if="columns.comprobantes_ciclo.visible" scope="col" class="text-center">Comp. ciclo / límite</th>
                                <th v-if="columns.usuarios.visible" scope="col" class="text-center">Usuarios</th>
                                <th v-if="columns.sucursales.visible" scope="col" class="text-center">Sucursales</th>
                                <th v-if="columns.ventas_mes.visible" scope="col" class="text-center">Ventas (mes)</th>
                                <th v-if="columns.fecha_creacion.visible" scope="col">F. creación</th>
                                <th v-if="columns.consultas_api.visible" scope="col" class="text-center">Consultas API (mes)</th>
                                <th v-if="columns.notas_venta.visible" scope="col" class="text-center">Cant. NV</th>
                                <th v-if="columns.total_mes.visible" scope="col" class="text-center">Comp. / mes</th>
                                <th v-if="columns.total_pse.visible" scope="col" class="text-center">Comp. PSE</th>
                                <th v-if="columns.total_notas.visible" scope="col" class="text-center">Docs+NV ciclo</th>
                                <th v-if="columns.limitar_doc.visible" scope="col" class="text-center">Limitar doc.</th>
                                <th v-if="columns.limitar_usuarios.visible" scope="col" class="text-center">Limitar usuarios</th>
                                <th v-if="columns.limitar_sucursales.visible" scope="col" class="text-center">Limitar sucursales</th>
                                <th v-if="columns.limitar_ventas.visible" scope="col" class="text-center">Limitar ventas</th>
                                <th scope="col">Últ. sync central</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!loading && records.length === 0">
                                <td :colspan="visibleColCount" class="text-center text-muted py-5">No hay registros con los filtros actuales.</td>
                            </tr>
                            <tr v-for="(row, index) in records" :key="row.id">
                                <td class="text-center text-muted">{{ indexMethod(index) }}</td>
                                <td class="sticky-column" :class="{ 'scroll-active': tableScrollActive }">
                                    <a :href="'http://' + row.hostname" target="_blank" rel="noopener" class="hostname-link font-weight-bold">{{
                                        row.hostname
                                    }}</a>
                                </td>
                                <td v-if="columns.bloquear_cuenta.visible" class="text-center">
                                    <template v-if="!row.locked">
                                        <el-switch v-model="row.locked_tenant" @change="changeLockedTenant(row)" />
                                    </template>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td v-if="columns.nombre.visible">{{ row.name }}</td>
                                <td v-if="columns.ruc.visible">{{ row.number }}</td>
                                <td v-if="columns.plan.visible">{{ row.plan }}</td>
                                <td v-if="columns.correo.visible">{{ row.email }}</td>
                                <td v-if="columns.entorno.visible">{{ entornoLabel(row.soap_type) }}</td>
                                <td v-if="columns.total_comprobantes.visible" class="text-center">{{ row.count_doc }}</td>
                                <td v-if="columns.notificaciones.visible" class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center align-items-center">
                                        <template v-if="row.document_not_sent > 0 || row.document_regularize_shipping > 0 || row.document_to_be_canceled > 0">
                                            <span v-if="row.document_not_sent > 0" class="d-inline-flex align-items-center mx-1">
                                                <el-tooltip content="Por enviar / borrador (01, 03)" placement="top">
                                                    <el-badge :value="row.document_not_sent" class="item" type="danger">
                                                        <i class="far fa-bell text-secondary"></i>
                                                    </el-badge>
                                                </el-tooltip>
                                                <el-button
                                                    type="text"
                                                    size="mini"
                                                    icon="el-icon-view"
                                                    class="p-0 ml-1 central-events-btn"
                                                    title="Ver listado"
                                                    @click.stop="openDocumentEventsModal(row, 'not_sent')"
                                                />
                                            </span>
                                            <span v-if="row.document_regularize_shipping > 0" class="d-inline-flex align-items-center mx-1">
                                                <el-tooltip content="Pendientes de rectificación de envío" placement="top">
                                                    <el-badge :value="row.document_regularize_shipping" class="item" type="warning">
                                                        <i class="fas fa-exclamation-triangle text-secondary"></i>
                                                    </el-badge>
                                                </el-tooltip>
                                                <el-button
                                                    type="text"
                                                    size="mini"
                                                    icon="el-icon-view"
                                                    class="p-0 ml-1 central-events-btn"
                                                    title="Ver listado"
                                                    @click.stop="openDocumentEventsModal(row, 'regularize_shipping')"
                                                />
                                            </span>
                                            <span v-if="row.document_to_be_canceled > 0" class="d-inline-flex align-items-center mx-1">
                                                <el-tooltip content="Por anular" placement="top">
                                                    <el-badge :value="row.document_to_be_canceled" class="item" type="danger">
                                                        <i class="fas fa-exclamation-circle text-secondary"></i>
                                                    </el-badge>
                                                </el-tooltip>
                                                <el-button
                                                    type="text"
                                                    size="mini"
                                                    icon="el-icon-view"
                                                    class="p-0 ml-1 central-events-btn"
                                                    title="Ver listado"
                                                    @click.stop="openDocumentEventsModal(row, 'to_be_canceled')"
                                                />
                                            </span>
                                        </template>
                                        <span v-else class="text-muted small">Todo OK</span>
                                    </div>
                                </td>
                                <td v-if="columns.otras_notificaciones.visible" class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center align-items-center">
                                        <template v-if="row.document_rejected > 0 || row.document_observed > 0">
                                            <span v-if="row.document_rejected > 0" class="d-inline-flex align-items-center mx-1">
                                                <el-tooltip content="Rechazados" placement="top">
                                                    <el-badge :value="row.document_rejected" class="item" type="danger">
                                                        <i class="fas fa-ban text-secondary"></i>
                                                    </el-badge>
                                                </el-tooltip>
                                                <el-button
                                                    type="text"
                                                    size="mini"
                                                    icon="el-icon-view"
                                                    class="p-0 ml-1 central-events-btn"
                                                    title="Ver listado"
                                                    @click.stop="openDocumentEventsModal(row, 'rejected')"
                                                />
                                            </span>
                                            <span v-if="row.document_observed > 0" class="d-inline-flex align-items-center mx-1">
                                                <el-tooltip content="Observados" placement="top">
                                                    <el-badge :value="row.document_observed" class="item" type="warning">
                                                        <i class="fas fa-search text-secondary"></i>
                                                    </el-badge>
                                                </el-tooltip>
                                                <el-button
                                                    type="text"
                                                    size="mini"
                                                    icon="el-icon-view"
                                                    class="p-0 ml-1 central-events-btn"
                                                    title="Ver listado"
                                                    @click.stop="openDocumentEventsModal(row, 'observed')"
                                                />
                                            </span>
                                        </template>
                                        <span v-else class="text-muted">—</span>
                                    </div>
                                </td>
                                <td v-if="columns.inicio_ciclo.visible" class="text-center">
                                    <template v-if="row.start_billing_cycle">
                                        <span>{{ row.start_billing_cycle }}</span>
                                    </template>
                                    <template v-else>
                                        <el-date-picker
                                            v-model="row.select_date_billing"
                                            type="date"
                                            placeholder="..."
                                            value-format="yyyy-MM-dd"
                                            size="mini"
                                            @change="setStartBillingCycle($event, row.id)"
                                        />
                                    </template>
                                </td>
                                <td v-if="columns.comprobantes_ciclo.visible" class="text-center">
                                    <strong>
                                        <template v-if="row.sale_notes_quantity_if_include > 0">
                                            {{ (row.count_doc_month || 0) + (row.sale_notes_quantity_if_include || 0) }} /
                                        </template>
                                        <template v-else> {{ row.count_doc_month || 0 }} / </template>
                                        <template v-if="row.max_documents == 0"><i class="fas fa-infinity"></i></template>
                                        <template v-else>{{ row.max_documents }}</template>
                                    </strong>
                                </td>
                                <td v-if="columns.usuarios.visible" class="text-center">
                                    <template v-if="row.max_users !== 0 && row.count_user > row.max_users">
                                        <el-popover :content="text_limit_users" placement="top" trigger="hover" width="220">
                                            <strong slot="reference" class="text-danger">{{ row.count_user }}</strong>
                                        </el-popover>
                                    </template>
                                    <template v-else>
                                        <strong>{{ row.count_user }}</strong>
                                    </template>
                                    /
                                    <template v-if="row.max_users == 0"><i class="fas fa-infinity"></i></template>
                                    <template v-else>{{ row.max_users }}</template>
                                    <div
                                        v-if="hasDocumentsDateFilter && row.mh_users_created != null"
                                        class="small text-muted mt-1"
                                        title="Eventos registrados en central (altas/bajas de usuarios en el rango)"
                                    >
                                        Reg. central: +{{ row.mh_users_created }} / −{{ row.mh_users_deleted }}
                                    </div>
                                </td>
                                <td v-if="columns.sucursales.visible" class="text-center">
                                    <data-limit-notification
                                        entity_description="sucursales"
                                        :unlimited="row.establishments_unlimited"
                                        :quantity="row.quantity_establishments"
                                        :max_quantity="row.max_quantity_establishments"
                                    />
                                    <div
                                        v-if="hasDocumentsDateFilter && row.mh_establishments_created != null"
                                        class="small text-muted mt-1"
                                        title="Eventos registrados en central (altas/bajas de sucursales en el rango)"
                                    >
                                        Reg. central: +{{ row.mh_establishments_created }} / −{{ row.mh_establishments_deleted }}
                                    </div>
                                </td>
                                <td v-if="columns.ventas_mes.visible" class="text-center">
                                    <data-limit-notification
                                        entity_description="ventas"
                                        style_div="width: 150px !important"
                                        :unlimited="row.sales_unlimited"
                                        :quantity="row.monthly_sales_total"
                                        :max_quantity="row.max_sales_limit"
                                    />
                                </td>
                                <td v-if="columns.fecha_creacion.visible">{{ row.created_at }}</td>
                                <td v-if="columns.consultas_api.visible" class="text-center">{{ row.queries_to_apiperu }}</td>
                                <td v-if="columns.notas_venta.visible" class="text-center">{{ row.count_sales_notes }}</td>
                                <td v-if="columns.total_mes.visible" class="text-center">{{ row.current_count_doc_month }}</td>
                                <td v-if="columns.total_pse.visible" class="text-center">{{ row.count_doc_pse }}</td>
                                <td v-if="columns.total_notas.visible" class="text-center">
                                    <strong>{{ (row.count_doc_month || 0) + (row.count_sales_notes_month || 0) }}</strong>
                                </td>
                                <td v-if="columns.limitar_doc.visible" class="text-center">
                                    <el-switch v-model="row.locked_emission" @change="changeLockedEmission(row)" />
                                </td>
                                <td v-if="columns.limitar_usuarios.visible" class="text-center">
                                    <el-switch v-model="row.locked_users" @change="changeLockedUser(row)" />
                                </td>
                                <td v-if="columns.limitar_sucursales.visible" class="text-center">
                                    <el-switch v-model="row.locked_create_establishments" @change="changeLockedByColumn(row, 'locked_create_establishments')" />
                                </td>
                                <td v-if="columns.limitar_ventas.visible" class="text-center">
                                    <el-switch v-model="row.restrict_sales_limit" @change="changeLockedByColumn(row, 'restrict_sales_limit')" />
                                </td>
                                <td class="text-muted small">{{ row.metrics_last_synced_at }}</td>
                                <td class="text-center">
                                    <el-dropdown trigger="click" @command="handleCommand" placement="bottom-end">
                                        <el-button type="primary" plain size="mini">
                                            Acciones<i class="el-icon-arrow-down el-icon--right"></i>
                                        </el-button>
                                        <el-dropdown-menu slot="dropdown">
                                            <el-dropdown-item :command="{ action: 'edit', id: row.id }">Editar</el-dropdown-item>
                                            <el-dropdown-item :command="{ action: 'secretLogin', id: row.id }">Acceso maestro</el-dropdown-item>
                                            <el-dropdown-item v-if="String(row.soap_type) === '01'" :command="{ action: 'demoConfig', id: row.id }">Configurar demo</el-dropdown-item>
                                            <el-dropdown-item divided :command="{ action: 'payments', id: row.id }">Pagos</el-dropdown-item>
                                            <el-dropdown-item :command="{ action: 'documentPackages', id: row.id }">Paquetes de comprobantes</el-dropdown-item>
                                            <el-dropdown-item :command="{ action: 'accountStatus', id: row.id }">Estado de cuenta</el-dropdown-item>
                                            <template v-if="!row.locked">
                                                <el-dropdown-item divided :command="{ action: 'password', id: row.id }">Restablecer contraseña</el-dropdown-item>
                                                <el-dropdown-item v-if="deletePermission === true" class="text-danger" :command="{ action: 'delete', row }">
                                                    Eliminar cliente
                                                </el-dropdown-item>
                                            </template>
                                        </el-dropdown-menu>
                                    </el-dropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <nav class="d-flex flex-wrap align-items-center justify-content-between mt-3 pt-2 border-top">
                <div class="text-muted small mb-2 mb-md-0">
                    Página <strong>{{ meta.current_page }}</strong> de <strong>{{ meta.last_page }}</strong>
                </div>
                <ul class="pagination pagination-sm mb-2 mb-md-0">
                    <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
                        <a class="page-link" href="#" @click.prevent="loadPage(meta.current_page - 1)">Anterior</a>
                    </li>
                    <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
                        <a class="page-link" href="#" @click.prevent="loadPage(meta.current_page + 1)">Siguiente</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <label class="small text-muted mb-0 mr-2" for="cm-per-page">Por página</label>
                    <select id="cm-per-page" class="form-control form-control-sm" style="width: auto" :value="meta.per_page" @change="onPerPageChange">
                        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </nav>
        </div>

        <el-dialog
            :title="documentEventsDialogTitle"
            :visible.sync="documentEventsDialogVisible"
            width="92%"
            top="5vh"
            append-to-body
            destroy-on-close
            custom-class="central-document-events-dialog"
            @closed="onDocumentEventsDialogClosed"
        >
            <p class="text-muted small mb-2">
                Comprobantes según índice central (última sincronización:
                {{ documentEventsClient && documentEventsClient.metrics_last_synced_at ? documentEventsClient.metrics_last_synced_at : '—' }}).
            </p>
            <el-table v-loading="documentEventsLoading" :data="documentEventsRows" border stripe size="small" max-height="420" empty-text="Sin registros">
                <el-table-column prop="tenant_document_id" label="ID (tenant)" width="110" align="center" />
                <el-table-column prop="date_of_issue" label="F. emisión" width="100" align="center" />
                <el-table-column prop="document_type_description" label="Tipo" min-width="120" show-overflow-tooltip />
                <el-table-column prop="state_type_description" label="Estado" min-width="110" show-overflow-tooltip />
                <el-table-column label="Rectif. envío" width="95" align="center">
                    <template slot-scope="{ row }">{{ row.regularize_shipping ? 'Sí' : 'No' }}</template>
                </el-table-column>
                <el-table-column label="PSE" width="60" align="center">
                    <template slot-scope="{ row }">{{ row.send_to_pse ? 'Sí' : 'No' }}</template>
                </el-table-column>
                <el-table-column prop="currency_type_id" label="Mon." width="70" align="center" />
                <el-table-column prop="total" label="Total" width="100" align="right" />
            </el-table>
            <div v-if="documentEventsMeta.total > 0" class="mt-3 text-right">
                <el-pagination
                    small
                    background
                    layout="prev, pager, next, total"
                    :page-size="documentEventsMeta.per_page"
                    :current-page="documentEventsMeta.current_page"
                    :total="documentEventsMeta.total"
                    @current-change="fetchDocumentEvents"
                />
            </div>
        </el-dialog>

        <system-clients-form :recordId="recordId" :showDialog.sync="showDialog" />

        <client-payments :clientId="recordId" :showDialog.sync="showDialogPayments" />

        <account-status :clientId="recordId" :showDialog.sync="showDialogAccountStatus" />

        <client-delete :record="record" :showDialog.sync="showDialogDelete" />

        <demo-configuration :clientId="recordId" :showDialog.sync="showDemoConfiguration" />
    </div>
</template>

<script>
import { changeable } from '../../../mixins/changeable';
import ClientPayments from './partials/payments.vue';
import DemoConfiguration from './partials/demo_configuration.vue';
import AccountStatus from './partials/account_status.vue';
import ClientDelete from './partials/delete.vue';
import DataLimitNotification from './partials/DataLimitNotification.vue';

const COLUMN_VISIBILITY_STORAGE_KEY = 'columnVisibilityClientsCentralMetrics';

const DOCUMENT_EVENT_KIND_TITLES = {
    not_sent: 'Por enviar / borrador',
    regularize_shipping: 'Rectificación de envío',
    to_be_canceled: 'Por anular',
    rejected: 'Rechazados',
    observed: 'Observados',
};

/** Mismas claves y visibilidad por defecto que `clients/index.vue` (Hostname siempre visible, no va en el menú). */
function defaultColumnsState() {
    return {
        nombre: { title: 'Nombre', visible: true },
        ruc: { title: 'RUC', visible: true },
        plan: { title: 'Plan', visible: true },
        correo: { title: 'Correo', visible: true },
        entorno: { title: 'Entorno', visible: true },
        total_comprobantes: { title: 'Total de Comprobantes', visible: false },
        notificaciones: { title: 'Notificaciones', visible: true },
        otras_notificaciones: { title: 'Otras Notificaciones', visible: true },
        inicio_ciclo: { title: 'Inicio Ciclo Facturacion', visible: true },
        comprobantes_ciclo: { title: 'Comprobantes Ciclo Facturación', visible: true },
        usuarios: { title: 'Usuarios', visible: true },
        sucursales: { title: 'Sucursales', visible: false },
        ventas_mes: { title: 'Ventas (Mes)', visible: false },
        fecha_creacion: { title: 'F.Creación', visible: true },
        consultas_api: { title: 'Consultas API Peru (mes)', visible: false },
        notas_venta: { title: 'Cant.Notas de venta', visible: false },
        total_mes: { title: 'Total (Comprobantes por mes)', visible: false },
        total_pse: { title: 'Total (Comprobantes a PSE-GIOR)', visible: false },
        total_notas: { title: 'Total (Comprobantes notas de venta)', visible: false },
        bloquear_cuenta: { title: 'Bloquear cuenta', visible: true },
        limitar_doc: { title: 'Limitar Doc.', visible: false },
        limitar_usuarios: { title: 'Limitar Usuarios', visible: false },
        limitar_sucursales: { title: 'Limitar Sucursales', visible: false },
        limitar_ventas: { title: 'Limitar Ventas (Mes)', visible: false },
    };
}

export default {
    mixins: [changeable],
    components: {
        ClientPayments,
        AccountStatus,
        ClientDelete,
        DemoConfiguration,
        DataLimitNotification,
    },
    props: {
        plans: { type: Array, default: () => [] },
        deletePermission: { type: [Boolean, Number], default: false },
    },
    data() {
        return {
            listResource: 'clientes-metricas-central',
            clientsResource: 'clients',
            loading: false,
            records: [],
            meta: {
                current_page: 1,
                per_page: 25,
                total: 0,
                last_page: 1,
            },
            filters: {
                search: '',
                plan_id: '',
                soap_type: null,
                locked_tenant: null,
                dateRange: null,
            },
            columns: defaultColumnsState(),
            showDialog: false,
            showDialogPayments: false,
            showDialogAccountStatus: false,
            showDialogDelete: false,
            showDemoConfiguration: false,
            recordId: null,
            record: {},
            text_limit_doc: 'El límite de comprobantes fue superado',
            text_limit_users: 'El límite de usuarios fue superado',
            tableScrollActive: false,
            perPageOptions: [10, 25, 50, 100],
            documentEventsDialogVisible: false,
            documentEventsClient: null,
            documentEventsKind: '',
            documentEventsRows: [],
            documentEventsLoading: false,
            documentEventsMeta: {
                current_page: 1,
                last_page: 1,
                per_page: 25,
                total: 0,
            },
        };
    },
    computed: {
        documentEventsDialogTitle() {
            const label = DOCUMENT_EVENT_KIND_TITLES[this.documentEventsKind] || 'Comprobantes';
            if (!this.documentEventsClient) {
                return label;
            }
            const name = this.documentEventsClient.name || '';
            const host = this.documentEventsClient.hostname || '';
            return `${label} — ${name} (${host})`;
        },
        columnsComputed() {
            return this.columns;
        },
        hasDocumentsDateFilter() {
            return !!(this.filters.dateRange && this.filters.dateRange.length === 2);
        },
        visibleColCount() {
            let n = 2;
            Object.keys(this.columns).forEach((k) => {
                const c = this.columns[k];
                if (c && c.visible) {
                    n += 1;
                }
            });
            n += 2;
            return Math.max(n, 4);
        },
    },
    mounted() {
        this.loadColumnVisibility();
        this.reload();
        this.$eventHub.$on('reloadData', this.onReloadData);
    },
    beforeDestroy() {
        this.$eventHub.$off('reloadData', this.onReloadData);
    },
    methods: {
        openDocumentEventsModal(row, kind) {
            this.documentEventsClient = row;
            this.documentEventsKind = kind;
            this.documentEventsDialogVisible = true;
            this.documentEventsRows = [];
            this.documentEventsMeta = {
                current_page: 1,
                last_page: 1,
                per_page: 25,
                total: 0,
            };
            this.fetchDocumentEvents(1);
        },
        fetchDocumentEvents(page = 1) {
            if (!this.documentEventsClient || !this.documentEventsKind) {
                return;
            }
            this.documentEventsLoading = true;
            const params = {
                client_id: this.documentEventsClient.id,
                kind: this.documentEventsKind,
                page,
                per_page: this.documentEventsMeta.per_page,
            };
            if (this.filters.dateRange && this.filters.dateRange.length === 2) {
                params.documents_date_start = this.filters.dateRange[0];
                params.documents_date_end = this.filters.dateRange[1];
            }
            this.$http
                .get(`/${this.listResource}/document-events`, {
                    params,
                })
                .then((res) => {
                    this.documentEventsRows = res.data.data || [];
                    if (res.data.meta) {
                        this.documentEventsMeta = {
                            ...this.documentEventsMeta,
                            ...res.data.meta,
                        };
                    }
                })
                .catch(() => {
                    this.$message.error('No se pudo cargar el listado de comprobantes');
                })
                .finally(() => {
                    this.documentEventsLoading = false;
                });
        },
        onDocumentEventsDialogClosed() {
            this.documentEventsClient = null;
            this.documentEventsKind = '';
            this.documentEventsRows = [];
        },
        onTableScroll(e) {
            this.tableScrollActive = e.target.scrollLeft > 2;
        },
        onPerPageChange(e) {
            const v = parseInt(e.target.value, 10);
            if (!Number.isNaN(v)) {
                this.changePerPage(v);
            }
        },
        indexMethod(index) {
            return (this.meta.current_page - 1) * this.meta.per_page + index + 1;
        },
        loadColumnVisibility() {
            const saved = localStorage.getItem(COLUMN_VISIBILITY_STORAGE_KEY);
            if (!saved) {
                return;
            }
            try {
                const parsed = JSON.parse(saved);
                const defaults = defaultColumnsState();
                Object.keys(defaults).forEach((key) => {
                    if (!parsed[key]) {
                        this.$set(this.columns, key, { ...defaults[key] });
                    } else {
                        this.$set(this.columns, key, {
                            ...defaults[key],
                            visible: parsed[key].visible !== undefined ? parsed[key].visible : defaults[key].visible,
                        });
                    }
                });
                if (parsed.hostname !== undefined) {
                    this.saveColumnVisibility();
                }
            } catch (e) {
                console.error('Error cargando visibilidad de columnas:', e);
            }
        },
        saveColumnVisibility() {
            localStorage.setItem(COLUMN_VISIBILITY_STORAGE_KEY, JSON.stringify(this.columns));
        },
        entornoLabel(soap) {
            const m = { '01': 'Demo', '02': 'Producción', '03': 'Interno' };
            return m[String(soap)] || soap || '—';
        },
        onReloadData() {
            this.fetch();
        },
        onDateChange() {
            this.meta.current_page = 1;
            this.fetch();
        },
        reload() {
            this.meta.current_page = 1;
            this.fetch();
        },
        loadPage(page) {
            if (page < 1 || page > this.meta.last_page) {
                return;
            }
            this.meta.current_page = page;
            this.fetch();
        },
        changePerPage(size) {
            this.meta.per_page = size;
            this.meta.current_page = 1;
            this.fetch();
        },
        fetch() {
            this.loading = true;
            const params = {
                page: this.meta.current_page,
                per_page: this.meta.per_page,
            };
            if (this.filters.search) {
                params.search = this.filters.search;
            }
            if (this.filters.plan_id !== '' && this.filters.plan_id != null) {
                params.plan_id = this.filters.plan_id;
            }
            if (this.filters.soap_type) {
                params.soap_type = this.filters.soap_type;
            }
            if (this.filters.locked_tenant === 0 || this.filters.locked_tenant === 1) {
                params.locked_tenant = this.filters.locked_tenant;
            }
            if (this.filters.dateRange && this.filters.dateRange.length === 2) {
                params.documents_date_start = this.filters.dateRange[0];
                params.documents_date_end = this.filters.dateRange[1];
            }
            this.$http
                .get(`/${this.listResource}/records`, { params })
                .then((res) => {
                    const rows = res.data.data || [];
                    rows.forEach((r) => {
                        if (r.select_date_billing === undefined || r.select_date_billing === null) {
                            this.$set(r, 'select_date_billing', '');
                        }
                    });
                    this.records = rows;
                    if (res.data.meta) {
                        this.meta = { ...this.meta, ...res.data.meta };
                    }
                })
                .catch(() => {
                    this.$message.error('No se pudieron cargar los datos');
                })
                .finally(() => {
                    this.loading = false;
                    this.$nextTick(() => {
                        this.tableScrollActive = false;
                    });
                });
        },
        changeLockedTenant(row) {
            this.$http
                .post(`/${this.clientsResource}/locked_tenant`, row)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit('reloadData');
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => {
                    if (error.response && error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    }
                });
        },
        changeLockedUser(row) {
            this.$http
                .post(`/${this.clientsResource}/locked_user`, row)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit('reloadData');
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => {
                    if (error.response && error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    }
                });
        },
        changeLockedEmission(row) {
            this.$http
                .post(`/${this.clientsResource}/locked_emission`, row)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit('reloadData');
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => {
                    if (error.response && error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    }
                });
        },
        changeLockedByColumn(row, column) {
            const params = { ...row, column };
            this.$http.post(`/${this.clientsResource}/locked-by-column`, params).then((response) => {
                if (response.data.success) {
                    this.$message.success(response.data.message);
                    this.$eventHub.$emit('reloadData');
                } else {
                    this.$message.error(response.data.message);
                }
            });
        },
        setStartBillingCycle(event, id) {
            this.$http
                .post(`/${this.clientsResource}/set_billing_cycle`, {
                    id,
                    start_billing_cycle: event,
                })
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => {
                    if (error.response && error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    }
                })
                .then(() => {
                    this.$eventHub.$emit('reloadData');
                });
        },
        handleCommand(command) {
            switch (command.action) {
                case 'password':
                    this.clickPassword(command.id);
                    break;
                case 'delete':
                    this.clickDelete(command.row);
                    break;
                case 'edit':
                    this.clickEdit(command.id);
                    break;
                case 'demoConfig':
                    this.clickDemoConfiguration(command.id);
                    break;
                case 'secretLogin':
                    this.clickSecretLogin(command.id);
                    break;
                case 'payments':
                    this.clickPayments(command.id);
                    break;
                case 'documentPackages':
                    this.clickDocumentPackages(command.id);
                    break;
                case 'accountStatus':
                    this.clickAccountStatus(command.id);
                    break;
                default:
                    break;
            }
        },
        clickCreate() {
            this.recordId = null;
            this.showDialog = true;
        },
        clickPassword(id) {
            this.change(`/${this.clientsResource}/password/${id}`);
        },
        clickDelete(row) {
            this.record = row;
            this.showDialogDelete = true;
        },
        clickEdit(recordId) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        clickDemoConfiguration(recordId) {
            this.recordId = recordId;
            this.showDemoConfiguration = true;
        },
        clickSecretLogin(clientId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/secret-login`;
            form.target = '_blank';
            form.style.display = 'none';
            const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
            const clientIdInput = document.createElement('input');
            clientIdInput.type = 'hidden';
            clientIdInput.name = 'client_id';
            clientIdInput.value = clientId;
            form.appendChild(clientIdInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },
        clickPayments(recordId) {
            this.recordId = recordId;
            this.showDialogPayments = true;
        },
        clickDocumentPackages(clientId) {
            window.location.href = `/document-packages?client_id=${clientId}`;
        },
        clickAccountStatus(recordId) {
            this.recordId = recordId;
            this.showDialogAccountStatus = true;
        },
    },
};
</script>

<style scoped>
.central-events-btn {
    min-width: 1.25rem !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
    color: #0088cc !important;
}
.hostname-link {
    color: #0088cc;
}
.hostname-link:hover {
    color: #006699;
    text-decoration: underline;
}
.central-table-shell {
    max-height: min(560px, calc(100vh - 280px));
}
.central-table-scroll {
    max-height: min(560px, calc(100vh - 280px));
    overflow: auto;
}
.central-metrics-table.table th,
.central-metrics-table.table td {
    white-space: nowrap;
    vertical-align: middle !important;
}
.central-metrics-page .thead-light th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 600;
    color: #5c636a;
    border-bottom-width: 2px;
}
th.sticky-column,
td.sticky-column {
    position: sticky;
    left: 0;
    background-color: #fff !important;
    z-index: 2;
    box-shadow: 1px 0 0 rgba(0, 0, 0, 0.06);
}
th.sticky-column {
    z-index: 3;
}
.table-striped tbody tr:nth-of-type(odd) td.sticky-column {
    background-color: #f9f9f9 !important;
}
.table-striped tbody tr:nth-of-type(even) td.sticky-column {
    background-color: #fff !important;
}
tbody tr:hover td.sticky-column {
    background-color: #f1f7fc !important;
}
th.sticky-column.scroll-active::after,
td.sticky-column.scroll-active::after {
    opacity: 1;
    transition: opacity 0.2s ease;
}
th.sticky-column::after,
td.sticky-column::after {
    content: '';
    position: absolute;
    top: 0;
    right: -6px;
    width: 6px;
    height: 100%;
    background: linear-gradient(to right, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.central-loading-overlay {
    position: absolute;
    inset: 0;
    z-index: 10;
    background: rgba(255, 255, 255, 0.85);
    border-radius: 0.25rem;
}
.filters-panel .el-input,
.filters-panel .el-date-editor.el-input__inner,
.filters-panel .el-date-editor--daterange,
.filters-panel .central-filter-daterange.el-date-editor {
    width: 100% !important;
}
.filters-panel .central-filter-select.el-select {
    display: block;
    width: 100% !important;
}
.filters-panel .central-filter-select .el-input {
    width: 100% !important;
}
</style>

<style>
/* Menú Element UI: mismo patrón que clients-columns-dropdown-menu en index.vue */
.central-columns-dropdown-menu {
    max-height: 80vh;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    min-width: 220px;
}
.central-columns-dropdown-menu::-webkit-scrollbar {
    width: 6px;
}
.central-columns-dropdown-menu .el-dropdown-menu__item {
    padding: 4px 12px;
    line-height: 1.4;
}
.central-columns-dropdown-menu .el-dropdown-menu__item:focus {
    background-color: transparent;
}
</style>
