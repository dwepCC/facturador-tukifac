@extends('tenant.layouts.app')

@section('content')
{{--<tenant-account-payment-index></tenant-account-payment-index>--}}
  <div>
    <div class="page-header pr-0">
      <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
      <ol class="breadcrumbs">
          <li class="active"><span>Pagos</span></li>
      </ol>
      <div class="right-wrapper pull-right">
          <template>
              <a type="button" class="btn btn-custom btn-sm  mt-2 mr-2" href="/cuenta/configuration"><i class="fas fa-cogs"></i> Configuración</a>
          </template>
      </div>
    </div>
    <div class="card tab-content-default row-new">
      <div class="card-body">
        <div class="row">
          <div class="col"></div>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr width="100%">
                <th width="5%">#</th>
                <th>Fecha de pago</th>
                <th>Fecha real de pago</th>
                <th>Comentario</th>
                <th class="text-center">Monto</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="payments-table-body">
              <tr>
                <td colspan="7" class="text-center">Cargando pagos...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div id="customPaymentModal" class="custom-modal" role="dialog" aria-modal="true" aria-labelledby="tukpayModalTitle">
    <div class="custom-modal-content tukpay-modal">
      <div class="custom-modal-header tukpay-header">
        <div class="tukpay-header-left">
          <div class="tukpay-header-icon">
            <i class="fas fa-receipt"></i>
          </div>
          <div>
            <div class="tukpay-title" id="tukpayModalTitle">Cargar comprobante</div>
            <div class="tukpay-subtitle">Pago manual por transferencia o Yape</div>
          </div>
        </div>
        <button type="button" class="tukpay-close" onclick="closeCustomModal()" aria-label="Cerrar">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="custom-modal-body tukpay-body">
        <form id="paymentForm" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="id_payment_account" name="id_payment_account">

          <div class="tukpay-amount-card">
            <div class="tukpay-amount-left">
              <div class="tukpay-amount-icon">
                <i class="fas fa-wallet"></i>
              </div>
              <div>
                <div class="tukpay-amount-label">Monto a pagar</div>
                <div class="tukpay-amount-hint">Realiza el pago y luego adjunta tu captura</div>
              </div>
            </div>
            <div class="tukpay-amount-value" id="payment_amount">S/ 0.00</div>
          </div>

          <div class="tukpay-section">
            <div class="tukpay-section-title">
              <i class="fas fa-credit-card"></i>
              Medios de pago
            </div>

            <div class="tukpay-methods-grid">
              <div class="tukpay-method-card">
                <div class="tukpay-method-head">
                  <div class="tukpay-method-head-left">
                    <div class="tukpay-method-icon tukpay-method-icon-primary">
                      <i class="fas fa-university"></i>
                    </div>
                    <div>
                      <div class="tukpay-method-title">Transferencia bancaria</div>
                      <div class="tukpay-method-subtitle">BCP</div>
                    </div>
                  </div>
                </div>

                <div class="tukpay-kv">
                  <div class="tukpay-kv-row">
                    <div class="tukpay-kv-label">CTA corriente</div>
                    <div class="tukpay-kv-value">
                      <span class="tukpay-mono" data-copy-target="bcp-cta">215 711 360 1071</span>
                      <button type="button" class="btn btn-outline-secondary btn-sm tukpay-copy" data-copy="215 711 360 1071" aria-label="Copiar CTA">
                        <i class="far fa-copy"></i>
                      </button>
                    </div>
                  </div>
                  <div class="tukpay-kv-row">
                    <div class="tukpay-kv-label">CCI</div>
                    <div class="tukpay-kv-value">
                      <span class="tukpay-mono" data-copy-target="bcp-cci">00221500711360107128</span>
                      <button type="button" class="btn btn-outline-secondary btn-sm tukpay-copy" data-copy="00221500711360107128" aria-label="Copiar CCI">
                        <i class="far fa-copy"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="tukpay-method-foot">Usa estos datos para transferir y guarda tu constancia.</div>
              </div>

              <div class="tukpay-method-card">
                <div class="tukpay-method-head">
                  <div class="tukpay-method-head-left">
                    <div class="tukpay-method-icon tukpay-method-icon-success">
                      <i class="fas fa-qrcode"></i>
                    </div>
                    <div>
                      <div class="tukpay-method-title">Yape</div>
                      <div class="tukpay-method-subtitle">Billetera digital</div>
                    </div>
                  </div>
                </div>

                <div class="tukpay-kv">
                  <div class="tukpay-kv-row">
                    <div class="tukpay-kv-label">Número</div>
                    <div class="tukpay-kv-value">
                      <span class="tukpay-mono" data-copy-target="yape-num">916 996 847</span>
                      <button type="button" class="btn btn-outline-secondary btn-sm tukpay-copy" data-copy="916 996 847" aria-label="Copiar número Yape">
                        <i class="far fa-copy"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="tukpay-qr-wrap">
                  <div class="tukpay-qr-frame">
                    <img class="tukpay-qr" src="{{ asset('payments/yape_qr.png') }}" alt="QR de pago Yape">
                  </div>
                  <div class="tukpay-qr-hint">Escanea el QR y adjunta tu captura como comprobante.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="tukpay-section">
            <div class="tukpay-section-title">
              <i class="fas fa-cloud-upload-alt"></i>
              Subir comprobante
            </div>

            <div class="tukpay-uploader" id="tukpay-dropzone" tabindex="0" role="button" aria-label="Arrastra o selecciona una imagen para subir">
              <input type="file" class="file-input" id="payment_voucher" name="payment_voucher" accept="image/jpeg,image/png,image/jpg,image/gif" required>
              <div class="tukpay-uploader-inner">
                <div class="tukpay-upload-icon">
                  <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="tukpay-upload-text">
                  <div class="tukpay-upload-title">Arrastra tu imagen aquí</div>
                  <div class="tukpay-upload-subtitle">o haz clic para seleccionar (JPG/PNG/GIF · máx 2MB)</div>
                  <div class="tukpay-upload-filename">
                    <i class="fas fa-paperclip"></i>
                    <span id="file-name">Ningún archivo seleccionado</span>
                  </div>
                </div>
              </div>

              <div id="preview-container" class="tukpay-preview" style="display:none;">
                <div id="voucher-preview" class="tukpay-preview-media"></div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="custom-modal-footer tukpay-footer">
        <button type="button" class="btn btn-light tukpay-btn-cancel" onclick="closeCustomModal()">
          Cancelar
        </button>
        <button type="button" class="btn btn-success tukpay-btn-submit" id="submitPayment">
          <i class="fas fa-check-circle mr-1"></i> Registrar pago
        </button>
      </div>
    </div>
  </div>
