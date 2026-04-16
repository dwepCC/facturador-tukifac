@php
    use Illuminate\Support\Str;
    $path = explode('/', request()->path());
    $activeSlug = isset($path[1]) ? $path[1] : '';
@endphp
<div class="container tuki_category_strip">
    <div class="tuki_categories ecom-categories">
        <div class="ecom-categories__header">
            <div class="ecom-categories__title">Categorías</div>
            <a class="ecom-categories__all {{ $activeSlug === '' ? 'is-active' : '' }}" href="{{ route('tenant.ecommerce.index') }}">Ver todos</a>
        </div>

        <div id="ecomCategoryScroller" class="ecom-categories__scroller" role="navigation" aria-label="Categorías" tabindex="0">
            @foreach ($categories as $category)
                @php
                    $slug = Str::slug($category->name, '-');
                @endphp
                <a class="ecom-category-chip {{ $activeSlug === $slug ? 'is-active' : '' }}" href="{{ route('tenant.ecommerce.index', $slug) }}" draggable="false">
                    <span class="ecom-category-chip__img">
                        <img src="{{ asset('storage/uploads/categories/'. $category->image) }}" alt="{{ $category->name }}" loading="lazy" draggable="false">
                    </span>
                    <span class="ecom-category-chip__label">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script>
(function () {
    function initCategoryScrollerDrag() {
        var el = document.getElementById('ecomCategoryScroller');
        if (!el || el.dataset.tukiDragInit === '1') return;
        el.dataset.tukiDragInit = '1';

        var active = false;
        var dragActive = false;
        var startX = 0;
        var startScroll = 0;
        var pointerId = null;
        var THRESHOLD = 6;

        function prefersReducedMotion() {
            return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }

        function scrollStep(delta) {
            el.scrollBy({
                left: delta,
                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
            });
        }

        el.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                scrollStep(140);
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                scrollStep(-140);
            } else if (e.key === 'Home') {
                e.preventDefault();
                el.scrollTo({ left: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
            } else if (e.key === 'End') {
                e.preventDefault();
                el.scrollTo({ left: el.scrollWidth, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
            }
        });

        /* Evita que Chrome/Edge inicien arrastre nativo del enlace (URL), que roba el mousemove en PC */
        el.addEventListener(
            'dragstart',
            function (e) {
                if (e.target.closest && e.target.closest('.ecom-category-chip')) {
                    e.preventDefault();
                }
            },
            true
        );

        el.addEventListener(
            'pointerdown',
            function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                active = true;
                dragActive = false;
                startX = e.clientX;
                startScroll = el.scrollLeft;
                pointerId = e.pointerId;
                try {
                    el.setPointerCapture(e.pointerId);
                } catch (err) { /* noop */ }
            },
            true
        );

        el.addEventListener('pointermove', function (e) {
            if (!active || e.pointerId !== pointerId) return;
            var dx = e.clientX - startX;
            if (!dragActive && Math.abs(dx) > THRESHOLD) {
                dragActive = true;
                el.classList.add('is-dragging');
            }
            if (dragActive) {
                el.scrollLeft = startScroll - dx;
                e.preventDefault();
            }
        }, { passive: false });

        function endPointer(e) {
            if (!active || e.pointerId !== pointerId) return;
            var wasDrag = dragActive;
            active = false;
            pointerId = null;
            el.classList.remove('is-dragging');
            try {
                el.releasePointerCapture(e.pointerId);
            } catch (err) { /* noop */ }

            if (wasDrag) {
                el.addEventListener(
                    'click',
                    function stopMisclick(ev) {
                        ev.preventDefault();
                        ev.stopImmediatePropagation();
                    },
                    { capture: true, once: true }
                );
            }
            dragActive = false;
        }

        el.addEventListener('pointerup', endPointer);
        el.addEventListener('pointercancel', endPointer);
        el.addEventListener('lostpointercapture', function () {
            active = false;
            pointerId = null;
            dragActive = false;
            el.classList.remove('is-dragging');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCategoryScrollerDrag);
    } else {
        initCategoryScrollerDrag();
    }
})();
</script>
