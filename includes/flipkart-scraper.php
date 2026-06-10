<?php
/**
 * Flipkart Product Scraper
 * PHP port of the React/TypeScript flipkart-import.functions.ts
 * Scrapes product data from Flipkart URLs including all images and variants.
 */

// ─── Image Filtering ──────────────────────────────────────────────────────────

$IMAGE_REJECT_KEYWORDS = [
    'placeholder', 'loading', 'spinner', 'logo', 'icon', 'sprite',
    'banner', 'advertisement', '/ads/', 'rating', 'fk-cp-zion', 'promos/', 'fa_62673a.png',
];

function is_likely_product_image(string $url): bool {
    global $IMAGE_REJECT_KEYWORDS;
    $lower = strtolower($url);
    if (preg_match('/^data:|^blob:/i', $lower)) return false;
    // Accept Flipkart CDN hosts (rukmini, rukminim1, rukminim2, etc.)
    if (!preg_match('/^https?:\/\/rukmini[a-z0-9]*\.flixcart\.com\//i', $lower)) return false;
    if (preg_match('/\.(svg|gif)(\?|$)/i', $lower)) return false;
    if (!preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $lower)) return false;
    foreach ($IMAGE_REJECT_KEYWORDS as $kw) {
        if (strpos($lower, $kw) !== false) return false;
    }
    return true;
}

function optimize_image_url(string $url): string {
    $u = html_entity_decode($url);
    $u = str_replace(['\\/', '\\u002F'], '/', $u);
    $u = urldecode($u);
    // Replace any size segment with high-res 832x832
    $u = preg_replace('/\/(?:\{@width\}|\d{2,4})\/(?:\{@height\}|\d{2,4})\//', '/832/832/', $u);
    // Clean query params
    $u = preg_replace('/[?&]q=(?:\{@quality\}|\d+)/i', '', $u);
    $u = preg_replace('/[?&]_=\d+/', '', $u);
    // Add quality param
    $sep = strpos($u, '?') !== false ? '&' : '?';
    if (!preg_match('/[?&]q=/', $u)) $u .= $sep . 'q=90';
    return $u;
}

function image_identity(string $url): string {
    $noQuery = explode('?', $url)[0];
    $noSize = preg_replace('/\/(?:\{@width\}|\d{2,4})\/(?:\{@height\}|\d{2,4})\//', '/', $noQuery);
    if (preg_match('/\/([a-z0-9_-]{6,})\.(?:jpe?g|png|webp)$/i', $noSize, $m)) {
        return strtolower($m[1]);
    }
    return strtolower($noSize);
}

// ─── Text Cleaning ────────────────────────────────────────────────────────────

