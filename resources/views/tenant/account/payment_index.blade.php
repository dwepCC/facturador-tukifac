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

  <!-- Modal personalizado para cargar comprobante de pago -->
  <div id="customPaymentModal" class="custom-modal">
    <div class="custom-modal-content">
      <div class="custom-modal-header">
        <h3>Cargar Comprobante de Pago</h3>
        <span class="custom-close" onclick="closeCustomModal()">&times;</span>
      </div>
      <div class="custom-modal-body">
        <form id="paymentForm" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="id_payment_account" name="id_payment_account">
          
          <div class="payment-info">
            <div class="info-item">
              <span class="info-label">Monto a Pagar:</span>
              <span class="info-value" id="payment_amount">S/ 0.00</span>
            </div>
          </div>

          <div class="form-group">
            <label for="payment_voucher" class="form-label">Comprobante de Pago: <span class="required">*</span></label>
            <div class="file-input-container">
              <input type="file" class="file-input" id="payment_voucher" name="payment_voucher" accept="image/jpeg,image/png,image/jpg,image/gif" required>
              <label for="payment_voucher" class="file-input-label" id="file-label">
                <i class="fas fa-upload"></i>
                <span id="file-name">Seleccione una imagen</span>
              </label>
            </div>
            <small class="form-text">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
            {{--<button type="button" class="btn btn-sm btn-secondary mt-2" id="triggerFileInput">
              <i class="fas fa-folder-open"></i> Buscar archivo
            </button>--}}
          </div>

          <div id="preview-container" class="preview-container" style="display: none;">
            <p class="preview-title">Vista previa:</p>
            <div id="voucher-preview" class="voucher-preview">
              <!-- Aquí se mostrará la vista previa -->
            </div>
          </div>
        </form>
      </div>
      <div class="custom-modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeCustomModal()">
          <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn btn-primary" id="submitPayment">
          <i class="fas fa-check"></i> Registrar Pago
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
    background-color: rgba(0, 0, 0, 0.5);
}

.custom-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.custom-modal-header {
    padding: 20px;
    background-color: #007bff;
    color: white;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.custom-modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
}

.custom-close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.custom-close:hover,
.custom-close:focus {
    color: #ddd;
}

.custom-modal-body {
    padding: 30px;
}

.custom-modal-footer {
    padding: 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Estilos para la información del pago */
.payment-info {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
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

/* Estilos para el input de archivo personalizado */
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
                                <span class="badge ${payment.state ? 'badge-success' : 'badge-warning'}">
                                    ${payment.state_description}
                                </span>
                            </td>
                            <td class="text-center">
                                ${!payment.state && !payment.reference_payment ? 
                                    `<button type="button" 
                                        class="btn waves-effect waves-light btn-xs btn-info"
                                        onclick="openCustomModal(${payment.id}, ${payment.payment})">
                                        <i class="fas fa-credit-card"></i> Pagar
                                    </button>` 
                                    : 
                                    '<span class="text-success"><i class="fas fa-check-circle"></i> Pagado</span>'
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
    $('#file-name').text('Seleccione una imagen');
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

// Trigger para abrir el selector de archivos
$(document).on('click', '#triggerFileInput, #file-label', function(e) {
    e.preventDefault();
    console.log('Activando selector de archivos');
    $('#payment_voucher').click();
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
            fileName.text('Seleccione una imagen');
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
            fileName.text('Seleccione una imagen');
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
        fileName.text('Seleccione una imagen');
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
