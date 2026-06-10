<?php
/**
 * Product Grid Component
 * Usage: include with $products array set
 */

$gridTitle = $gridTitle ?? 'Products';
$products = $products ?? [];
?>
<section class="product-grid-section">
    <h3 class="section-title"><?= e($gridTitle) ?></h3>
    <?php if (empty($products)): ?>
    <p class="empty-message">No products yet.</p>
    <?php else: ?>
    <div class="product-grid" id="productGrid">
        <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <div id="loopLoader" style="text-align:center;padding:20px;color:#878787;font-size:13px;">Loading more...</div>
    <script>
    (function(){
        var grid = document.getElementById('productGrid');
        var loader = document.getElementById('loopLoader');
        var original = grid.innerHTML;
        var loading = false;
        
        function onScroll() {
            if (loading) return;
            var rect = loader.getBoundingClientRect();
            if (rect.top < window.innerHeight + 200) {
                loading = true;
                // Shuffle and append products to create variety feel
                var cards = grid.querySelectorAll('.product-card');
                var arr = Array.from(cards).slice(0, Math.min(cards.length, 20));
                // Shuffle
                for (var i = arr.length - 1; i > 0; i--) {
                    var j = Math.floor(Math.random() * (i + 1));
                    var tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
                }
                var html = '';
                arr.forEach(function(card) { html += card.outerHTML; });
                grid.insertAdjacentHTML('beforeend', html);
                setTimeout(function() { loading = false; }, 500);
            }
        }
        
        window.addEventListener('scroll', onScroll, {passive: true});
    })();
    </script>
    <?php endif; ?>
</section>