@endsection

@push('styles')
<style>
/* Estilos del modal personalizado */
.custom-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background: rgba(13, 110, 253, 0.10);
    backdrop-filter: blur(6px);
}

.custom-modal-content {
    background-color: #ffffff;
    margin: 3.5vh auto;
    border-radius: 16px;
    width: 90%;
    max-width: 780px;
    box-shadow: 0 14px 50px rgba(18, 38, 63, 0.18);
    overflow: hidden;
    transform: translateY(6px);
}

.custom-modal-header {
    padding: 14px 16px;
}

.custom-modal-body {
    padding: 16px;
    background: linear-gradient(180deg, rgba(13, 110, 253, 0.04), rgba(255, 255, 255, 0.0) 40%);
}

.custom-modal-footer {
    padding: 12px 16px;
    border-top: 1px solid rgba(17, 24, 39, 0.08);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #fff;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-label {
    font-weight: bold;
    color: #495057;
}

.info-value {
    font-size: 1.25rem;
    color: #007bff;
    font-weight: bold;
}

.tukpay-modal {
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Liberation Sans", sans-serif;
}

.tukpay-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 60%, #0a58ca 100%);
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.tukpay-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tukpay-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.tukpay-title {
    font-weight: 800;
    letter-spacing: -0.01em;
    font-size: 1.05rem;
    line-height: 1.1;
}

.tukpay-subtitle {
    opacity: 0.9;
    font-size: 0.85rem;
    line-height: 1.1;
}

.tukpay-close {
    border: 0;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
    margin-left: auto;
    margin-top: 2px;
    transition: transform 120ms ease, background-color 120ms ease;
}

.tukpay-close:hover {
    background: rgba(255, 255, 255, 0.22);
    transform: translateY(-1px);
}

.tukpay-body {
    display: grid;
    gap: 12px;
}

.tukpay-amount-card {
    border-radius: 14px;
    padding: 12px 12px;
    border: 1px solid rgba(13, 110, 253, 0.16);
    background: radial-gradient(1200px 200px at 20% -40%, rgba(13, 110, 253, 0.18), rgba(255, 255, 255, 0.0)),
                linear-gradient(180deg, rgba(13, 110, 253, 0.06), rgba(255, 255, 255, 0.0));
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.tukpay-amount-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.tukpay-amount-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, 0.14);
    color: #0d6efd;
}

