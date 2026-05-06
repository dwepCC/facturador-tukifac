@extends('system.layouts.app')

@section('content')

    <div class="card mb-4">
        <div class="card-header bg-info bg-info-customer-admin d-flex justify-content-between align-items-center">
            <span>Pagos por aprobar</span>
            <span class="badge badge-light" id="pending-approvals-count">0 pendientes</span>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div class="mb-2">
                    <h5 class="card-title mb-0">Pagos pendientes de aprobación</h5>
                    <small class="text-muted">Revisa el comprobante y aprueba o rechaza para actualizar el estado del cliente.</small>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <span class="text-muted me-2">Por página</span>
                    <select id="pending-approvals-per-page" class="form-control form-control-sm" style="width: 110px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="pending-approvals-refresh" title="Actualizar">
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Cliente</th>
                            <th width="12%">RUC/DNI</th>
                            <th width="12%">Fecha de Pago</th>
                            <th width="10%">Monto</th>
                            <th width="16%">Actualizado</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="pending-approvals-tbody">
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 mb-0">Cargando pagos pendientes...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <div class="text-muted small" id="pending-approvals-pagination-info"></div>
                <nav aria-label="Paginación pagos pendientes">
                    <ul class="pagination pagination-sm mb-0" id="pending-approvals-pagination"></ul>
                </nav>
            </div>

            <div class="modal fade" id="paymentDetailModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header pending-voucher-modal-header">
                            <h5 class="modal-title">Comprobante de Pago</h5>
                            <button type="button" class="close pending-voucher-modal-close" data-dismiss="modal" onclick="closeModal()">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="payment-voucher-img" src="" alt="Comprobante de pago" class="img-fluid" style="max-height: 70vh; cursor: zoom-in;" onclick="openImageInNewTab()">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cerrar</button>
                            <button type="button" class="btn btn-primary" onclick="openImageInNewTab()">
                                <i class="fa fa-external-link"></i> Abrir en nueva pestaña
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <system-payments-index></system-payments-index>

@endsection

