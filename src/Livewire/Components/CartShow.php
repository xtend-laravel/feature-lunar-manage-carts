<?php

namespace XtendLunar\Features\ManageCarts\Livewire\Components;

use Illuminate\Support\Arr;
use Livewire\Component;
use Lunar\Hub\Http\Livewire\Traits\HasSlots;
use Lunar\Hub\Http\Livewire\Traits\Notifies;
use Lunar\Hub\Http\Livewire\Traits\WithCountries;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\Channel;
use Lunar\Models\State;

class CartShow extends Component
{
    use Notifies, WithCountries, HasSlots;

    /**
     * The current cart in view.
     *
     * @var \Lunar\Models\Cart
     */
    public Cart $cart;

    /**
     * The instance of the shipping address.
     *
     * @var \Lunar\Models\CartAddress
     */
    public ?CartAddress $shippingAddress = null;

    /**
     * The instance of the shipping address.
     *
     * @var \Lunar\Models\CartAddress
     */
    public ?CartAddress $billingAddress = null;

    /**
     * Whether all lines should be visible.
     *
     * @var bool
     */
    public bool $allLinesVisible = false;

    /**
     * The maximum lines to show on load.
     *
     * @var int
     */
    public int $maxLines = 5;

    /**
     * The new comment property.
     *
     * @var string
     */
    public string $comment = '';

    /**
     * Whether to show the update status modal.
     *
     * @var bool
     */
    public bool $showUpdateStatus = false;

    /**
     * Whether to show the address edit screen.
     *
     * @var bool
     */
    public bool $showShippingAddressEdit = false;

    /**
     * Whether to show the billing address edit.
     *
     * @var bool
     */
    public bool $showBillingAddressEdit = false;

    /**
     * The currently selected lines.
     *
     * @var array
     */
    public array $selectedLines = [];

    /**
     * Whether to show the refund panel.
     *
     * @var bool
     */
    public bool $showRefund = false;

    /**
     * Whether to show the capture panel.
     *
     * @var bool
     */
    public bool $showCapture = false;

    /**
     * {@inheritDoc}
     */
    protected function getListeners()
    {
        return array_merge(
            [
                'captureSuccess',
                'refundSuccess',
                'cancelRefund',
                'refreshCart',
            ],
            $this->getHasSlotsListeners()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            'cart.status' => 'string',
            'comment' => 'required|string',
            'shippingAddress.postcode' => 'required|string|max:255',
            'shippingAddress.title' => 'nullable|string|max:255',
            'shippingAddress.first_name' => 'nullable|string|max:255',
            'shippingAddress.last_name' => 'nullable|string|max:255',
            'shippingAddress.company_name' => 'nullable|string|max:255',
            'shippingAddress.line_one' => 'nullable|string|max:255',
            'shippingAddress.line_two' => 'nullable|string|max:255',
            'shippingAddress.line_three' => 'nullable|string|max:255',
            'shippingAddress.city' => 'nullable|string|max:255',
            'shippingAddress.state' => 'nullable|string|max:255',
            'shippingAddress.delivery_instructions' => 'nullable|string|max:255',
            'shippingAddress.contact_email' => 'nullable|email|max:255',
            'shippingAddress.contact_phone' => 'nullable|string|max:255',
            'shippingAddress.country_id' => 'required',
            'billingAddress.postcode' => 'required|string|max:255',
            'billingAddress.title' => 'nullable|string|max:255',
            'billingAddress.first_name' => 'nullable|string|max:255',
            'billingAddress.last_name' => 'nullable|string|max:255',
            'billingAddress.company_name' => 'nullable|string|max:255',
            'billingAddress.line_one' => 'nullable|string|max:255',
            'billingAddress.line_two' => 'nullable|string|max:255',
            'billingAddress.line_three' => 'nullable|string|max:255',
            'billingAddress.city' => 'nullable|string|max:255',
            'billingAddress.state' => 'nullable|string|max:255',
            'billingAddress.delivery_instructions' => 'nullable|string|max:255',
            'billingAddress.contact_email' => 'nullable|email|max:255',
            'billingAddress.contact_phone' => 'nullable|string|max:255',
            'billingAddress.country_id' => 'required',
        ];
    }