.tukpay-amount-label {
    font-weight: 800;
    color: #111827;
    line-height: 1.1;
}

.tukpay-amount-hint {
    font-size: 0.85rem;
    color: rgba(17, 24, 39, 0.70);
    line-height: 1.1;
}

.tukpay-amount-value {
    font-weight: 900;
    font-size: 1.35rem;
    color: #0d6efd;
    letter-spacing: -0.02em;
    white-space: nowrap;
}

.tukpay-section {
    border-radius: 14px;
    border: 1px solid rgba(17, 24, 39, 0.08);
    background: rgba(255, 255, 255, 0.92);
    padding: 12px;
    box-shadow: 0 6px 20px rgba(18, 38, 63, 0.06);
}

.tukpay-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 800;
    color: #111827;
    margin-bottom: 10px;
    letter-spacing: -0.01em;
}

.tukpay-methods-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.tukpay-method-card {
    border-radius: 14px;
    border: 1px solid rgba(17, 24, 39, 0.08);
    background: #fff;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 252px;
    transition: transform 140ms ease, box-shadow 140ms ease, border-color 140ms ease;
}

.tukpay-method-card:hover {
    transform: translateY(-1px);
    border-color: rgba(13, 110, 253, 0.18);
    box-shadow: 0 10px 26px rgba(18, 38, 63, 0.10);
}

.tukpay-method-head-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tukpay-method-icon {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(17, 24, 39, 0.08);
}

.tukpay-method-icon-primary {
    background: rgba(13, 110, 253, 0.12);
    color: #0d6efd;
}

.tukpay-method-icon-success {
    background: rgba(25, 135, 84, 0.12);
    color: #198754;
}

.tukpay-method-title {
    font-weight: 900;
    color: #111827;
    letter-spacing: -0.01em;
    line-height: 1.1;
}

.tukpay-method-subtitle {
    font-size: 0.82rem;
    color: rgba(17, 24, 39, 0.68);
    line-height: 1.1;
}

.tukpay-kv {
    display: grid;
    gap: 8px;
}

.tukpay-kv-row {
    display: grid;
    grid-template-columns: 110px 1fr;
    gap: 10px;
    align-items: center;
}

.tukpay-kv-label {
    font-size: 0.86rem;
    color: rgba(17, 24, 39, 0.72);
    font-weight: 700;
}

.tukpay-kv-value {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    align-items: center;
    min-width: 0;
}

.tukpay-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-weight: 800;
    color: #111827;
    text-align: right;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
    line-height: 1.15;
}

.tukpay-copy {
    padding: 0.2rem 0.45rem;
    border-radius: 10px;
}

.tukpay-method-foot {
    margin-top: auto;
    font-size: 0.82rem;
    color: rgba(17, 24, 39, 0.62);
    line-height: 1.25;
}

.tukpay-qr-wrap {
    margin-top: auto;
    display: grid;
    gap: 8px;
    justify-items: center;
}

.tukpay-qr-frame {
    width: 168px;
    max-width: 100%;
    padding: 10px;
    border-radius: 16px;
    border: 1px solid rgba(17, 24, 39, 0.08);
    background: linear-gradient(180deg, rgba(13, 110, 253, 0.05), rgba(255, 255, 255, 0.0));
}

.tukpay-qr {
    width: 100%;
    height: auto;
    border-radius: 12px;
    display: block;
}

.tukpay-qr-hint {
    font-size: 0.82rem;
    color: rgba(17, 24, 39, 0.62);
    text-align: center;
    line-height: 1.25;
}

.tukpay-uploader {
    border-radius: 16px;
    border: 1.5px dashed rgba(13, 110, 253, 0.30);
    background: rgba(13, 110, 253, 0.04);
    padding: 14px;
    cursor: pointer;
    transition: background-color 140ms ease, border-color 140ms ease, transform 140ms ease, box-shadow 140ms ease;
    outline: none;
    position: relative;
}

.tukpay-uploader:hover {
    background: rgba(13, 110, 253, 0.06);
    border-color: rgba(13, 110, 253, 0.42);
    box-shadow: 0 8px 24px rgba(18, 38, 63, 0.08);
}

.tukpay-uploader:focus {
    border-color: rgba(13, 110, 253, 0.60);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.18);
}

