<?php
// index.php — dynamic storefront using your products table

// ---- DEBUG (remove in production) ----
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// -------------------------------------

require __DIR__ . '/db_connect.php';

/**
 * If your admin panel stores product images as "uploads/products/xyz.jpg"
 * and THIS file lives in a different folder, set a URL prefix here.
 * Examples:
 *   ''                -> images live relative to this file (same folder depth)
 *   'admin/'          -> images live under /admin/uploads/...
 *   '/toDo/admin/'    -> absolute from webroot
 */
const PUBLIC_UPLOADS_PREFIX = ''; // change to 'admin/' if needed

// ----- helpers -----
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n) { return '$' . number_format((float)$n, 2); }
function img_or_placeholder(?string $relPath, int $w = 300, int $h = 300): string {
    $relPath = trim((string)$relPath);
    if ($relPath !== '') {
        $try1 = __DIR__ . '/' . $relPath;
        $try2 = __DIR__ . '/' . PUBLIC_UPLOADS_PREFIX . $relPath;
        if (is_file($try1)) return $relPath;
        if (is_file($try2)) return PUBLIC_UPLOADS_PREFIX . $relPath;
    }
    return "https://via.placeholder.com/{$w}x{$h}.png?text=No+Image";
}
function sale_badge(?float $price, ?float $sale): string {
    if ($sale !== null && $sale > 0 && $price > 0 && $sale < $price) {
        $pct = round(100 - ($sale / $price) * 100);
        return "<p class=\"showcase-badge\">{$pct}%</p>";
    }
    return '';
}

// ----- data fetch -----
$limitGrid = 12;

