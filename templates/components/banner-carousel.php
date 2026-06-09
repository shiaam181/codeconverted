<?php
/**
 * Banner Carousel Component
 */
$position = $position ?? 'hero';
$banners = get_banners($position);

if (empty($banners)) return;
$count = count($banners);
?>
<div class="banner-section">
    <div class="banner-carousel" id="bannerCarousel">
        <div class="banner-track" id="bannerTrack">
            <?php foreach ($banners as $index => $banner): ?>
            <a href="<?= e($banner['link_url'] ?? '#') ?>" class="banner-slide">
                <img 
                    src="<?= e(img_url($banner['image_url'], ['w' => 800, 'h' => 480, 'resize' => 'cover', 'quality' => 75])) ?>" 
                    alt="<?= e($banner['title'] ?? 'Banner') ?>"
                    class="banner-image"
                    loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                    decoding="async"
                >
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if ($count > 1): ?>
    <div class="banner-dots" id="bannerDots">
        <?php for ($i = 0; $i < $count; $i++): ?>
        <button class="banner-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const track = document.getElementById('bannerTrack');
    const dots = document.querySelectorAll('#bannerDots .banner-dot');
    const count = <?= $count ?>;
    let current = 0;
    let interval;
    
    function goTo(i) {
        current = i;
        track.style.transform = `translateX(-${current * 100}%)`;
        dots.forEach((d, idx) => d.classList.toggle('active', idx === current));
    }
    
    function next() {
        goTo((current + 1) % count);
    }
    
    function startAutoplay() {
        interval = setInterval(next, 4500);
    }
    
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            clearInterval(interval);
            goTo(parseInt(dot.dataset.index));
            startAutoplay();
        });
    });
    
    // Touch swipe support
    let startX = null;
    track.addEventListener('touchstart', e => { startX = e.touches[0].clientX; });
    track.addEventListener('touchend', e => {
        if (startX === null) return;
        const diff = e.changedTouches[0].clientX - startX;
        if (diff < -40) goTo((current + 1) % count);
        else if (diff > 40) goTo((current - 1 + count) % count);
        startX = null;
        clearInterval(interval);
        startAutoplay();
    });
    
    if (count > 1) startAutoplay();
})();
</script>