    public function mount()
    {
        $this->shippingAddress = $this->cart->shippingAddress ?: new CartAddress();

        $this->billingAddress = $this->cart->billingAddress ?: new CartAddress();
    }

    /**
     * Refresh the current cart in the database.
     *
     * @return void
     */
    public function refreshCart()
    {
        $this->cart = $this->cart->refresh();
    }

    /**
     * Return the computed channel property.
     *
     * @return string
     */
    public function getChannelProperty()
    {
        return Channel::findOrFail($this->cart->channel_id)->name;
    }

    /**
     * Return the configured statuses.
     *
     * @return array
     */
    public function getStatusesProperty()
    {
        return config('lunar.carts.statuses', []);
    }

    /**
     * Get the billing address computed property.
     *
     * @return \Lunar\Models\CartAddress|null
     */
    public function getBillingProperty()
    {
        return $this->cart->billingAddress;
    }

    /**
     * Return the computed shipping lines.
     *
     * @return void
     */
    public function getShippingLinesProperty()
    {
        return $this->cart->shippingLines;
    }

    /**
     * Return all lines "above the fold".
     *
     * @return void
     */
    public function getVisibleLinesProperty()
    {
        return $this->physicalAndDigitalLines->take($this->allLinesVisible ? null : $this->maxLines);
    }

    /**
     * Return the physical cart lines.
     *
     * @return void
     */
    public function getPhysicalLinesProperty()
    {
        return $this->cart->lines->filter(function ($line) {
            return $line->type == 'physical';
        });
    }

    /**
     * Return the physical and digital cart lines.
     *
     * @return void
     */
    public function getPhysicalAndDigitalLinesProperty()
    {
        return $this->cart->lines->filter(function ($line) {
            return in_array($line->type, ['physical', 'digital']);
        });
    }

    /**
     * Update the cart status.
     *
     * @return void
     */
    public function updateStatus()
    {
        $this->cart->update([
            'status' => $this->cart->status,
        ]);

        $this->notify(
            __('adminhub::notifications.cart.status_updated')
        );
        $this->showUpdateStatus = false;
    }

    /**
     * Handler when shipping edit toggle is updated.
     *
     * @return void
     */
    public function updatedShowShippingAddressEdit()
    {
        $this->shippingAddress = $this->shippingAddress->refresh();
    }

    /**
     * Return the refund amount based on selected lines
     * or based on the cart total.
     *
     * @return int
     */
    public function getRefundAmountProperty()
    {
        if (count($this->selectedLines)) {
            return $this->cart->lines->filter(function ($line) {
                return in_array($line->id, $this->selectedLines);
            })->sum('total.value');
        }

        return $this->cart->total->value;
    }

    /**
     * Returns whether this cart still requires capture.
     *
     * @return void
     */
    public function getRequiresCaptureProperty()
    {
        $captures = $this->transactions->filter(function ($transaction) {
            return $transaction->type == 'capture';
        })->count();

        $intents = $this->transactions->filter(function ($transaction) {
            return $transaction->type == 'intent';
        })->count();

        if (! $intents) {
            return false;
        }

        return ! $captures;
    }

    /**
     * Return the cart transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTransactionsProperty()
    {
        return $this->cart->transactions()->cartBy('created_at', 'desc')->get();
    }

    /**
     * Return the total amount captured.
     *
     * @return int
     */
    public function getCaptureTotalProperty()
    {
        return $this->transactions->filter(function ($transaction) {
            return $transaction->type == 'capture';
        })->sum('amount.value');
    }

    /**
     * Return the total amount refunded.
     *
     * @return int
     */
    public function getRefundTotalProperty()
    {
        return $this->transactions->filter(function ($transaction) {
            return $transaction->type == 'refund';
        })->sum('amount.value');
    }

    /**
     * Return the total amount refunded.
     *
     * @return int
     */
    public function getIntentTotalProperty()
    {
        return $this->transactions->filter(function ($transaction) {
            return $transaction->type == 'intent';
        })->sum('amount.value');
    }

