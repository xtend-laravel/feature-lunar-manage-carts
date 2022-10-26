<?php

use Illuminate\Support\Facades\Route;
use Lunar\Hub\Http\Middleware\Authenticate;
use XtendLunar\Features\ManageCarts\Livewire\Pages\CartShow;
use XtendLunar\Features\ManageCarts\Livewire\Pages\CartsIndex;

/**
 * Carts routes.
 */
Route::group([
    'prefix' => config('lunar-hub.system.path', 'hub'),
    'middleware' => ['web', Authenticate::class, 'can:catalogue:manage-orders'],
], function () {
    Route::get('carts', CartsIndex::class)->name('hub.carts.index');
    Route::get('carts/{cart}', CartShow::class)->name('hub.carts.show');
});
