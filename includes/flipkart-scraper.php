<?php
/**
 * Flipkart Product Scraper
 * Scrapes product data from Flipkart URLs including all images.
 */

/**
 * Import a single product from a Flipkart URL.
 * Returns: ['title', 'price', 'mrp', 'brand', 'description', 'images' => [...urls], 'rating', 'rating_count']
 */
function scrape_flipkart_product(string $url): ?array {
    $html = fetch_page_html($url);
    if (!$html) return null;

    $result = [
        'title' => null,
        'price' => 0,
        'mrp' => null,
        'brand' => null,
        'description' => null,
        'images' => [],
        'rating' => null,
        'rating_count' => 0,
    ];

    // Suppress HTML warnings
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    // === TITLE ===
    // Flipkart uses <span class="VU-ZEz"> or <h1 class="..."> for product title
    $titleNodes = $xpath->query('//span[contains(@class,"VU-ZEz")] | //h1[contains(@class,"yhB1nd")] | //span[contains(@class,"B_NuCI")]');
    if ($titleNodes->length > 0) {
        $result['title'] = trim($titleNodes->item(0)->textContent);
    }
    // Fallback: og:title meta
    if (!$result['title']) {
        $ogTitle = $xpath->query('//meta[@property="og:title"]/@content');
        if ($ogTitle->length > 0) {
            $result['title'] = trim($ogTitle->item(0)->nodeValue);
        }
    }

    // === BRAND ===
    $brandNodes = $xpath->query('//span[contains(@class,"mEh187")] | //span[contains(@class,"G6XhRU")]');
    if ($brandNodes->length > 0) {
        $result['brand'] = trim($brandNodes->item(0)->textContent);
    }

    // === PRICE (selling price) ===
    $priceNodes = $xpath->query('//div[contains(@class,"Nx9bqj") and contains(@class,"CxhGGd")] | //div[contains(@class,"_30jeq3") and contains(@class,"_16Jk6d")]');
    if ($priceNodes->length > 0) {
        $priceText = trim($priceNodes->item(0)->textContent);
        $result['price'] = (float) preg_replace('/[^0-9.]/', '', $priceText);
    }
    // Fallback: look for any element with ₹ followed by numbers
    if ($result['price'] <= 0) {
        if (preg_match('/₹\s*([\d,]+)/', $html, $m)) {
            $result['price'] = (float) str_replace(',', '', $m[1]);
        }
    }

    // === MRP (original price / strikethrough) ===
    $mrpNodes = $xpath->query('//div[contains(@class,"yRaY8j")] | //div[contains(@class,"_3I9_wc")] | //span[contains(@class,"yRaY8j")]');
    if ($mrpNodes->length > 0) {
        $mrpText = trim($mrpNodes->item(0)->textContent);
        $mrpVal = (float) preg_replace('/[^0-9.]/', '', $mrpText);
        if ($mrpVal > $result['price']) {
            $result['mrp'] = $mrpVal;
        }
    }

    // === DESCRIPTION ===
    // Flipkart has product description in various divs
    $descNodes = $xpath->query('//div[contains(@class,"_1mXcCf")] | //div[contains(@class,"yN+eNk")] | //div[contains(@class,"_1AN87F")]//p');
    $descParts = [];
    foreach ($descNodes as $node) {
        $text = trim($node->textContent);
        if ($text && strlen($text) > 20) {
            $descParts[] = $text;
        }
    }
    if (!empty($descParts)) {
        $result['description'] = implode("\n\n", array_slice($descParts, 0, 3));
    }
    // Fallback: og:description
    if (!$result['description']) {
        $ogDesc = $xpath->query('//meta[@property="og:description"]/@content');
        if ($ogDesc->length > 0) {
            $result['description'] = trim($ogDesc->item(0)->nodeValue);
        }
    }

    // === IMAGES (all product images) ===
    // Flipkart stores images in <img> with src containing rukminim2.flixcart.com
    // They also have data in JSON-LD and img tags
    $images = [];
    
    // Method 1: Look for high-res images in the page (rukminim2 or rukminim1)
    if (preg_match_all('/https?:\/\/rukminim[12]\.flixcart\.com\/image\/[^"\'>\s]+/i', $html, $matches)) {
        foreach ($matches[0] as $imgUrl) {
            // Convert thumbnail URLs to high-res (832/832 or 416/416)
            $hiRes = preg_replace('/\/\d+\/\d+\//', '/832/832/', $imgUrl);
            if (!in_array($hiRes, $images)) {
                $images[] = $hiRes;
            }
        }
    }
    
    // Method 2: Look for og:image
    $ogImages = $xpath->query('//meta[@property="og:image"]/@content');
    foreach ($ogImages as $ogImg) {
        $imgUrl = trim($ogImg->nodeValue);
        if ($imgUrl && !in_array($imgUrl, $images)) {
            array_unshift($images, $imgUrl); // Put og:image first
        }
    }

    // Method 3: JSON-LD structured data
    $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
    foreach ($scriptNodes as $script) {
        $json = json_decode(trim($script->textContent), true);
        if ($json) {
            // Single product
            if (!empty($json['image'])) {
                $imgs = is_array($json['image']) ? $json['image'] : [$json['image']];
                foreach ($imgs as $img) {
                    if (is_string($img) && !in_array($img, $images)) {
                        $images[] = $img;
                    }
                }
            }
            // Get brand/name from JSON-LD
            if (!$result['title'] && !empty($json['name'])) {
                $result['title'] = $json['name'];
            }
            if (!$result['brand'] && !empty($json['brand']['name'])) {
                $result['brand'] = $json['brand']['name'];
            }
            // Rating from JSON-LD
            if (!empty($json['aggregateRating'])) {
                $result['rating'] = (float) ($json['aggregateRating']['ratingValue'] ?? 0);
                $result['rating_count'] = (int) ($json['aggregateRating']['ratingCount'] ?? 0);
            }
        }
    }

    // Deduplicate and limit images
    $result['images'] = array_values(array_unique(array_slice($images, 0, 15)));

    // === RATING (from HTML if not from JSON-LD) ===
    if (!$result['rating']) {
        $ratingNodes = $xpath->query('//div[contains(@class,"XQDdHH")] | //span[contains(@class,"_1lRcqv")]');
        if ($ratingNodes->length > 0) {
            $ratingText = trim($ratingNodes->item(0)->textContent);
            if (is_numeric($ratingText)) {
                $result['rating'] = (float) $ratingText;
            }
        }
    }
    if (!$result['rating_count']) {
        // Look for "X Ratings" text
        if (preg_match('/([\d,]+)\s*Ratings/i', $html, $m)) {
            $result['rating_count'] = (int) str_replace(',', '', $m[1]);
        }
    }

    // Validate we got something useful
    if (!$result['title'] && !$result['price']) {
        return null;
    }

    return $result;
}

