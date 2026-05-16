/**
 * Indica si, tras crear un comprobante, debe enviarse de inmediato a SUNAT/PSE.
 * Con PSE en modo manual no se envía al crear (solo con /documents/send).
 */
export function canSendDocumentImmediately(company) {
    if (!company || !company.send_document_to_pse) {
        return true
    }

    return company.auto_send_document_to_pse !== false
}