.tukpay-uploader.is-dragover {
    background: rgba(25, 135, 84, 0.06);
    border-color: rgba(25, 135, 84, 0.45);
}

.tukpay-uploader .file-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.tukpay-uploader-inner {
    display: flex;
    align-items: center;
    gap: 12px;
}

.tukpay-upload-icon {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, 0.14);
    color: #0d6efd;
    font-size: 1.15rem;
    flex: 0 0 auto;
}

.tukpay-upload-title {
    font-weight: 900;
    color: #111827;
    letter-spacing: -0.01em;
    line-height: 1.1;
}

.tukpay-upload-subtitle {
    font-size: 0.82rem;
    color: rgba(17, 24, 39, 0.68);
    line-height: 1.15;
    margin-top: 2px;
}

.tukpay-upload-filename {
    font-size: 0.82rem;
    color: rgba(17, 24, 39, 0.70);
    display: flex;
    gap: 6px;
    align-items: center;
    margin-top: 8px;
}

.tukpay-preview {
    margin-top: 12px;
}

.tukpay-preview-media img {
    width: 100%;
    max-height: 340px;
    object-fit: contain;
    border-radius: 14px;
    border: 1px solid rgba(17, 24, 39, 0.08);
    background: #fff;
}

.tukpay-footer .tukpay-btn-cancel {
    border-radius: 12px;
    padding: 0.55rem 0.9rem;
}

.tukpay-footer .tukpay-btn-submit {
    border-radius: 12px;
    padding: 0.55rem 1.05rem;
    font-weight: 800;
    background: linear-gradient(135deg, #198754 0%, #12824f 100%);
    border: 0;
    box-shadow: 0 10px 22px rgba(25, 135, 84, 0.18);
}

.tukpay-footer .tukpay-btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 26px rgba(25, 135, 84, 0.22);
}

