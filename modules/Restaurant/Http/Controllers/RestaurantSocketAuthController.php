<?php

namespace Modules\Restaurant\Http\Controllers;

use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Restaurant\Services\RestaurantSocketBroadcaster;

/**
 * Validación de token API para el servidor Node (handshake Socket.IO).
 * El cliente envía el mismo token que devuelve POST /login.
 */
class RestaurantSocketAuthController extends Controller
{
    public function authenticate(Request $request, RestaurantSocketBroadcaster $broadcaster)
    {
        $token = $request->input('token')
            ?? $request->query('token')
            ?? $request->bearerToken();

        if (is_string($token)) {
            $token = trim($token);
            if (str_starts_with($token, 'Bearer ')) {
                $token = trim(substr($token, 7));
            }
        }

        if (! $token) {
            return response()->json(['valid' => false, 'message' => 'Token requerido'], 401);
        }

        $user = User::where('api_token', $token)->first();

        if (! $user) {
            return response()->json(['valid' => false, 'message' => 'Token inválido'], 401);
        }

        return response()->json([
            'valid' => true,
            'user_id' => $user->id,
            'establishment_id' => $user->establishment_id,
            'socket_room' => $broadcaster->tenantRoomPublicKey(),
            'email' => $user->email,
        ]);
    }
}