/**
 * Import multiple products from a Flipkart category/search URL.
 * Returns array of product links found on the page.
 */
function scrape_flipkart_category(string $url): array {
    $html = fetch_page_html($url);
    if (!$html) return [];

    $productLinks = [];

    // Flipkart product links pattern: /product-name/p/itXXXXXX
    if (preg_match_all('#href="(/[^"]*?/p/it[^"]+)"#i', $html, $matches)) {
        foreach ($matches[1] as $path) {
            $fullUrl = 'https://www.flipkart.com' . $path;
            if (!in_array($fullUrl, $productLinks)) {
                $productLinks[] = $fullUrl;
            }
        }
    }

    // Also look for dl.flipkart.com links
    if (preg_match_all('#https?://dl\.flipkart\.com/[^"\'>\s]+#i', $html, $matches)) {
        foreach ($matches[0] as $link) {
            if (!in_array($link, $productLinks)) {
                $productLinks[] = $link;
            }
        }
    }

    return array_slice($productLinks, 0, 30); // Limit to 30 products
}

/**
 * Fetch page HTML with cURL (handles Flipkart redirects and mobile user-agent)
 */
function fetch_page_html(string $url): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-IN,en;q=0.9',
            'Cache-Control: no-cache',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING => '', // Accept all encodings (gzip, deflate)
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode >= 400 || !$html) {
        return null;
    }

    return $html;
}

/**
 * Full import: scrape product + create in database + save all images
 * Returns the created product data or null on failure.
 */
function import_flipkart_product_to_db(string $url, ?string $categoryId = null, int $stock = 10, bool $isActive = true, bool $isFeatured = false): ?array {
    $scraped = scrape_flipkart_product($url);
    if (!$scraped || !$scraped['title']) {
        return null;
    }

    // Calculate discount
    $discount = 0;
    if ($scraped['mrp'] && $scraped['mrp'] > $scraped['price']) {
        $discount = (int) round((($scraped['mrp'] - $scraped['price']) / $scraped['mrp']) * 100);
    }

    // Create product
    $slug = slugify($scraped['title']);
    $slug = substr($slug, 0, 70) . '-' . substr(md5(uniqid()), 0, 5);

    $productPayload = [
        'title' => $scraped['title'],
        'slug' => $slug,
        'description' => $scraped['description'],
        'brand' => $scraped['brand'],
        'price' => $scraped['price'],
        'mrp' => $scraped['mrp'],
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

    // Save all images
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
 * Import all products from a Flipkart category URL.
 * Returns ['imported' => count, 'failed' => count, 'total' => count]
 */
function import_flipkart_category_to_db(string $url, ?string $categoryId = null, int $stock = 10): array {
    $links = scrape_flipkart_category($url);
    $imported = 0;
    $failed = 0;

    foreach ($links as $productUrl) {
        $result = import_flipkart_product_to_db($productUrl, $categoryId, $stock);
        if ($result) {
            $imported++;
        } else {
            $failed++;
        }
        // Small delay to avoid rate-limiting
        usleep(500000); // 0.5 seconds
    }

    return [
        'imported' => $imported,
        'failed' => $failed,
        'total' => count($links),
    ];
}
