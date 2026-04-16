@php
    // $tagid = request()->query('tagid');
    $tagid = Request::segment(3);
    $catetgory_segment = strtolower(Request::segment(2));
    $__tuki_mm_phone = isset($information) ? trim((string) ($information->information_contact_phone ?? '')) : '';
    $__tuki_mm_tel_href = $__tuki_mm_phone !== '' ? preg_replace('/\s+/', '', $__tuki_mm_phone) : '';
    $__tuki_mm_wa = isset($information) ? trim((string) ($information->phone_whatsapp ?? '')) : '';
@endphp
<div class="mobile-menu-wrapper">
    <span class="mobile-menu-close" role="button" aria-label="Cerrar menú"><i class="fas fa-times"></i></span>
    <nav class="mobile-nav">
        <ul class="mobile-menu">
            <li class="{{ (!$tagid) ? 'active':'' }}"><a href="{{ route("tenant.ecommerce.index") }}">Home</a></li>
            <li class="{{ ($catetgory_segment) && ($catetgory_segment == 'category') ? 'active':'' }}">
                <a href="#">Categorias</a>
                <ul>
                    @foreach ($items as $item)
                        <li class="{{ ($tagid == $item->id) ? 'active':'' }}">
                            <a href="{{ route("tenant.ecommerce.category", ['category' => $item->id]) }}">
                                {{$item->name}}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
            {{-- <li>
                <a href="product.html">Products</a>
                <ul>
                    <li>
                        <a href="#">Variations</a>
                        <ul>
                            <li><a href="product.html">Horizontal Thumbnails</a></li>
                            <li><a href="product-full-width.html">Vertical Thumbnails<span
                                        class="tip tip-hot">Hot!</span></a></li>
                            <li><a href="product.html">Inner Zoom</a></li>
                            <li><a href="product-addcart-sticky.html">Addtocart Sticky</a></li>
                            <li><a href="product-sidebar-left.html">Accordion Tabs</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#">Variations</a>
                        <ul>
                            <li><a href="product-sticky-tab.html">Sticky Tabs</a></li>
                            <li><a href="product-simple.html">Simple Product</a></li>
                            <li><a href="product-sidebar-left.html">With Left Sidebar</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#">Product Layout Types</a>
                        <ul>
                            <li><a href="product.html">Default Layout</a></li>
                            <li><a href="product-extended-layout.html">Extended Layout</a></li>
                            <li><a href="product-full-width.html">Full Width Layout</a></li>
                            <li><a href="product-grid-layout.html">Grid Images Layout</a></li>
                            <li><a href="product-sticky-both.html">Sticky Both Side Info<span
                                        class="tip tip-hot">Hot!</span></a></li>
                            <li><a href="product-sticky-info.html">Sticky Right Side Info</a></li>
                        </ul>
                    </li>
                </ul>
            </li> --}}
            {{-- <li>
                <a href="#">Pages<span class="tip tip-hot">Hot!</span></a>
                <ul>
                    <li><a href="cart.html">Shopping Cart</a></li>
                    <li>
                        <a href="#">Checkout</a>
                        <ul>
                            <li><a href="checkout-shipping.html">Checkout Shipping</a></li>
                            <li><a href="checkout-shipping-2.html">Checkout Shipping 2</a></li>
                            <li><a href="checkout-review.html">Checkout Review</a></li>
                        </ul>
                    </li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="#" class="login-link">Login</a></li>
                    <li><a href="forgot-password.html">Forgot Password</a></li>
                </ul>
            </li> --}}
            {{-- <li><a href="blog.html">Blog</a>
                <ul>
                    <li><a href="single.html">Blog Post</a></li>
                </ul>
            </li> --}}
            <li><a href="{{ route('tenant_detail_cart') }}">Ver carrito</a></li>
            {{-- <li><a href="#">Special Offer!<span class="tip tip-hot">Hot!</span></a></li>
            <li><a href="#">Buy Porto!</a></li> --}}
        </ul>
    </nav><!-- End .mobile-nav -->

    @if ($__tuki_mm_phone !== '' || $__tuki_mm_wa !== '')
        <section class="tuki_mobile_menu__contact" aria-label="Atención al cliente">
            <div class="tuki_mobile_menu__contact-title">Atención al cliente</div>
            @if ($__tuki_mm_phone !== '')
                <a class="tuki_mobile_menu__contact-row tuki_mobile_menu__contact-row--tel" href="tel:{{ $__tuki_mm_tel_href }}" rel="nofollow">
                    <span class="tuki_mobile_menu__contact-icon" aria-hidden="true"><i class="fas fa-phone" aria-hidden="true"></i></span>
                    <span class="tuki_mobile_menu__contact-text">{{ $__tuki_mm_phone }}</span>
                </a>
            @endif
            @if ($__tuki_mm_wa !== '')
                <a class="tuki_mobile_menu__contact-row tuki_mobile_menu__contact-row--wa" href="https://wa.me/{{ $__tuki_mm_wa }}" target="_blank" rel="noopener noreferrer">
                    <span class="tuki_mobile_menu__contact-icon" aria-hidden="true"><i class="fab fa-whatsapp" aria-hidden="true"></i></span>
                    <span class="tuki_mobile_menu__contact-text">WhatsApp</span>
                </a>
            @endif
        </section>
    @endif

    <div class="social-icons">
        @if($information->link_facebook)
            <a href="{{$information->link_facebook}}" class="social-icon" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
        @endif

        @if($information->link_twitter)
            <a href="{{$information->link_twitter}}" class="social-icon" target="_blank" rel="noopener noreferrer"><i class="fab fa-twitter"></i></a>
        @endif

        @if($information->link_youtube)
            <a href="{{$information->link_youtube}}" class="social-icon" target="_blank"><i class="fab fa-youtube"></i></a>
        @endif
    </div><!-- End .social-icons -->
</div><!-- End .mobile-menu-wrapper -->
