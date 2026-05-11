<?php

namespace App\CoreFacturalo\Requests\Api\Validation;

use App\Models\Tenant\Item;

class DocumentValidation
{
    public static function validation($inputs) {
        $inputs['establishment_id'] = auth()->user()->establishment_id;// Functions::establishment($inputs['establishment']);
        //unset($inputs['establishment']);
        
        Functions::validateSeries($inputs);
        
        if (in_array($inputs['document_type_id'], ['07', '08'])) {

            if($inputs['affected_document_external_id']){
                $document = Functions::findAffectedDocumentByExternalId($inputs['affected_document_external_id']);
                $inputs['affected_document_id'] = $document->id;
                $inputs['data_affected_document'] = null;

            }else{
                //validar campos json doc afectado
                $inputs['affected_document_id'] = null;

            }
            
            unset($inputs['affected_document_external_id']);
        }
        
        $inputs['customer_id'] = Functions::person($inputs['customer'], 'customers');
        unset($inputs['customer']);
        
        $inputs['items'] = self::items($inputs['items']);
        
        Functions::DNI($inputs);
        Functions::identityDocumentTypeInvoice($inputs);

        Functions::validateDetraction($inputs);

        Functions::validateDateOfIssue($inputs);
        
        return $inputs;
    }
    
    private static function items($inputs) {
        foreach ($inputs as &$row) {
            $lineDescription = $row['description'] ?? null;
            $lineUnitTypeId = $row['unit_type_id'] ?? null;

            $row['item_id'] = Functions::item($row);

            $resolved = Item::query()->find($row['item_id']);
            if ($resolved && (
                $resolved->barcode === 'VARIOUS_ITEM'
                || trim((string) $resolved->description) === 'REPLACE DESCRIPTION'
            )) {
                $row['item'] = array_merge(is_array($row['item'] ?? null) ? $row['item'] : [], [
                    'description' => $lineDescription ?? $resolved->description,
                    'unit_type_id' => $lineUnitTypeId ?? $resolved->unit_type_id,
                ]);
            }

            unset($row['internal_id'], $row['description']);
            unset($row['item_type_id'], $row['item_code']);
            unset($row['item_code_gs1'], $row['unit_type_id']);
            unset($row['currency_type_id']);
        }

        return $inputs;
    }
}