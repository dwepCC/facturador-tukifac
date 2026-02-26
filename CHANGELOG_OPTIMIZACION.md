# Changelog: Optimización de Registro de Ventas y Validación de Caja

Este documento detalla todas las mejoras, optimizaciones y cambios realizados en el sistema de facturación para reducir el tiempo de respuesta al registrar ventas y mejorar la integridad de los datos.

## 🚀 Resumen Ejecutivo
Se ha logrado reducir significativamente el tiempo de espera del usuario final al registrar una venta. Esto se consiguió moviendo procesos pesados (generación de PDF y envío de correos) a segundo plano y optimizando el flujo de validación de caja, eliminando pasos innecesarios en el frontend.

---

## 🛠️ Detalle de Cambios Técnicos

### 1. Arquitectura Asíncrona (Background Jobs)
El sistema antes realizaba todas las tareas de forma secuencial y bloqueante. Ahora, se ha desacoplado el proceso:

*   **Nuevo Job `ProcessDocumentPdf`**: La generación del PDF (A4, Ticket, A5) de Facturas y Boletas ahora se realiza en segundo plano. El usuario ya no espera a que el archivo se cree para ver la confirmación de venta.
*   **Nuevo Job `ProcessDocumentEmail`**: El envío del correo electrónico al cliente ahora se procesa en una cola de trabajo, evitando la espera de conexión con el servidor SMTP.
*   **Nuevo Job `ProcessSaleNotePdf`**: Similar a los comprobantes, las Notas de Venta ahora generan su PDF de forma asíncrona.
*   **Configuración Multi-Tenant**: Todos los Jobs fueron programados respetando la arquitectura multi-inquilino del sistema, asegurando que se ejecuten en la base de datos correcta del cliente.

### 2. Optimización de Controladores API
Se modificaron los controladores principales para implementar estos cambios:

*   **`DocumentController` (Facturas/Boletas)**:
    *   Se eliminó la llamada síncrona a `createPdf()` y `sendEmail()`.
    *   Se implementó el despacho encadenado (`withChain`) de los nuevos Jobs.
    *   **Mejora de Respuesta**: Se eliminó el envío del Logo en Base64 dentro del objeto `print_data`, reduciendo drásticamente el tamaño de la respuesta JSON (menos consumo de datos para el cliente móvil).
*   **`SaleNoteController` (Notas de Venta)**:
    *   Se movió la generación del PDF fuera de la transacción de base de datos y se delegó al Job `ProcessSaleNotePdf`.
    *   Se eliminaron logs innecesarios (`self::ExtraLog`) que ralentizaban el proceso de guardado por escritura en disco.
    *   Se optimizó la respuesta `print_data` eliminando también el Logo en Base64.

### 3. Validación de Caja Integrada
Se optimizó el flujo de validación para evitar dobles peticiones desde el frontend:

*   **Eliminación de Doble Check**: Ya no es necesario llamar a `/api/cash/available-restaurant` antes de vender.
*   **Validación en el Backend**: Se implementó el método `validationOpenCash` directamente en el proceso de guardado (`store`) tanto para Documentos como para Notas de Venta.
*   **Lógica Inteligente**:
    *   Si el pago es en **Efectivo** (`payment_destination_id: 'cash'`), el sistema exige estrictamente una caja abierta.
    *   Si el pago es por **Transferencia/Banco/Crédito**, el sistema permite la venta sin exigir caja abierta.
*   **Respuesta de Error Estándar**: Si la validación falla, se devuelve un mensaje claro: *"Ocurrió un error: Caja seleccionada en métodos de pago se encuentra cerrada o no tiene una caja aperturada"*.

---

## 📋 Instrucciones para Despliegue (Deploy)

Para que estos cambios surtan efecto en producción, se deben seguir estos pasos:

1.  **Actualizar Código**: Desplegar los nuevos archivos PHP en el servidor.
2.  **Reiniciar Colas**: Es **CRÍTICO** ejecutar el siguiente comando para que los workers reconozcan los nuevos Jobs:
    ```bash
    php artisan queue:restart
    ```
    *(Si se usa Docker, ejecutar dentro del contenedor correspondiente)*.
3.  **Frontend**:
    *   Eliminar la petición previa de validación de caja.
    *   Enviar la venta directamente y capturar el error 422/500 si la caja está cerrada.
    *   Estructurar el array de `payments` correctamente (usar `payment_destination_id: 'cash'` solo para efectivo).

---

## ✅ Beneficios Obtenidos
1.  **Mayor Velocidad**: El usuario percibe el registro de venta como "casi instantáneo".
2.  **Menor Consumo de Datos**: Respuestas API más ligeras al quitar binarios (imágenes).
3.  **Mejor UX**: Menos tiempos de carga y validaciones más lógicas según el método de pago.
4.  **Escalabilidad**: El sistema ahora puede manejar picos de ventas sin bloquearse generando PDFs.
