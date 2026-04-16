
 function cart_add(data) {

    try {
			
        let raw = localStorage.getItem('products_cart');
        let array;
        try {
            array = JSON.parse(raw || '[]');
        } catch (e) {
            array = [];
        }
        if (!Array.isArray(array)) {
            array = [];
        }

        let item = JSON.parse(data)
        let found = array.find(x => x.id == item.id)

        if (!found) {
            array.push(item);
            localStorage.setItem('products_cart', JSON.stringify(array));
            productsCartDropDown();

						jQuery('#moda-succes-add-product').modal('show');
						// setTimeout(function() {
						// 	jQuery('#moda-succes-add-product').modal('hide');
						// }, 3000);
						
            calculateTotalCart();

            $('#product_added').html(`
							<div class="product-single-details-restaurant">
                            <h1 class="product-title">${item.description}</h1>
                            <div class="price-box">
                                <span class="product-price">S/ ${ Number(item.sale_unit_price).toFixed(2) }</span>
                            </div>
                            <div class="product-descrip">
                                <p>${item.name}</p>
                            </div>
								</div>`);

            $('#product_added_image').html(`<img src="/storage/uploads/items/${item.image_medium}" class="img" alt="product">`)
        }
        else {
            jQuery('#modal-already-product').modal('show');
        }

    } catch ({error}) {
        console.log(error)
    }


}

function productsCartDropDown()
{

	jQuery(".dropdown-cart-products").empty();
	jQuery(".cart-count").empty();
	let count = 0;
	let array;
	try {
		array = JSON.parse(localStorage.getItem('products_cart') || '[]');
	} catch (e) {
		array = [];
	}
	if (!Array.isArray(array)) {
		array = [];
	}
	count = array.length;

	var $shell = jQuery(".tuki_header_cart .tuki_mini_cart__shell");
	if ($shell.length) {
		$shell.toggleClass("is-empty", count === 0);
	}
	if (!count) {
		jQuery(".dropdown-cart-products").append(
			'<div class="tuki_mini_cart__empty">Tu carrito está vacío. Agrega productos desde la tienda.</div>'
		);
	} else if (typeof window.tukiMiniCartLineHtml === "function") {
		array.forEach(function(element) {
			jQuery(".dropdown-cart-products").append(window.tukiMiniCartLineHtml(element));
		});
	} else {
		array.forEach(element => {
			jQuery(".dropdown-cart-products").append(
				'<article class="tuki_mini_cart__item product"><div class="tuki_mini_cart__body"><span class="tuki_mini_cart__title">' +
				String(element.description || "") +
				"</span></div></article>"
			);
		});
	}

    jQuery(".cart-count").append(count);

}


function calculateTotalCart()
{

	let array;
	try {
		array = JSON.parse(localStorage.getItem('products_cart') || '[]');
	} catch (e) {
		array = [];
	}
	if (!Array.isArray(array)) {
		array = [];
	}
	let total = 0;
	array.forEach(element => {
		var qty = parseFloat(element.cantidad) > 0 ? parseFloat(element.cantidad) : 1;
		total += parseFloat(element.sale_unit_price) * qty;
	});

	$(".cart-total-price").empty();
    $(".cart-total-price").append(total.toFixed(2));


}

function logout()
{
	console.log("register logout")
	$.ajax({
		url: "/ecommerce/logout",
		method: 'get',
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		},
		success: function (data) {
			location.reload()
		},
		error: function (error_data) {

		}
	});
}
