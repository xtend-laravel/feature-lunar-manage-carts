<?php

namespace XtendLunar\Features\ManageCarts\Livewire\Pages;

use Livewire\Component;
use Lunar\Models\Cart;

class CartShow extends Component
{
    /**
     * The Product we are currently editing.
     *
     * @var \Lunar\Models\Product
     */
    public Cart $cart;

    /**
     * Render the livewire component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('adminhub::livewire.pages.show')
            ->layout('adminhub::layouts.app', [
                'title' => __('adminhub::orders.show.title', ['id' => $this->cart->id]),
            ]);
    }
}