function decode_entities(string $s): string {
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function clean_title(string $name): string {
    $n = decode_entities($name);
    $n = preg_replace('/^Flipkart\.com\s*\|\s*/i', '', $n);
    $n = preg_replace('/^Flipkart\s*-?\s*/i', '', $n);
    $n = preg_replace('/\s*-\s*Buy\s+.*$/i', '', $n);
    $n = preg_replace('/\s*\|\s*Flipkart\.com\s*$/i', '', $n);
    $n = preg_replace('/^\s*Add to Compare\s*[:\-–]?\s*/i', '', $n);
    $n = preg_replace('/\s*Add to Compare\s*/i', ' ', $n);
    $n = preg_replace('/^\s*(?:Sponsored|Assured|New Arrival|Bestseller|Trending)\s*[:\-–]?\s*/i', '', $n);
    return trim(preg_replace('/\s+/', ' ', $n));
}

function clean_description(string $desc): string {
    $d = decode_entities($desc);
    $d = preg_replace('/^Flipkart\.com\s*:\s*/i', '', $d);
    $d = preg_replace('/Buy\s+[^.]*only\s+for\s+Rs\.\s+from\s+Flipkart\.com\.\s*/i', '', $d);
    $d = preg_replace('/Only\s+Genuine\s+Products\.\s*/i', '', $d);
    $d = preg_replace('/\d+\s+Day\s+Replacement\s+Guarantee\.\s*/i', '', $d);
    $d = preg_replace('/Free\s+Shipping\.\s*/i', '', $d);
    $d = preg_replace('/Cash\s+On\s+Delivery\s*!?\s*/i', '', $d);
    $d = trim(preg_replace('/\s+/', ' ', $d));
    if (strlen($d) > 500) $d = substr($d, 0, 497) . '...';
    return $d;
}

// ─── Image Extraction ─────────────────────────────────────────────────────────

function extract_image_urls(string $text): array {
    $normalized = decode_entities($text);
    $normalized = str_replace(['\\/', '\\u002F'], '/', $normalized);
    
    $images = [];
    $seen = [];
    
    // Match all Flipkart CDN image URLs
    if (preg_match_all('/https?:\/\/rukmini[a-z0-9]*\.flixcart\.com\/[^"\'<>\s,)\\\\]+?\.(?:jpe?g|png|webp)(?:\?[^"\'<>\s,)\\\\]*)?/i', $normalized, $matches)) {
        foreach ($matches[0] as $url) {
            $optimized = optimize_image_url($url);
            if (!is_likely_product_image($optimized)) continue;
            $id = image_identity($optimized);
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            $images[] = $optimized;
        }
    }
    
    return $images;
}

function extract_gallery_images(string $html): array {
    $normalized = decode_entities($html);
    $normalized = str_replace(['\\/', '\\u002F'], '/', $normalized);
    
    $images = [];
    $seen = [];
    
    $addImage = function(string $url) use (&$images, &$seen) {
        $optimized = optimize_image_url($url);
        if (!is_likely_product_image($optimized)) return;
        $id = image_identity($optimized);
        if (isset($seen[$id])) return;
        $seen[$id] = true;
        $images[] = $optimized;
    };
    
    // 1. Scan multimedia/gallery widget blocks
    $widgetMarkers = [
        'default_fk_pp_multimedia_inline_slider',
        'ATLAS_MULTIMEDIA_INLINE_SLIDER',
        'multiMediaViewData_0',
        '"type":"MULTI_MEDIA"',
        '"widgetType":"MULTIMEDIA"',
    ];
    foreach ($widgetMarkers as $marker) {
        $pos = strpos($normalized, $marker);
        while ($pos !== false) {
            $section = substr($normalized, $pos, 200000);
            foreach (extract_image_urls($section) as $url) $addImage($url);
            $pos = strpos($normalized, $marker, $pos + strlen($marker));
        }
    }
    
    // 2. Extract from JSON image keys (ProductPageContext)
    $productPageStart = strpos($normalized, '"type":"ProductPageContext"');
    $productPageHtml = $productPageStart !== false ? substr($normalized, $productPageStart) : $normalized;
    
    $imageKeys = ['imageUrl', 'dynamicImageUrl', 'actualImageUrl', 'highResImage', 'url', 'src'];
    foreach ($imageKeys as $key) {
        if (preg_match_all('/"' . $key . '"\s*:\s*"(https?:\/\/rukmini(?:m)?\d?\.flixcart\.com\/image\/[^"\\\\]+)"/i', $productPageHtml, $m)) {
            foreach ($m[1] as $url) $addImage($url);
        }
    }
    
    // 3. Fallback: regex over first 500K of product page slice
    if (count($images) < 2) {
        $slice = substr($productPageHtml, 0, 500000);
        foreach (extract_image_urls($slice) as $url) $addImage($url);
    }
    
    return array_slice($images, 0, 16);
}

// ─── HTML Parser ──────────────────────────────────────────────────────────────

function parse_flipkart_html(string $html): array {
    $result = [
        'title' => '',
        'price' => 0,
        'mrp' => null,
        'description' => '',
        'brand' => null,
        'images' => [],
        'rating' => null,
        'rating_count' => 0,
    ];
    
    // === TITLE ===
    if (preg_match('/<h1[^>]*>([\s\S]*?)<\/h1>/i', $html, $m)) {
        $result['title'] = strip_tags($m[1]);
    }
    if (!$result['title'] && preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
        $result['title'] = $m[1];
    }
    if (!$result['title'] && preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
        $result['title'] = $m[1];
    }
    $result['title'] = clean_title($result['title']);
    
    // === PRICE ===
    // Pattern: ₹X then ₹Y (first is selling price, second is MRP, or vice versa)
    if (preg_match('/₹\s*([0-9,]+)[\s\S]{0,400}?₹\s*([0-9,]+)/', $html, $m)) {
        $a = (int) str_replace(',', '', $m[1]);
        $b = (int) str_replace(',', '', $m[2]);
        $result['price'] = min($a, $b);
        $max = max($a, $b);
        $result['mrp'] = ($max > $result['price']) ? $max : null;
    } else {
        if (preg_match('/₹\s*([0-9,]+)/', $html, $m)) {
            $result['price'] = (int) str_replace(',', '', $m[1]);
        }
        if (!$result['price'] && preg_match('/"price"\s*:\s*"?([0-9,]+)"?/i', $html, $m)) {
            $result['price'] = (int) str_replace(',', '', $m[1]);
        }
    }
    
    // === IMAGES (gallery extraction - same as React version) ===
    $result['images'] = extract_gallery_images($html);
    
    // Fallback: og:image
    if (empty($result['images'])) {
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+rukmini(?:m)?[^"\']+)["\']/i', $html, $m)) {
            $optimized = optimize_image_url($m[1]);
            if (is_likely_product_image($optimized)) {
                $result['images'][] = $optimized;
            }
        }
    }
    
    // === DESCRIPTION ===
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
        $result['description'] = $m[1];
    }
    if (!$result['description'] && preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
        $result['description'] = $m[1];
    }
    $result['description'] = clean_description($result['description']);
    
    // === BRAND ===
    if (preg_match('/"brand"\s*:\s*\{?\s*"name"\s*:\s*"([^"]+)"/i', $html, $m)) {
        $result['brand'] = $m[1];
    }
    
    // === RATING (from JSON-LD) ===
    if (preg_match('/"ratingValue"\s*:\s*"?([0-9.]+)"?/i', $html, $m)) {
        $result['rating'] = (float) $m[1];
    }
    if (preg_match('/"ratingCount"\s*:\s*"?([0-9,]+)"?/i', $html, $m)) {
        $result['rating_count'] = (int) str_replace(',', '', $m[1]);
    }
    // Fallback rating count from text
    if (!$result['rating_count'] && preg_match('/([\d,]+)\s*Ratings/i', $html, $m)) {
        $result['rating_count'] = (int) str_replace(',', '', $m[1]);
    }
    
    return $result;
}

