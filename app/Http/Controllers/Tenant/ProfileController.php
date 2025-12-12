<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        return view('tenant.profile.index'); 
    }

    public function record(Request $request)
    {
        $user = auth()->user();
        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'establishment_id' => $user->establishment_id,
            ],
        ];
    }
}

