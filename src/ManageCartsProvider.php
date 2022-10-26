<?php

namespace XtendLunar\Features\ManageCarts;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use XtendLunar\Features\ManageCarts\Livewire\Components\CartShow;
use XtendLunar\Features\ManageCarts\Livewire\Components\CartsIndex;
use XtendLunar\Features\ManageCarts\Livewire\Components\CartsTable;

class ManageCartsProvider extends ServiceProvider
{
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/carts.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'adminhub');

        Livewire::component('hub.components.carts.index', CartsIndex::class);
        Livewire::component('hub.components.carts.show', CartShow::class);
        Livewire::component('hub.components.carts.table', CartsTable::class);
    }
}
