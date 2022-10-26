<?php

namespace XtendLunar\Features\ManageCarts\Livewire\Components;

use Closure;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters;
use Filament\Tables\Filters\Layout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Lunar\Hub\Http\Livewire\Traits\Notifies;
use Lunar\Models\Cart;
use Lunar\Models\CartAddress;
use Lunar\Models\Customer;

class CartsTable extends Component implements Tables\Contracts\HasTable
{
    use Notifies;
    use Tables\Concerns\InteractsWithTable;

    /**
     * Restrict records to a customer.
     *
     * @var Customer|null
     */
    public ?Customer $customer = null;

    /**
     * Whether to show filters.
     *
     * @var bool
     */
    public bool $filterable = true;

    /**
     * {@inheritDoc}
     */
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 20, 50, 100];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    /**
     * {@inheritDoc}
     */
    protected function getTableQuery(): Builder
    {
        $query = Cart::query();

        if ($this->customer) {
            $query = Cart::whereCustomerId($this->customer->id);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTableColumns(): array
    {
        $prefix = config('getcandy.database.table_prefix');

        return [
            TextColumn::make('id')->visible(auth()->user()->admin)->sortable()->searchable(),
            BadgeColumn::make('new_client')
                ->label('New client')
                ->colors([
                    'success' => fn ($state, $record): bool => $this->isNewClient($record) === 'YES',
                    'warning' => fn ($state, $record): bool => $this->isNewClient($record) === 'NO',
                ])
                ->getStateUsing(fn (Cart $record) => $this->isNewClient($record)),

            TextColumn::make('customer.fullName')
                ->label('Customer')
                ->sortable(query: function (Builder $query, string $direction) use ($prefix): Builder {
                    return $query->orderBy(
                        CartAddress::select('first_name')
                            ->whereColumn('order_id', $prefix.'carts.id')
                            ->where('type', 'billing'), $direction);
                })
                ->url(fn (Cart $record): string => route('hub.customers.show', ['customer' => $record->customer->id ?? 0])),
            TextColumn::make('billingAddress.country.name')
                ->getStateUsing(fn (Cart $record) => $record->billingAddress?->country?->emoji.' '.$record->billingAddress?->country?->name ?? '')
                ->sortable('name', function (Builder $query, string $direction) use ($prefix): Builder {
                    return $query->orderBy(
                        CartAddress::select('country_id')
                            ->whereColumn('order_id', $prefix.'carts.id')
                            ->where('type', 'billing'), $direction);
                })
                ->label('Country'),

            TextColumn::make('lines_count')
                ->counts('lines')
                ->hidden(false)
                ->sortable()
                ->getStateUsing(fn (Cart $record) => $record->lines->count()),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ];
    }

    protected function isNewClient(Cart|Model $order): string
    {
        return $order?->customer?->carts?->count() === 1 ? 'YES' : 'NO';
    }

    /**
     * {@inheritDoc}
     */
    protected function getTableRecordUrlUsing(): Closure
    {
        return fn (Cart $record): string => route('hub.carts.show', ['cart' => $record]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                  ->label(false)
                  ->icon('heroicon-o-eye')
                  ->url(fn (Cart $record): string => route('hub.carts.show', ['cart' => $record])),
        ];
    }

    protected function getTableFiltersLayout(): ?string
    {
        return Tables\Filters\Layout::AboveContent;
    }

    protected function getTableFiltersFormColumns(): int | array
    {
        return match ($this->getTableFiltersLayout()) {
            Layout::AboveContent, Layout::BelowContent => [
                'sm' => 2,
                'lg' => 3,
                'xl' => 4,
                '2xl' => 3,
            ],
            default => 1,
        };
    }

    /**
     * {@inheritDoc}
     */
    protected function getTableFilters(): array
    {
        return [];
    }

    /**
     * Render the livewire component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('adminhub::livewire.components.tables.base-table')
            ->layout('adminhub::layouts.base');
    }
}
