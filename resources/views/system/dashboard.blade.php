@extends('system.layouts.app')

@section('content')

    <system-clients-index :delete-permission="{{json_encode($delete_permission)}}"
                          :disc-used="{{json_encode($disc_used)}}"
                          :i-used="{{json_encode($i_used)}}"
                          :storage-size="{{json_encode($storage_size)}}"
                          :version="{{json_encode($version)}}"></system-clients-index>
    <div class="card">
            <div class="card-header bg-info bg-info-customer-admin">
                <span>Listado de Pagos Pendientes de Aprobación</span>
                <span class="badge badge-light" id="pending-count">0 pendientes</span>
            </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="card-title">Pagos en espera de confirmación</h5>
                    <p class="text-muted">Estos pagos requieren tu revisión y aprobación</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">Cliente</th>
                            <th width="15%">RUC/DNI</th>
                            <th width="15%">Fecha de Pago</th>
                            <th width="10%">Monto</th>
                            <th width="15%">Actualizado</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="payments-table-body">
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando pagos pendientes...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Modal para comprobante de pago -->
            <div class="modal fade" id="paymentDetailModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Comprobante de Pago</h5>
                            <button type="button" class="close" data-dismiss="modal" onclick="closeModal()">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center" id="payment-detail-content">
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
@endsection

@push('styles')
<style>
    .table td {
        vertical-align: middle;
    }
    .client-info {
        font-weight: 500;
    }
    .payment-amount {
        font-size: 1.1em;
        font-weight: bold;
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
    #payment-voucher-img {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px;
    }
    .modal {
        z-index: 10000 !important;
    }
    .modal-backdrop {
        z-index: 9999 !important;
    }
    .modal-dialog {
        z-index: 10001 !important;
        margin: 1.75rem auto !important;
    }
    .modal-content {
        background: white !important;
        border: 2px solid #000 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let currentVoucherUrl = '';

    function loadPendingPayments() {
        $.ajax({
            url: "{{ url('clients/records/list') }}",
            method: 'GET',
            dataType: 'JSON',
            success: function (data) {
               // console.log(data.records);
                const tbody = $('#payments-table-body');
                tbody.empty();
                
                if (data.records && data.records.length > 0) {
                    $('#pending-count').text(data.records.length + ' pendientes');
                    
                    data.records.forEach(function(payment, index) {
                        const hasReference = payment.reference && payment.reference.trim() !== '';
                        
                        const actionButtons = hasReference ? `
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" 
                                    class="btn btn-outline-info"
                                    onclick='showPaymentVoucher(${JSON.stringify(payment.reference || "")})'
                                    title="Ver comprobante">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" 
                                    class="btn btn-success"
                                    onclick="approvePayment(${payment.id})"
                                    title="Aprobar pago">
                                    <i class="fa fa-check"></i> Aprobar
                                </button>
                                <button type="button" 
                                    class="btn btn-danger"
                                    onclick="rejectPayment(${payment.id})"
                                    title="Rechazar pago">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        ` : `
                            <span class="badge badge-warning">
                                <i class="fa fa-clock"></i> Pendiente de pago
                            </span>
                        `;

                        const row = `
                            <tr>
                                <td>${index + 1}</td>
                                <td>
                                    <div class="client-info">
                                        <i class="fa fa-building mr-1"></i>${payment.client_name || 'N/A'}
                                    </div>
                                    <small class="text-muted">
                                        <i class="fa fa-envelope mr-1"></i>${payment.email || 'N/A'}
                                    </small>
                                    ${payment.hostname ? `
                                    <br>
                                    <small class="text-info">
                                        <i class="fa fa-globe mr-1"></i>
                                        <a href="http://${payment.hostname}" target="_blank">${payment.hostname}</a>
                                    </small>
                                    ` : ''}
                                </td>
                                <td>${payment.client_ruc || 'N/A'}</td>
                                <td>${formatDate(payment.date_of_payment)}</td>
                                <td class="payment-amount">S/ ${parseFloat(payment.payment).toFixed(2)}</td>
                                <td>
                                    <small>${formatDateTime(payment.updated_at)}</small>
                                </td>
                                <td>
                                    ${actionButtons}
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    $('#pending-count').text('0 pendientes');
                    tbody.append(`
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fa fa-check-circle text-success fa-3x mb-3"></i>
                                <h5 class="text-success">¡Excelente!</h5>
                                <p class="text-muted">No hay pagos pendientes de aprobación en este momento.</p>
                            </td>
                        </tr>
                    `);
                }
            },
            error: function (xhr, status, error) {
                const tbody = $('#payments-table-body');
                tbody.html(`
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>Error al cargar los pagos</h5>
                            <button class="btn btn-primary mt-2" onclick="loadPendingPayments()">
                                <i class="fa fa-refresh"></i> Reintentar
                            </button>
                        </td>
                    </tr>
                `);
            }
        });
    }

    function showPaymentVoucher(voucherUrl) {
        //console.log('URL del comprobante:', voucherUrl);
        
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
        
        // Forzar display del modal
        $('#paymentDetailModal').css('display', 'block');
        $('#paymentDetailModal').addClass('show');
        $('body').addClass('modal-open');
        
        // Agregar backdrop si no existe
        if ($('.modal-backdrop').length === 0) {
            $('body').append('<div class="modal-backdrop fade show"></div>');
        }
        
       // console.log('Modal debería estar visible');
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

    function formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('es-PE', {
            year: 'numeric', month: '2-digit', day: '2-digit'
        });
    }

    function formatDateTime(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('es-PE', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function approvePayment(paymentId) {
        Swal.fire({
            title: '¿Confirmar aprobación?',
            text: "¿Está seguro de que desea APROBAR este pago? Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Aprobando pago...');
                $.ajax({
                    url: "{{ url('clients/payment/approve') }}/" + paymentId,
                    method: 'POST',
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        hideLoading();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Aprobado!',
                                text: response.message,
                                confirmButtonText: 'Aceptar'
                            });
                            loadPendingPayments();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    },
                    error: function (xhr) {
                        hideLoading();
                        let errorMessage = 'Error al aprobar el pago. Intente nuevamente.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
        });
    }

    function rejectPayment(paymentId) {
        Swal.fire({
            title: '¿Confirmar anulación?',
            text: "¿Está seguro de que desea ANULAR este pago? Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Anulando pago...');
                $.ajax({
                    url: "{{ url('clients/payment/reject') }}/" + paymentId,
                    method: 'POST',
                    data: { 
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        hideLoading();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Anulado!',
                                text: response.message,
                                confirmButtonText: 'Aceptar'
                            });
                            loadPendingPayments();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    },
                    error: function (xhr) {
                        hideLoading();
                        let errorMessage = 'Error al anular el pago. Intente nuevamente.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            }
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

    $(document).ready(function() {
        loadPendingPayments();
        setInterval(loadPendingPayments, 30000);
    });
</script>
@endpush