.file-input-container {
    position: relative;
    margin-bottom: 10px;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.file-input-label {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
    background-color: #f8f9fa;
    border: 2px dashed #ced4da;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.file-input-label:hover {
    background-color: #e9ecef;
    border-color: #007bff;
}

.file-input-label i {
    margin-right: 10px;
    font-size: 1.2rem;
    color: #007bff;
}

.file-input-label span {
    color: #6c757d;
}

.form-text {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 0.875rem;
}

/* Estilos para la vista previa */
.preview-container {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.preview-title {
    font-weight: bold;
    margin-bottom: 10px;
    color: #495057;
}

.voucher-preview {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
}

.voucher-preview img {
    max-width: 100%;
    max-height: 400px;
    border-radius: 5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.required {
    color: red;
}

@media (max-width: 768px) {
    .custom-modal-content {
        margin: 2vh auto;
    }

    .custom-modal-footer {
        flex-direction: column;
    }

    .custom-modal-footer button {
        width: 100%;
    }

    .tukpay-methods-grid {
        grid-template-columns: 1fr;
    }

    .tukpay-kv-row {
        grid-template-columns: 1fr;
        gap: 4px;
    }

    .tukpay-kv-value {
        justify-content: flex-start;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Variables globales
let currentPaymentId = null;
let currentPaymentAmount = null;

function listPayment(){
    $.ajax({
        url: "{{ url('cuenta/payment_records') }}",
        method: 'GET',
        dataType: 'JSON',
        success: function (data) {
            console.log('Datos de pagos recibidos:', data);
            
            const tbody = $('#payments-table-body');
            tbody.empty();
            
            if (data.data && data.data.length > 0) {
                data.data.forEach(function(payment, index) {
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${payment.date_of_payment}</td>
                            <td>${payment.date_of_payment_real || '-'}</td>
                            <td>${payment.comentario || '-'}</td>
                            <td class="text-center">S/ ${payment.payment}</td>
                            <td class="text-center">
                                <span class="badge ${payment.state_badge_class || (payment.state ? 'badge-success' : 'badge-warning')}">
                                    ${payment.state_description}
                                </span>
                            </td>
                            <td class="text-center">
                                ${payment.has_receipt_pdf
                                    ? `<a class="btn waves-effect waves-light btn-xs btn-success"
                                            href="{{ url('cuenta/payment_receipt') }}/${payment.id}"
                                            target="_blank" rel="noopener">
                                            <i class="fas fa-file-pdf"></i> Comprobante
                                        </a>`
                                    : (!payment.state && !payment.reference_payment
                                        ? `<button type="button" 
                                                class="btn waves-effect waves-light btn-xs btn-info"
                                                onclick="openCustomModal(${payment.id}, ${payment.payment})">
                                                <i class="fas fa-credit-card"></i> Pagar
                                            </button>`
                                        : (payment.state
                                            ? '<span class="text-success"><i class="fas fa-check-circle"></i> Pagado</span>'
                                            : '<span class="text-warning"><i class="fas fa-hourglass-half"></i> Pendiente</span>'
                                        )
                                    )
                                }
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                tbody.append(`
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron pagos registrados</td>
                    </tr>
                `);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', xhr.responseText);
            const tbody = $('#payments-table-body');
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        Error al cargar los pagos. Intente nuevamente.
                    </td>
                </tr>
            `);
        }
    });
}

// Funciones para el modal personalizado
function openCustomModal(paymentId, amount) {
    console.log('Abriendo modal para pago:', paymentId, amount);
    
    currentPaymentId = paymentId;
    currentPaymentAmount = amount;
    
    // Establecer valores en el formulario (nombre correcto del campo)
    $('#id_payment_account').val(paymentId);
    $('#payment_amount').text('S/ ' + parseFloat(amount).toFixed(2));
    
    // Resetear formulario
    $('#paymentForm')[0].reset();
    $('#id_payment_account').val(paymentId); // Volver a establecer después del reset
    $('#file-name').text('Ningún archivo seleccionado');
    $('#preview-container').hide();
    $('#voucher-preview').empty();
    
    // Mostrar modal
    $('#customPaymentModal').fadeIn(300);
    $('body').css('overflow', 'hidden');
}

function closeCustomModal() {
    $('#customPaymentModal').fadeOut(300);
    $('body').css('overflow', 'auto');
    
    // Limpiar variables
    currentPaymentId = null;
    currentPaymentAmount = null;
}

// Cerrar modal al hacer clic fuera del contenido
$(document).on('click', function(e) {
    if ($(e.target).is('#customPaymentModal')) {
        closeCustomModal();
    }
});

$(document).on('click', '[data-copy]', async function(e) {
    e.preventDefault();
    e.stopPropagation();

    const text = String($(this).data('copy') || '').trim();
    if (!text) return;

    const btn = this;
    const originalHtml = btn.innerHTML;
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            const temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
        }
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 900);
    } catch (err) {
        btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 900);
    }
});

$(document).on('dragenter dragover', '#tukpay-dropzone', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('is-dragover');
});

$(document).on('dragleave dragend drop', '#tukpay-dropzone', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('is-dragover');
});

$(document).on('drop', '#tukpay-dropzone', function(e) {
    const dt = e.originalEvent.dataTransfer;
    if (!dt || !dt.files || !dt.files.length) return;
    const input = $('#payment_voucher')[0];
    input.files = dt.files;
    $(input).trigger('change');
});

// Vista previa de archivo seleccionado - Usando delegación de eventos
$(document).on('change', '#payment_voucher', function(e) {
    console.log('Evento change detectado en payment_voucher');
    const file = this.files[0];
    const previewContainer = $('#preview-container');
    const previewDiv = $('#voucher-preview');
    const fileName = $('#file-name');
    
    console.log('Archivo seleccionado:', file);
    
    if (file) {
        // Verificar tamaño del archivo (2MB máximo)
        console.log('Tamaño del archivo:', file.size);
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El archivo es demasiado grande. El tamaño máximo permitido es 2MB.'
            });
            $(this).val('');
            fileName.text('Ningún archivo seleccionado');
            previewContainer.hide();
            return;
        }
        
        // Verificar tipo de archivo
        console.log('Tipo de archivo:', file.type);
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Formato de archivo no válido. Solo se permiten imágenes JPG, PNG y GIF.'
            });
            $(this).val('');
            fileName.text('Ningún archivo seleccionado');
            previewContainer.hide();
            return;
        }
        
        // Actualizar nombre del archivo
        fileName.text(file.name);
        console.log('Nombre actualizado a:', file.name);
        
        // Mostrar vista previa
        const reader = new FileReader();
        
        reader.onload = function(e) {
            console.log('Imagen cargada, mostrando preview');
            previewDiv.html(`<img src="${e.target.result}" alt="Vista previa del comprobante">`);
            previewContainer.show();
        }
        
        reader.onerror = function(e) {
            console.error('Error al leer archivo:', e);
        }
        
        reader.readAsDataURL(file);
    } else {
        console.log('No hay archivo seleccionado');
        previewContainer.hide();
        fileName.text('Ningún archivo seleccionado');
    }
});

// Enviar formulario de pago - Usando delegación de eventos
$(document).on('click', '#submitPayment', function(e) {
    e.preventDefault();
    console.log('Botón submitPayment clickeado');
    
    const form = $('#paymentForm')[0];
    const formData = new FormData(form);
    
    // Verificar que se haya seleccionado un archivo
    const voucherFile = $('#payment_voucher')[0].files[0];
    console.log('Archivo del voucher:', voucherFile);
    
    if (!voucherFile) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Por favor, seleccione un comprobante de pago.'
        });
        return;
    }
    
    // Verificar que el ID del pago esté presente
    const paymentId = $('#id_payment_account').val();
    console.log('ID del pago:', paymentId);
    
    if (!paymentId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo identificar el pago. Por favor, cierre e intente nuevamente.'
        });
        return;
    }
    
    console.log('Enviando pago con ID:', paymentId);
    console.log('Archivo:', voucherFile.name);
    console.log('FormData entries:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Mostrar indicador de carga
    const submitBtn = $(this);
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
    
    // Enviar datos mediante AJAX
    $.ajax({
        url: "{{route('tenant.account.payment_manual')}}",
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            
            // Cerrar modal
            closeCustomModal();
            
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: response.message || 'Pago registrado correctamente',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Recargar tabla de pagos
                listPayment();
            });
        },
        error: function(xhr, status, error) {
            console.error('Error completo:', xhr);
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            
            let errorMessage = 'Error al registrar el pago. Intente nuevamente.';
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage,
                confirmButtonText: 'Aceptar'
            });
        },
        complete: function() {
            // Restaurar botón
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
});

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    console.log('Documento listo, cargando pagos...');
    listPayment();
    
    // Cerrar modal con ESC
    $(document).keyup(function(e) {
        if (e.key === 'Escape') {
            closeCustomModal();
        }
    });
});
</script>



<script>
    
    /*Culqi.publicKey = "{{$token_public_culqui}}";
    Culqi.options({
        installments: true
    });

    var price_culqi_payment_account = 0;
    var price_payment_account = 0;

    var id_payment_account = null;



    function execCulqi(id, payment) {

        id_payment_account  = id
        price_culqi_payment_account =  Math.round( Number(payment).toFixed(2))
       
        price_payment_account = Math.round((Number(payment).toFixed(2)) * 100)

        Culqi.settings({
            title: "Pago de Cuenta Facturador",
            currency: 'PEN',
            description: 'Pago programado facturador',
            amount: price_payment_account
        });

        Culqi.open();

    }

    function culqi() {

        if (Culqi.token) {

            swal({
                title: "Estamos hablando con su banco",
                text: `Por favor no cierre esta ventana hasta que el proceso termine.`,
                focusConfirm: false,
                onOpen: () => {
                    Swal.showLoading()
                }
            });

            var token = Culqi.token.id;
            var email = Culqi.token.email;
            var installments = Culqi.token.metadata.installments;
            let items = [{ description: 'Pago programado facturador', cantidad: '1', unit_type_id: 'NIU' }]
            var data = {
                producto: 'Pago Progamado Cuenta Facturador Pro',
                precio: price_payment_account,
                precio_culqi: price_culqi_payment_account,
                token: token,
                email: email,
                installments: installments,
                id_payment_account: id_payment_account,
                items: items
            }

            $.ajax({
                url: "{{route('tenant.account.payment_culqui')}}",
                method: 'post',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: data,
                dataType: 'JSON',
                success: function (data) {

                    if (data.success == true) {
                        swal({
                            title: "Gracias por su pago!",
                            text: "En breve le enviaremos un correo electronico con los detalles de su compra.",
                            type: "success"
                        }).then((x) => {
                            location.reload();
                        })
                    } else {
                        swal({
                            title: "Pago No realizado!",
                            text: data.message,
                            type: "error"
                        }).then((x) => {
                            location.reload();
                        })
                    }
                },
                error: function (error_data) {
                    swal({
                            title: "Pago No realizado!",
                            text: "Tuvimos un problema al procesar el pago.",
                            type: "error"
                        }).then((x) => {
                            location.reload();
                        })
                }
            });

        } else {
            console.log(Culqi.error);
            swal("Pago No realizado", Culqi.error.user_message, "error");
        }
    };*/

</script>
@endpush