// ─── Category/Listing Parser ──────────────────────────────────────────────────

function parse_listing_product_links(string $html, int $limit = 30): array {
    $normalized = decode_entities($html);
    $normalized = str_replace(['\\/', '\\u002F'], '/', $normalized);
    $links = [];
    
    // Find product links: /product-name/p/itXXXXXX
    if (preg_match_all('/href=["\']([^"\']*\/p\/itm[^"\']*)["\']/', $normalized, $matches)) {
        foreach ($matches[1] as $path) {
            $url = (strpos($path, 'http') === 0) ? $path : 'https://www.flipkart.com' . $path;
            // Skip sponsored
            if (!in_array($url, $links)) {
                $links[] = $url;
            }
            if (count($links) >= $limit) break;
        }
    }
    
    return $links;
}

// ─── HTTP Fetch ───────────────────────────────────────────────────────────────

function fetch_page_html(string $url): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-IN,en;q=0.9',
            'Cache-Control: no-cache',
            'Referer: https://www.flipkart.com/',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING => '', // gzip/deflate
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode >= 400 || !$html || strlen($html) < 2000) {
        return null;
    }

    // Check if blocked
    $lower = strtolower(substr($html, 0, 4000));
    if (strpos($lower, 'access denied') !== false || strpos($lower, 'captcha') !== false) {
        return null;
    }

    return $html;
}

// ─── Full Import Functions ────────────────────────────────────────────────────

/**
 * Scrape a single Flipkart product URL.
 * Returns parsed data: title, price, mrp, brand, description, images[], rating, rating_count
 */
function scrape_flipkart_product(string $url): ?array {
    $html = fetch_page_html($url);
    if (!$html) return null;
    
    $parsed = parse_flipkart_html($html);
    
    if (!$parsed['title'] && !$parsed['price']) return null;
    
    return $parsed;
}

/**
 * Get product links from a Flipkart category/search page.
 */
function scrape_flipkart_category(string $url): array {
    $html = fetch_page_html($url);
    if (!$html) return [];
    return parse_listing_product_links($html, 30);
}

/**
 * Full import: scrape product + create in database + save ALL images
 */