@push('styles')
    <style>
        #pending-approvals-tbody td {
            vertical-align: middle;
        }

        .pending-client-info {
            font-weight: 600;
        }

        .pending-amount {
            font-size: 1.05em;
            font-weight: 800;
            color: #28a745;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .pending-voucher-modal-header {
            position: relative;
            padding-right: 3.25rem;
        }

        .pending-voucher-modal-close {
            position: absolute;
            right: 1rem;
            top: 0.75rem;
            margin: 0;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentVoucherUrl = '';
        let pendingApprovalsState = { page: 1, per_page: 10 };

        function formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('es-PE', { year: 'numeric', month: '2-digit', day: '2-digit' });
        }

        function formatDateTime(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('es-PE', {
                year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'
            });
        }

        function showLoading(message = 'Procesando...') {
            $('body').append(`
                <div class="loading-overlay">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">${message}</p>
                </div>
            `);
        }

        function hideLoading() {
            $('.loading-overlay').remove();
        }

        function showPaymentVoucher(voucherUrl) {
            if (!voucherUrl) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Comprobante no disponible',
                    text: 'No hay comprobante disponible para este pago',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            currentVoucherUrl = voucherUrl;
            $('#payment-voucher-img').attr('src', voucherUrl);

            $('#paymentDetailModal').css('display', 'block');
            $('#paymentDetailModal').addClass('show');
            $('body').addClass('modal-open');
            if ($('.modal-backdrop').length === 0) {
                $('body').append('<div class="modal-backdrop fade show"></div>');
            }
        }

        function openImageInNewTab() {
            if (currentVoucherUrl) {
                window.open(currentVoucherUrl, '_blank');
            }
        }

        function closeModal() {
            $('#paymentDetailModal').removeClass('show');
            $('#paymentDetailModal').css('display', 'none');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        }

        function renderPendingApprovalsPagination(pagination) {
            const ul = $('#pending-approvals-pagination');
            ul.empty();

            if (!pagination || pagination.last_page <= 1) {
                return;
            }

            const current = pagination.current_page;
            const last = pagination.last_page;
            const start = Math.max(1, current - 2);
            const end = Math.min(last, current + 2);

            const addItem = (label, page, disabled, active) => {
                const li = $('<li/>', { class: `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}` });
                const a = $('<a/>', { class: 'page-link', href: '#', text: label });
                a.on('click', function (e) {
                    e.preventDefault();
                    if (disabled || active) return;
                    loadPendingApprovals(page);
                });
                li.append(a);
                ul.append(li);
            };

            addItem('«', Math.max(1, current - 1), current === 1, false);
            for (let p = start; p <= end; p++) {
                addItem(String(p), p, false, p === current);
            }
            addItem('»', Math.min(last, current + 1), current === last, false);
        }

        function renderPendingApprovalsInfo(pagination) {
            const info = $('#pending-approvals-pagination-info');
            if (!pagination || pagination.total === 0) {
                info.text('');
                return;
            }
            info.text(`Mostrando ${pagination.from} - ${pagination.to} de ${pagination.total}`);
        }

        function loadPendingApprovals(page = 1) {
            pendingApprovalsState.page = page;

            $.ajax({
                url: "{{ url('clients/records/list') }}",
                method: 'GET',
                dataType: 'JSON',
                data: {
                    page: pendingApprovalsState.page,
                    per_page: pendingApprovalsState.per_page
                },
                success: function (data) {
                    const tbody = $('#pending-approvals-tbody');
                    tbody.empty();

                    const pagination = data.pagination || { total: 0, current_page: 1, last_page: 1 };
                    $('#pending-approvals-count').text(`${pagination.total || 0} pendientes`);

                    renderPendingApprovalsPagination(pagination);
                    renderPendingApprovalsInfo(pagination);

                    if (data.records && data.records.length > 0) {
                        data.records.forEach(function (payment, index) {
                            const hasReference = payment.reference && String(payment.reference).trim() !== '';
                            const rowIndex = (pagination.from ? (pagination.from + index) : (index + 1));
                            const hostnameBlock = payment.hostname ? `
                                <br>
                                <small class="text-info">
                                    <i class="fa fa-globe mr-1"></i>
                                    <a href="http://${payment.hostname}" target="_blank" rel="noopener">${payment.hostname}</a>
                                </small>
                            ` : '';

                            const actionButtons = hasReference ? `
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-info"
                                        onclick='showPaymentVoucher(${JSON.stringify(payment.reference || "")})' title="Ver comprobante">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="approvePayment(${payment.id})" title="Aprobar pago">
                                        <i class="fa fa-check"></i> Aprobar
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="rejectPayment(${payment.id})" title="Rechazar pago">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            ` : `
                                <span class="badge badge-warning">
                                    <i class="fa fa-clock"></i> Pendiente de pago
                                </span>
                            `;

                            tbody.append(`
                                <tr>
                                    <td>${rowIndex}</td>
                                    <td>
                                        <div class="pending-client-info">
                                            <i class="fa fa-building mr-1"></i>${payment.client_name || 'N/A'}
                                        </div>
                                        <small class="text-muted">
                                            <i class="fa fa-envelope mr-1"></i>${payment.email || 'N/A'}
                                        </small>
                                        ${hostnameBlock}
                                    </td>
                                    <td>${payment.client_ruc || 'N/A'}</td>
                                    <td>${formatDate(payment.date_of_payment)}</td>
                                    <td class="pending-amount">S/ ${parseFloat(payment.payment || 0).toFixed(2)}</td>
                                    <td><small>${formatDateTime(payment.updated_at)}</small></td>
                                    <td>${actionButtons}</td>
                                </tr>
                            `);
                        });
                    } else {
                        tbody.append(`
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fa fa-check-circle text-success fa-3x mb-3"></i>
                                    <h5 class="text-success">¡Excelente!</h5>
                                    <p class="text-muted mb-0">No hay pagos pendientes de aprobación en este momento.</p>
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function () {
                    const tbody = $('#pending-approvals-tbody');
                    tbody.html(`
                        <tr>
                            <td colspan="7" class="text-center text-danger py-4">
                                <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                                <h5>Error al cargar los pagos</h5>
                                <button class="btn btn-primary mt-2" onclick="loadPendingApprovals(pendingApprovalsState.page)">
                                    <i class="fa fa-refresh"></i> Reintentar
                                </button>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function approvePayment(paymentId) {
            Swal.fire({
                title: 'Subir comprobante (PDF)',
                text: 'Adjunte la boleta/nota de venta en PDF para aprobar este pago.',
                icon: 'info',
                input: 'file',
                inputAttributes: {
                    accept: 'application/pdf'
                },
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Aprobar',
                cancelButtonText: 'Cancelar'
                ,
                preConfirm: (file) => {
                    if (!file) {
                        Swal.showValidationMessage('Debe seleccionar un archivo PDF.');
                        return false;
                    }
                    if (!String(file.name || '').toLowerCase().endsWith('.pdf')) {
                        Swal.showValidationMessage('El archivo debe ser un PDF.');
                        return false;
                    }
                    return file;
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                showLoading('Aprobando pago...');
                const formData = new FormData();
                formData.append('_token', "{{ csrf_token() }}");
                formData.append('receipt_pdf', result.value);
                $.ajax({
                    url: "{{ url('clients/payment/approve') }}/" + paymentId,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        hideLoading();
                        if (response.success) {
                            Swal.fire({ icon: 'success', title: '¡Aprobado!', text: response.message, confirmButtonText: 'Aceptar' });
                            loadPendingApprovals(pendingApprovalsState.page);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonText: 'Aceptar' });
                        }
                    },
                    error: function (xhr) {
                        hideLoading();
                        let errorMessage = 'Error al aprobar el pago. Intente nuevamente.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'Error', text: errorMessage, confirmButtonText: 'Aceptar' });
                    }
                });
            });
        }

        function rejectPayment(paymentId) {
            const defaultReason = 'Su pago fue rechazado. Por favor, vuelva a enviar un comprobante válido.';
            Swal.fire({
                title: 'Rechazar pago',
                text: 'Puedes editar el mensaje que verá el tenant.',
                icon: 'warning',
                input: 'textarea',
                inputValue: defaultReason,
                inputPlaceholder: defaultReason,
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Rechazar',
                cancelButtonText: 'Cancelar',
                preConfirm: (value) => {
                    const reason = String(value || '').trim();
                    if (reason.length > 255) {
                        Swal.showValidationMessage('El mensaje es demasiado largo (máx 255 caracteres).');
                        return false;
                    }
                    return reason || defaultReason;
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                showLoading('Rechazando pago...');
                $.ajax({
                    url: "{{ url('clients/payment/reject') }}/" + paymentId,
                    method: 'POST',
                    data: { _token: "{{ csrf_token() }}", reason: result.value },
                    success: function (response) {
                        hideLoading();
                        if (response.success) {
                            Swal.fire({ icon: 'success', title: '¡Rechazado!', text: response.message, confirmButtonText: 'Aceptar' });
                            loadPendingApprovals(pendingApprovalsState.page);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message, confirmButtonText: 'Aceptar' });
                        }
                    },
                    error: function (xhr) {
                        hideLoading();
                        let errorMessage = 'Error al rechazar el pago. Intente nuevamente.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'Error', text: errorMessage, confirmButtonText: 'Aceptar' });
                    }
                });
            });
        }

        $(document).ready(function () {
            $('#pending-approvals-per-page').on('change', function () {
                pendingApprovalsState.per_page = parseInt($(this).val(), 10) || 10;
                loadPendingApprovals(1);
            });

            $('#pending-approvals-refresh').on('click', function () {
                loadPendingApprovals(pendingApprovalsState.page);
            });

            loadPendingApprovals(1);
            setInterval(function () {
                loadPendingApprovals(pendingApprovalsState.page);
            }, 30000);
        });
    </script>
@endpush
