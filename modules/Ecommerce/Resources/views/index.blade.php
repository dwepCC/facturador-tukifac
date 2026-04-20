@extends('ecommerce::layouts.master')

@section('content')
@php
    $categorySlug = isset($activeCategorySlug) && $activeCategorySlug !== '' ? $activeCategorySlug : null;
    $isStoreHome = !$categorySlug;
    $heroFull = $isStoreHome && isset($full_width_banner) && $full_width_banner;
@endphp

<div class="tuki_storefront tuki_catalog ecom-page{{ $isStoreHome ? ' tuki_storefront--home' : ' tuki_storefront--catalog' }}{{ $heroFull ? ' tuki_storefront--hero-full' : '' }}">
    @if($isStoreHome)
        <section class="tuki_hero ecom-hero{{ $heroFull ? ' ecom-hero--full tuki_hero--full' : '' }}">
            <div class="{{ $heroFull ? 'tuki_hero__shell tuki_hero__shell--wide' : 'tuki_hero__shell container' }}">
                @include('ecommerce::layouts.partials_ecommerce.home_slider')
            </div>
        </section>
    @else
        <section class="tuki_hero ecom-hero ecom-hero--compact tuki_hero--compact">
            <div class="container">
                <div class="ecom-hero__content">
                    <h1 class="ecom-hero__title">{{ str_replace('-', ' ', $categorySlug) }}</h1>
                    <div class="ecom-hero__subtitle">Explora productos por categoría</div>
                </div>
            </div>
        </section>
    @endif

    <section class="tuki_section tuki_section--categories ecom-section ecom-section--categories">
        @include('ecommerce::layouts.partials_ecommerce.categories')
    </section>

    <section class="tuki_section tuki_section--products ecom-section ecom-section--products">
        <div class="container">
            <div class="ecom-section__header tuki_section_head">
                <h2 class="ecom-section__title">Explora nuestros productos</h2>
            </div>
            <div class="row ecom-products-grid">
                @include('ecommerce::layouts.partials_ecommerce.list_products')
            </div>
            <div class="ecom-pagination">
                {{ $dataPaginate->onEachSide(1)->links('restaurant::layouts.partials.pagination') }}
            </div>
        </div>
    </section>

    <section class="tuki_section tuki_section--offers ecom-section ecom-section--offers">
        @include('ecommerce::layouts.partials_ecommerce.offers')
    </section>
</div>
@endsection
