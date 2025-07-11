<?php
$page_title = "Yarac - Fashion Store Terpercaya";
require_once 'config/yarac_db.php';
require_once 'classes/Product.php';

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize product object
$product = new Product($db);

// Get featured products
$featured_products = $product->getFeatured(4);

// Get advertisements
$ads_query = "SELECT * FROM advertisements WHERE active = 1 ORDER BY sort_order ASC";
$ads_stmt = $db->prepare($ads_query);
$ads_stmt->execute();
$advertisements = $ads_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<section class="hero" id="home">
    <div class="hero-grid-container">
        <div class="hero-left-column">
            <div class="ad-slider"  >
                <?php if (!empty($advertisements)): ?>
                    <?php foreach ($advertisements as $index => $ad): ?>
                        <div class="ad-slide">
                            <img src="assets/images/ads/<?php echo htmlspecialchars($ad['image']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                            <div class="ad-card-label">
                                <span>Promotion</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-right-column">
            <div class="proud-section-group">
                <div class="proud-text">
                    WE'RE<br>PROUD<br>OF<br>OUR<br>CLOTHES
                </div>
                            <div class="hero-filters">
                        <a class="filter-btn active" href="#featured-products" data-filter="all">ALL <span>></span></a>
                        <a class="filter-btn" href="#featured-products" data-filter="men">MEN <span>></span></a>
                        <a class="filter-btn" href="#featured-products" data-filter="women">WOMEN <span>></span></a>
                        <a class="filter-btn" href="#featured-products" data-filter="unisex">UNISEX <span>></span></a>
                    </div>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-globe"></i>
                        <span>ECO-FRIENDLY PACKAGING</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-arrows-alt-h"></i>
                        <span>INCLUSIVE SIZES</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-palette"></i>
                        <span>DESIGNED BY LOCALS</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="featured-products">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        <div class="products-grid" id="featured-products">
            <?php while ($row = $featured_products->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="product-card fade-in" data-category="<?php echo $row['category']; ?>" data-gender="<?php echo $row['gender']; ?>" data-id="<?php echo $row['id']; ?>">
                    <img src="assets/images/products/<?php echo $row['image']; ?>" 
                         alt="<?php echo htmlspecialchars($row['name']); ?>" 
                         class="product-image"
                         onclick="quickView(<?php echo $row['id']; ?>)">
                    <div class="product-info">
                        <div class="product-category"><?php echo strtoupper($row['category']); ?></div>
                        <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                        <div class="product-rating">
                            <div class="stars">
                                <?php 
                                $rating = $row['rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <span class="star <?php echo $i <= $rating ? '' : 'empty'; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">(<?php echo $row['total_reviews'] ?? 0; ?> reviews)</span>
                        </div>
                        <div class="product-price">Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></div>
                        <div class="product-actions">
                            <button class="btn-add-cart" onclick="addToCart(<?php echo $row['id']; ?>)">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn-quick-view" onclick="quickView(<?php echo $row['id']; ?>)">
                                <i class="fas fa-eye"></i> Quick View
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="view-all">
            <a href="products.php" class="btn-primary">
                <i class="fas fa-arrow-right"></i> View All Products
            </a>
        </div>
    </div>
</section>

<section class="about-section-modern" id="about">
    <div class="container">
        <div class="about-modern-grid">
            <div class="about-modern-left">
                <h2 class="about-modern-title">Our Story</h2>
                <p class="about-modern-text">
                    Yarac didirikan dengan visi untuk menghadirkan fashion berkualitas tinggi yang terjangkau untuk semua kalangan. Sejak tahun 2020, kami telah melayani ribuan pelanggan di seluruh Indonesia dengan komitmen pada kualitas, style, dan kepuasan pelanggan.
                </p>
                <p class="about-modern-text">
                    Kami percaya bahwa fashion adalah bentuk ekspresi diri yang powerful. Setiap piece dalam koleksi kami dipilih dengan cermat untuk memastikan Anda tampil percaya diri dalam setiap kesempatan.
                </p>
            </div>
            <div class="about-modern-right">
                <img src="assets/images/Yarac LOgo.png" alt="Yarac Team" class="about-modern-image">
            </div>
        </div>
        <div class="why-choose-us">
            <h3>Why Choose Yarac?</h3>
            <div class="reasons-grid">
                <div class="reason-item">
                    <span class="reason-number">01</span>
                    <h4 class="reason-title">Premium Quality</h4>
                    <p class="reason-desc">Kami hanya menggunakan bahan-bahan terbaik untuk kenyamanan dan daya tahan maksimal.</p>
                </div>
                <div class="reason-item">
                    <span class="reason-number">02</span>
                    <h4 class="reason-title">Latest Trends</h4>
                    <p class="reason-desc">Tim kami bekerja keras untuk menghadirkan tren fashion terbaru langsung untuk Anda.</p>
                </div>
                <div class="reason-item">
                    <span class="reason-number">03</span>
                    <h4 class="reason-title">Customer First</h4>
                    <p class="reason-desc">Kepuasan Anda adalah prioritas utama kami dalam setiap langkah.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="categories-grid">
            <div class="category-card fade-in" data-category="shirts">
                <i class="fas fa-tshirt" style="font-size: 3rem; color: var(--olive-drab); margin-bottom: 20px;"></i>
                <h3>Shirts</h3>
                <p>Koleksi kemeja terbaru untuk berbagai acara formal dan kasual</p>
            </div>
            <div class="category-card fade-in" data-category="casual">
                <i class="fas fa-user-tie" style="font-size: 3rem; color: var(--olive-drab); margin-bottom: 20px;"></i>
                <h3>Casual Wear</h3>
                <p>Pakaian santai kekinian untuk gaya sehari-hari yang stylish</p>
            </div>
            <div class="category-card fade-in" data-category="formal">
                <i class="fas fa-briefcase" style="font-size: 3rem; color: var(--olive-drab); margin-bottom: 20px;"></i>
                <h3>Formal Wear</h3>
                <p>Pakaian formal elegan untuk penampilan profesional</p>
            </div>
        </div>
    </div>
</section>

<div class="modal" id="quick-view-modal">
    <div class="modal-content">
        <span class="close" onclick="closeQuickView()">&times;</span>
        <div class="modal-body">
            <div class="modal-image">
                <img id="modal-product-image" src="" alt="">
            </div>
            <div class="modal-info">
                <div class="product-category" id="modal-product-category"></div>
                <h3 id="modal-product-name"></h3>
                <div class="product-rating" id="modal-product-rating">
                    <div class="stars" id="modal-stars"></div>
                    <span class="rating-text" id="modal-rating-text"></span>
                </div>
                <div class="product-price" id="modal-product-price"></div>
                <div class="product-description" id="modal-product-description"></div>

                <div class="size-selector">
                    <label for="modal-size">Size:</label>
                    <select id="modal-size">
                        <option value="S">S</option>
                        <option value="M" selected>M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                    </select>
                </div>

                <div class="quantity-selector">
                    <label for="modal-quantity">Quantity:</label>
                    <input type="number" id="modal-quantity" value="1" min="1" max="10">
                </div>

                <button class="btn-add-cart-modal" onclick="addToCartFromModal()">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>