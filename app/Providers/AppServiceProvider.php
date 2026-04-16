<?php

namespace App\Providers;

use App\Models\Tenant\Company;
use App\Models\Tenant\Document;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\User as TenantUser;
use App\Observers\DocumentObserver;
use App\Observers\TenantCentralCompanyObserver;
use App\Observers\TenantCentralEstablishmentObserver;
use App\Observers\TenantCentralSaleNoteObserver;
use App\Observers\TenantCentralUserObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Modules\LevelAccess\Helpers\SessionLifetimeHelper;


class AppServiceProvider extends ServiceProvider
{
	public function boot()
	{
		// Evitar ejecutar en consola; aplicar sólo en contexto web
		if (!app()->runningInConsole()) {
			SessionLifetimeHelper::setTenantSessionLifetime();
		}

		if (config('tenant.force_https')) {
			URL::forceScheme('https');
		}
		Document::observe(DocumentObserver::class);
		SaleNote::observe(TenantCentralSaleNoteObserver::class);
		TenantUser::observe(TenantCentralUserObserver::class);
		Establishment::observe(TenantCentralEstablishmentObserver::class);
		Company::observe(TenantCentralCompanyObserver::class);
	}

	public function register()
	{
	}
}
