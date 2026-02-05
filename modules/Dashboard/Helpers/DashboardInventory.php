<?php

namespace Modules\Dashboard\Helpers;

use Modules\Inventory\Models\ItemWarehouse;
use Modules\Dashboard\Http\Resources\DashboardInventoryCollection;
use App\Models\Tenant\Establishment;
use Carbon\Carbon;

class DashboardInventory
{

    public function data($request)
    {
        $establishment_id = $request->establishment_id;
        $date_start = $this->parseDate($request->date_start);
        $date_end = $this->parseDate($request->date_end);

        return $this->products_date_of_due($establishment_id, $date_start, $date_end);
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return $date;
            }
        }
    }

    
    private function products_date_of_due($establishment_id, $date_start, $date_end)
    {

        if(!$establishment_id){
            $establishment_id = Establishment::select('id')->first()->id;
        }

        if ($date_start && $date_end) {
        $products = ItemWarehouse::whereHas('item',function($query) use($date_start,$date_end){

                        $query->whereNotIsSet();
                        $query->where('unit_type_id','!=', 'ZZ');
                        $query->whereBetween('date_of_due', [$date_start, $date_end]);
                        
                    })
                    ->whereHas('warehouse',function($query) use($establishment_id){
                        $query->where('establishment_id', $establishment_id);
                    })
                    ->paginate(config('tenant.items_per_page_simple_d_table'));
        }else{
            $products = ItemWarehouse::whereHas('item',function($query){

                $query->whereNotIsSet();
                $query->where('unit_type_id','!=', 'ZZ');
                
            })
            ->whereHas('warehouse',function($query) use($establishment_id){
                $query->where('establishment_id', $establishment_id);
            })
            ->paginate(config('tenant.items_per_page_simple_d_table'));
        }
                   
        return new DashboardInventoryCollection($products);
    }
 
}