function import_flipkart_product_to_db(string $url, ?string $categoryId = null, int $stock = 10, bool $isActive = true, bool $isFeatured = false, ?int $priceMin = null, ?int $priceMax = null): ?array {
    $scraped = scrape_flipkart_product($url);
    if (!$scraped || !$scraped['title']) return null;

    // Check for duplicates by title (skip if already exists)
    $existing = supabase_query('products', [
        'title' => 'eq.' . $scraped['title'],
        'select' => 'id',
        'limit' => '1',
    ]);
    if (!empty($existing) && !isset($existing['error']) && !empty($existing[0]['id'])) {
        // Product already exists, skip it
        return null;
    }

    // Apply random price override if range is set
    $price = $scraped['price'];
    $mrp = $scraped['mrp'];
    if ($priceMin !== null && $priceMax !== null && $priceMin > 0 && $priceMax >= $priceMin) {
        // MRP stays as Flipkart's real price, selling price is randomized
        $mrp = $scraped['mrp'] ?: $scraped['price']; // Keep real Flipkart price as MRP
        $price = rand($priceMin, $priceMax); // Random offer price
        // Make sure price is less than MRP
        if ($price >= $mrp) {
            $price = (int) round($mrp * 0.7); // 30% off if random is too high
        }
    } elseif ($priceMin !== null && $priceMin > 0) {
        $mrp = $scraped['mrp'] ?: $scraped['price'];
        $price = $priceMin;
        if ($price >= $mrp) {
            $price = (int) round($mrp * 0.7);
        }
    }

    $discount = 0;
    if ($mrp && $mrp > $price) {
        $discount = (int) round((($mrp - $price) / $mrp) * 100);
    }

    $slug = slugify($scraped['title']);
    $slug = substr($slug, 0, 70) . '-' . substr(md5(uniqid()), 0, 5);

    $productPayload = [
        'title' => $scraped['title'],
        'slug' => $slug,
        'description' => $scraped['description'] ?: null,
        'brand' => $scraped['brand'],
        'price' => $price,
        'mrp' => $mrp,
        'discount_percent' => $discount,
        'stock' => $stock,
        'rating' => $scraped['rating'],
        'rating_count' => $scraped['rating_count'],
        'category_id' => $categoryId,
        'is_active' => $isActive,
        'is_featured' => $isFeatured,
    ];

    $result = supabase_query('products', [], 'POST', $productPayload);

    if (empty($result) || isset($result['error']) || empty($result[0]['id'])) {
        return null;
    }

    $productId = $result[0]['id'];

    // Save ALL images to product_images table
    if (!empty($scraped['images'])) {
        foreach ($scraped['images'] as $i => $imgUrl) {
            supabase_query('product_images', [], 'POST', [
                'product_id' => $productId,
                'url' => $imgUrl,
                'sort_order' => $i,
            ]);
        }
    }

    return array_merge($result[0], ['images_count' => count($scraped['images'])]);
}

/**
 * Import all products from a Flipkart category/search URL.
 */
function import_flipkart_category_to_db(string $url, ?string $categoryId = null, int $stock = 10, ?int $priceMin = null, ?int $priceMax = null): array {
    $links = scrape_flipkart_category($url);
    $imported = 0;
    $failed = 0;

    foreach ($links as $productUrl) {
        $result = import_flipkart_product_to_db($productUrl, $categoryId, $stock, true, false, $priceMin, $priceMax);
        if ($result) {
            $imported++;
        } else {
            $failed++;
        }
        // Delay to avoid rate-limiting
        usleep(500000); // 0.5 seconds
    }

    return [
        'imported' => $imported,
        'failed' => $failed,
        'total' => count($links),
    ];
}



// ─── Tenant-aware Import Wrappers ─────────────────────────────────────────────

/**
 * Import a single Flipkart product for a tenant.
 * Returns product array with title on success, null on failure.
 */
function flipkart_import_product(string $url, string $tenantId): ?array {
    $scraped = scrape_flipkart_product($url);
    if (!$scraped || !$scraped['title']) return null;
    
    $discount = 0;
    if ($scraped['mrp'] && $scraped['mrp'] > $scraped['price']) {
        $discount = (int) round((($scraped['mrp'] - $scraped['price']) / $scraped['mrp']) * 100);
    }
    
    $slug = slugify($scraped['title']);
    $slug = substr($slug, 0, 70) . '-' . substr(md5(uniqid()), 0, 5);
    
    $payload = [
        'tenant_id' => $tenantId,
        'title' => $scraped['title'],
        'slug' => $slug,
        'description' => $scraped['description'] ?: null,
        'brand' => $scraped['brand'],
        'price' => $scraped['price'],
        'mrp' => $scraped['mrp'],
        'discount_percent' => $discount,
        'stock' => 10,
        'rating' => $scraped['rating'],
        'rating_count' => $scraped['rating_count'],
        'is_active' => true,
        'is_featured' => false,
    ];
    
    $result = supabase_query('products', [], 'POST', $payload);
    if (empty($result) || isset($result['error'])) return null;
    
    $newId = is_array($result[0] ?? null) ? $result[0]['id'] : ($result['id'] ?? null);
    
    if ($newId && !empty($scraped['images'])) {
        foreach ($scraped['images'] as $i => $imgUrl) {
            supabase_query('product_images', [], 'POST', [
                'product_id' => $newId,
                'url' => $imgUrl,
                'sort_order' => $i,
            ]);
        }
    }
    
    return $scraped;
}

/**
 * Import products from a Flipkart category/search page for a tenant.
 * Returns count of successfully imported products.
 */
function flipkart_import_category(string $url, string $tenantId, int $limit = 12): int {
    $links = scrape_flipkart_category($url);
    if (empty($links)) return 0;
    
    $links = array_slice($links, 0, $limit);
    $count = 0;
    
    foreach ($links as $productUrl) {
        $result = flipkart_import_product($productUrl, $tenantId);
        if ($result) $count++;
        usleep(300000); // 0.3s delay to avoid rate limiting
    }
    
    return $count;
}
