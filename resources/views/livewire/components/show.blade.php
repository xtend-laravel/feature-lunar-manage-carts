<section>
    <header class="flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-900 md:text-2xl">
            <span class="text-gray-500">
                {{ __('Cart') }}
            </span>

            <span class="text-[#CFA55B]">#{{ $cart->id }}</span> {{ $cart->customer->fullName }}
        </h1>
    </header>
</section>
