<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant\Configuration;
use App\Helpers\UserControlHelper;


class LockedTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $configuration = Configuration::first();
        if(null === $configuration) {
            $configuration = new Configuration();
        }

        if($configuration->isLockedTenant()){
            $path = ltrim($request->path(), '/');
            $allowed = [
                'cuenta/payment_index',
                'cuenta/payment_records',
                'cuenta/info_plan',
                'cuenta/payment_manual',
                'cuenta/payment_culqui',
                'cuenta/tables',
            ];

            if (!in_array($path, $allowed, true)) {
            abort(403);
            }
        }

        UserControlHelper::checkActiveUser();

        return $next($request);
    }
}