// categories + counts (sidebar)
$categories = [];
$res = $conn->query("SELECT category, COUNT(*) AS cnt 
                     FROM products 
                     WHERE status='active' AND category IS NOT NULL AND category<>'' 
                     GROUP BY category 
                     ORDER BY category ASC");
while ($row = $res->fetch_assoc()) { $categories[] = $row; }

// new products for main grid
$stmt = $conn->prepare("SELECT id, product_name, sku, category, price, sale_price, quantity, product_image, is_featured, created_at
                        FROM products
                        WHERE status='active'
                        ORDER BY created_at DESC
                        LIMIT ?");
$stmt->bind_param("i", $limitGrid);
$stmt->execute();
$newProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "New Arrivals" (small cards) – recent 8
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image
                        FROM products
                        WHERE status='active'
                        ORDER BY created_at DESC
                        LIMIT 8");
$stmt->execute();
$newArrivals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "Trending" – by stock qty (fallback to recent if many zeros)
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image, quantity
                        FROM products
                        WHERE status='active'
                        ORDER BY quantity DESC, created_at DESC
                        LIMIT 8");
$stmt->execute();
$trending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// "Top Rated" – use featured flag as a proxy
$stmt = $conn->prepare("SELECT id, product_name, category, price, sale_price, product_image
                        FROM products
                        WHERE status='active'
                        ORDER BY is_featured DESC, created_at DESC
                        LIMIT 8");
$stmt->execute();
$topRated = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Anon - eCommerce Website</title>

  <link rel="shortcut icon" href="./assets/images/logo/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="./assets/css/style-prefix.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    /* a few tiny tweaks for DB-driven content */
    .product-grid .showcase-title { min-height: 2.75rem; line-height: 1.35; }
    .price-box .price { font-weight: 600; }
    .showcase-badge { text-transform: none; }
    .sidebar-submenu-title .stock { margin-left: .5rem; }
  </style>
</head>
<body>

  <div class="overlay" data-overlay></div>

  <!-- ======== modal + toast (unchanged) ======== -->
  <div class="modal" data-modal>
    <div class="modal-close-overlay" data-modal-overlay></div>
    <div class="modal-content">
      <button class="modal-close-btn" data-modal-close><ion-icon name="close-outline"></ion-icon></button>
      <div class="newsletter-img">
        <img src="./assets/images/newsletter.png" alt="subscribe newsletter" width="400" height="400">
      </div>
      <div class="newsletter">
        <form action="#">
          <div class="newsletter-header">
            <h3 class="newsletter-title">Subscribe Newsletter.</h3>
            <p class="newsletter-desc">Subscribe the <b>Anon</b> to get latest products and discount update.</p>
          </div>
          <input type="email" name="email" class="email-field" placeholder="Email Address" required>
          <button type="submit" class="btn-newsletter">Subscribe</button>
        </form>
      </div>
    </div>
  </div>

  <div class="notification-toast" data-toast>
    <button class="toast-close-btn" data-toast-close><ion-icon name="close-outline"></ion-icon></button>
    <div class="toast-banner">
      <img src="./assets/images/products/jewellery-1.jpg" alt="Rose Gold Earrings" width="80" height="70">
    </div>
    <div class="toast-detail">
      <p class="toast-message">Someone in new just bought</p>
      <p class="toast-title">Rose Gold Earrings</p>
      <p class="toast-meta"><time datetime="PT2M">2 Minutes</time> ago</p>
    </div>
  </div>

  <!-- ======== HEADER (unchanged except it’s now PHP file) ======== -->
  <header>
    <!-- top bar, main header, desktop + mobile menus… keep your original -->
    <!-- (omitted here for brevity; keep everything from your template) -->
    <?php /* You can paste your full header HTML here unchanged from your template */ ?>
  </header>

  <!-- ======== MAIN ======== -->
  <main>

    <!-- ===== Banner (keep your existing static hero) ===== -->
    <?php /* keep your banner HTML block unchanged */ ?>

    <!-- ===== Categories row (keep static icons) ===== -->
    <?php /* keep your "category" horizontal scroller unchanged if you like */ ?>

    <!-- ====== PRODUCT SECTION ====== -->
    <div class="product-container">
      <div class="container">

        <!-- ============ SIDEBAR (Categories from DB) ============ -->
        <div class="sidebar has-scrollbar" data-mobile-menu>
          <div class="sidebar-category">
            <div class="sidebar-top">
              <h2 class="sidebar-title">Category</h2>
              <button class="sidebar-close-btn" data-mobile-menu-close-btn><ion-icon name="close-outline"></ion-icon></button>
            </div>

            <ul class="sidebar-menu-category-list">
              <?php foreach ($categories as $cat): ?>
                <li class="sidebar-menu-category">
                  <button class="sidebar-accordion-menu" data-accordion-btn>
                    <div class="menu-title-flex">
                      <img src="./assets/images/icons/tee.svg" alt="category" width="20" height="20" class="menu-title-img">
                      <p class="menu-title"><?= h($cat['category']) ?></p>
                    </div>
                    <div>
                      <ion-icon name="add-outline" class="add-icon"></ion-icon>
                      <ion-icon name="remove-outline" class="remove-icon"></ion-icon>
                    </div>
                  </button>

                  <ul class="sidebar-submenu-category-list" data-accordion>
                    <li class="sidebar-submenu-category">
                      <a href="#" class="sidebar-submenu-title">
                        <p class="product-name">All</p>
                        <data class="stock" title="Available Stock"><?= (int)$cat['cnt'] ?></data>
                      </a>
                    </li>
                  </ul>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Best sellers block (optional: show 4 newest small cards) -->
          <div class="product-showcase">
            <h3 class="showcase-heading">best sellers</h3>
            <div class="showcase-wrapper">
              <div class="showcase-container">
                <?php foreach (array_slice($newProducts, 0, 4) as $p): ?>
                  <div class="showcase">
                    <a href="#" class="showcase-img-box">
                      <img src="<?= h(img_or_placeholder($p['product_image'], 75, 75)) ?>" alt="<?= h($p['product_name']) ?>" width="75" height="75" class="showcase-img">
                    </a>
                    <div class="showcase-content">
                      <a href="#"><h4 class="showcase-title"><?= h($p['product_name']) ?></h4></a>
                      <div class="price-box">
                        <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                          <del><?= h(money($p['price'])) ?></del>
                          <p class="price"><?= h(money($p['sale_price'])) ?></p>
                        <?php else: ?>
                          <p class="price"><?= h(money($p['price'])) ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- ============ RIGHT SIDE (PRODUCT LISTS) ============ -->
        <div class="product-box">

          <!-- --- PRODUCT MINIMAL: New Arrivals / Trending / Top Rated (small cards) --- -->
          <div class="product-minimal">
            <!-- New Arrivals -->
            <div class="product-showcase">
              <h2 class="title">New Arrivals</h2>
              <div class="showcase-wrapper has-scrollbar">
                <div class="showcase-container">
                  <?php foreach ($newArrivals as $p): ?>
                    <div class="showcase">
                      <a href="#" class="showcase-img-box">
                        <img src="<?= h(img_or_placeholder($p['product_image'], 70, 70)) ?>" alt="<?= h($p['product_name']) ?>" width="70" class="showcase-img">
                      </a>
                      <div class="showcase-content">
                        <a href="#"><h4 class="showcase-title"><?= h($p['product_name']) ?></h4></a>
                        <a href="#" class="showcase-category"><?= h($p['category']) ?></a>
                        <div class="price-box">
                          <p class="price">
                            <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                              <?= h(money($p['sale_price'])) ?>
                              <del><?= h(money($p['price'])) ?></del>
                            <?php else: ?>
                              <?= h(money($p['price'])) ?>
                            <?php endif; ?>
                          </p>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Trending -->
            <div class="product-showcase">
              <h2 class="title">Trending</h2>
              <div class="showcase-wrapper has-scrollbar">
                <div class="showcase-container">
                  <?php foreach ($trending as $p): ?>
                    <div class="showcase">
                      <a href="#" class="showcase-img-box">
                        <img src="<?= h(img_or_placeholder($p['product_image'], 70, 70)) ?>" alt="<?= h($p['product_name']) ?>" width="70" class="showcase-img">
                      </a>
                      <div class="showcase-content">
                        <a href="#"><h4 class="showcase-title"><?= h($p['product_name']) ?></h4></a>
                        <a href="#" class="showcase-category"><?= h($p['category']) ?></a>
                        <div class="price-box">
                          <p class="price">
                            <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                              <?= h(money($p['sale_price'])) ?>
                              <del><?= h(money($p['price'])) ?></del>
                            <?php else: ?>
                              <?= h(money($p['price'])) ?>
                            <?php endif; ?>
                          </p>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Top Rated -->
            <div class="product-showcase">
              <h2 class="title">Top Rated</h2>
              <div class="showcase-wrapper has-scrollbar">
                <div class="showcase-container">
                  <?php foreach ($topRated as $p): ?>
                    <div class="showcase">
                      <a href="#" class="showcase-img-box">
                        <img src="<?= h(img_or_placeholder($p['product_image'], 70, 70)) ?>" alt="<?= h($p['product_name']) ?>" width="70" class="showcase-img">
                      </a>
                      <div class="showcase-content">
                        <a href="#"><h4 class="showcase-title"><?= h($p['product_name']) ?></h4></a>
                        <a href="#" class="showcase-category"><?= h($p['category']) ?></a>
                        <div class="price-box">
                          <p class="price">
                            <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                              <?= h(money($p['sale_price'])) ?>
                              <del><?= h(money($p['price'])) ?></del>
                            <?php else: ?>
                              <?= h(money($p['price'])) ?>
                            <?php endif; ?>
                          </p>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- --- PRODUCT GRID: New Products (from DB) --- -->
          <div class="product-main">
            <h2 class="title">New Products</h2>

            <div class="product-grid">
              <?php foreach ($newProducts as $p): ?>
                <div class="showcase">
                  <div class="showcase-banner">
                    <?php
                      $imgDefault = img_or_placeholder($p['product_image'], 300, 300);
                      // reuse one image for hover; if you store multiple later, swap here
                      $imgHover   = $imgDefault;
                    ?>
                    <img src="<?= h($imgDefault) ?>" alt="<?= h($p['product_name']) ?>" width="300" class="product-img default">
                    <img src="<?= h($imgHover) ?>"   alt="<?= h($p['product_name']) ?>" width="300" class="product-img hover">

                    <?= sale_badge((float)$p['price'], $p['sale_price'] !== null ? (float)$p['sale_price'] : null) ?>

                    <div class="showcase-actions">
                      <button class="btn-action"><ion-icon name="heart-outline"></ion-icon></button>
                      <button class="btn-action"><ion-icon name="eye-outline"></ion-icon></button>
                      <button class="btn-action"><ion-icon name="repeat-outline"></ion-icon></button>
                      <button class="btn-action"><ion-icon name="bag-add-outline"></ion-icon></button>
                    </div>
                  </div>

                  <div class="showcase-content">
                    <a href="#" class="showcase-category"><?= h($p['category']) ?></a>
                    <a href="#"><h3 class="showcase-title"><?= h($p['product_name']) ?></h3></a>

                    <!-- simple static stars to match template -->
                    <div class="showcase-rating">
                      <ion-icon name="star"></ion-icon>
                      <ion-icon name="star"></ion-icon>
                      <ion-icon name="star"></ion-icon>
                      <ion-icon name="star-outline"></ion-icon>
                      <ion-icon name="star-outline"></ion-icon>
                    </div>

                    <div class="price-box">
                      <?php if (!empty($p['sale_price']) && $p['sale_price'] < $p['price']): ?>
                        <p class="price"><?= h(money($p['sale_price'])) ?></p>
                        <del><?= h(money($p['price'])) ?></del>
                      <?php else: ?>
                        <p class="price"><?= h(money($p['price'])) ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div><!-- /product-main -->

        </div><!-- /product-box -->

      </div><!-- /container -->
    </div><!-- /product-container -->

    <!-- Keep your Testimonials / CTA / Blog sections as-is -->
    <?php /* paste your remaining sections unchanged (testimonials, cta, blog, footer etc.) */ ?>

  </main>

  <!-- ====== FOOTER (keep your original) ====== -->
  <footer>
    <?php /* paste your footer HTML here from your template */ ?>
  </footer>

  <script src="./assets/js/script.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