    /**
     * Return whether this cart is partially refunded.
     *
     * @return void
     */
    public function getPaymentStatusProperty()
    {
        $total = $this->intentTotal ?: $this->captureTotal;

        if (! $total) {
            return 'offline';
        }

        if (
            ($this->refundTotal && $this->refundTotal < $total) ||
            ($this->captureTotal && $this->captureTotal < $this->intentTotal)
        ) {
            return 'partial-refund';
        }

        if ($this->refundTotal >= $total) {
            return 'refunded';
        }

        if ($this->captureTotal >= $this->intentTotal) {
            return 'captured';
        }

        return 'uncaptured';
    }

    /**
     * Handle when a capture is successful.
     *
     * @return void
     */
    public function captureSuccess()
    {
        $this->showCapture = false;
    }

    /**
     * Handle when a refund is successful.
     *
     * @return void
     */
    public function refundSuccess()
    {
        $this->showRefund = false;
    }

    /**
     * Cancel the refund process.
     *
     * @return void
     */
    public function cancelRefund()
    {
        $this->showRefund = false;
    }

    /**
     * Handle when selected cart lines update.
     *
     * @param  array  $val
     * @return void
     */
    public function updatedSelectedLines($val)
    {
        $this->emit('updateRefundAmount', $this->refundAmount);
    }

    /**
     * Save the shipping address.
     *
     * @return void
     */
    public function saveShippingAddress()
    {
        $addressRules = collect($this->rules())->filter(function ($rule, $field) {
            return str_contains($field, 'shippingAddress');
        });

        $this->validate($addressRules->toArray());

        $this->shippingAddress->cart_id = $this->cart->id;
        $this->shippingAddress->save();

        $this->shippingAddress->refresh();

        $this->notify(
            __('adminhub::notifications.shipping_address.saved')
        );

        $this->showShippingAddressEdit = false;
    }

    /**
     * Save the shipping address.
     *
     * @return void
     */
    public function saveBillingAddress()
    {
        $addressRules = collect($this->rules())->filter(function ($rule, $field) {
            return str_contains($field, 'billingAddress');
        });

        $this->validate($addressRules->toArray());

        $this->billingAddress->cart_id = $this->cart->id;
        $this->billingAddress->save();

        $this->billingAddress->refresh();

        $this->notify(
            __('adminhub::notifications.billing_address.saved')
        );

        $this->showBillingAddressEdit = false;
    }

    /**
     * Return whether the billing postcode matches the shipping postcode.
     *
     * @return void
     */
    public function getShippingEqualsBillingProperty()
    {
        if (! $this->shippingAddress || ! $this->billingAddress) {
            return false;
        }

        $fieldsToCheck = Arr::except(
            $this->billingAddress->getAttributes(),
            ['id', 'created_at', 'updated_at', 'cart_id', 'type', 'meta']
        );

        // Is the same until proven otherwise
        $isSame = true;

        foreach ($fieldsToCheck as $field => $value) {
            if ($this->shippingAddress->getAttribute($field) != $value) {
                $isSame = false;
            }
        }

        return $isSame;
    }

    /**
     * Return states for the shipping address.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getShippingStatesProperty()
    {
        if (! $this->shippingAddress) {
            return collect();
        }

        return State::whereCountryId($this->shippingAddress->country_id)->get();
    }

    /**
     * Return states for the shipping address.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getBillingStatesProperty()
    {
        if (! $this->billingAddress) {
            return collect();
        }

        return State::whereCountryId($this->billingAddress->country_id)->get();
    }

    /**
     * Display meta fields.
     *
     * @return void
     */
    public function getMetaFieldsProperty()
    {
        return (array) $this->cart->meta;
    }

    /**
     * Render the livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('adminhub::livewire.components.show')
            ->layout('adminhub::layouts.base');
    }

    /**
     * Returns the model which has slots associated.
     *
     * @return \Lunar\Models\Cart
     */
    protected function getSlotModel()
    {
        return $this->cart;
    }

    /**
     * Returns the contexts for any slots.
     *
     * @return array
     */
    protected function getSlotContexts()
    {
        return ['cart.all', 'cart.show'];
    }
}
