@php
    $configurationModel = \App\Models\Tenant\Configuration::first();
    $defaultImage = $configurationModel->product_default_image ?? 'imagen-no-disponible.jpg';
    $defaultImagePath = $defaultImage === 'imagen-no-disponible.jpg'
        ? asset('logo/imagen-no-disponible.jpg')
        : asset('storage/defaults/' . $defaultImage);
@endphp

<div class="dropdown cart-dropdown tuki_header_cart">
    <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"></path><path d="M9 11v-5a3 3 0 0 1 6 0v5"></path></svg>
        <span class="cart-count">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-right tuki_mini_cart__dropdown" aria-label="Vista previa del carrito">
        <div class="dropdownmenu-wrapper tuki_mini_cart__shell">
            <div class="tuki_mini_cart__head">
                <span class="tuki_mini_cart__head-title">Tu carrito</span>
                <span class="tuki_mini_cart__head-badge">Vista previa</span>
            </div>

            <div class="dropdown-cart-products tuki_mini_cart__list"></div>

            <div class="tuki_mini_cart__total dropdown-cart-total">
                <div class="tuki_mini_cart__total-row">
                    <span class="tuki_mini_cart__total-label">Total estimado</span>
                    <span class="tuki_mini_cart__total-amount"><span class="tuki_mini_cart__total-currency">S/</span> <span class="cart-total-price">0.00</span></span>
                </div>
            </div>

            <div class="tuki_mini_cart__actions dropdown-cart-action">
                <a href="{{ route('tenant_detail_cart') }}" class="btn tuki_mini_cart__btn tuki_mini_cart__btn--primary">Ver carrito</a>
                <a href="{{ route('tenant.ecommerce.index') }}" class="btn tuki_mini_cart__btn tuki_mini_cart__btn--ghost">Seguir comprando</a>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script type="text/javascript">

	function remove(id)
	{
		let array = [];
		try {
			array = JSON.parse(localStorage.getItem('products_cart') || '[]');
		} catch (e) {
			array = [];
		}
		if (!Array.isArray(array)) {
			array = [];
		}
		let indexFound = array.findIndex( x=> x.id == id);
		if (indexFound >= 0) {
			array.splice(indexFound, 1);
		}
		localStorage.setItem('products_cart', JSON.stringify(array));
		populate();
		calculatetotal();
		if (typeof jQuery !== 'undefined') {
			jQuery(document).trigger('tukiProductsCartChanged');
		}
	}

	function calculatetotal()
	{
		let array = [];
		try {
			array = JSON.parse(localStorage.getItem('products_cart') || '[]');
		} catch (e) {
			array = [];
		}
		if (!Array.isArray(array)) {
			array = [];
		}
		let total = 0;
		array.forEach(function (element) {
			var qty = parseFloat(element.cantidad) > 0 ? parseFloat(element.cantidad) : 1;
			var price = parseFloat(element.sale_unit_price);
			if (isNaN(price)) {
				price = 0;
			}
			total += price * qty;
		});
		$(".cart-total-price").empty();
		$(".cart-total-price").append(total.toFixed(2));
	}

	function populate()
	{
		$(".dropdown-cart-products").empty();
		$(".cart-count").empty();
		let array = [];
		try {
			array = JSON.parse(localStorage.getItem('products_cart') || '[]');
		} catch (e) {
			array = [];
		}
		if (!Array.isArray(array)) {
			array = [];
		}
		let count = array.length;
		var $shell = $(".tuki_header_cart .tuki_mini_cart__shell");
		if ($shell.length) {
			$shell.toggleClass("is-empty", count === 0);
		}
		if (!count) {
			$(".dropdown-cart-products").append(
				'<div class="tuki_mini_cart__empty">Tu carrito está vacío. Agrega productos desde la tienda.</div>'
			);
		} else if (typeof window.tukiMiniCartLineHtml === "function") {
			array.forEach(function (element) {
				$(".dropdown-cart-products").append(window.tukiMiniCartLineHtml(element));
			});
		} else {
			array.forEach(function (element) {
				var d = String(element.description || "").replace(/</g, "&lt;");
				$(".dropdown-cart-products").append(
					'<article class="tuki_mini_cart__item product"><div class="tuki_mini_cart__body"><a class="tuki_mini_cart__title" href="/ecommerce/item/' +
					element.id + '">' + d + "</a></div></article>"
				);
			});
		}
		$(".cart-count").append(count);
	}

	
	$(function(){
    'use strict';
		populate();
		calculatetotal();
    });
</script>
@endpush