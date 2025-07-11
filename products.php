<?php
$page_title = "Products - Yarac Fashion Store";
$additional_css = ['products.css'];
$additional_js = ['product.js'];

require_once 'config/yarac_db.php';
require_once 'classes/Product.php';

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize product object
$product = new Product($db);

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at DESC';

// Get products based on filters
if (!empty($search)) {
    $products_result = $product->search($search, $category, $gender, $sort);
} else if ($category != 'all') {
    $products_result = $product->getByCategory($category, null, $sort);
} else if ($gender != 'all') {
    $products_result = $product->getByGender($gender, null, $sort);
} else {
    $products_result = $product->getAll(null, null, $sort);
}

include 'includes/header.php';
?>

<!-- Products Header -->
<section class="products-header">
    <div class="container">
        <h1>Our Products</h1>
        <p>Discover our latest collection of premium fashion</p>
    </div>
</section>

<!-- Enhanced Search Section -->
<section class="search-section">
    <div class="container">
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="search-input" placeholder="Search for products, brands, categories..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if (!empty($search)): ?>
                    <a href="products.php" class="clear-search" title="Clear search">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div class="search-suggestions" id="search-suggestions"></div>
            <div class="search-stats" id="search-stats"></div>
        </div>
        
        <div class="popular-searches">
            <span class="popular-label">Popular:</span>
            <div class="popular-tags">
                <a href="products.php?search=shirt" class="popular-tag">Shirt</a>
                <a href="products.php?search=casual" class="popular-tag">Casual</a>
                <a href="products.php?search=formal" class="popular-tag">Formal</a>
                <a href="products.php?search=jacket" class="popular-tag">Jacket</a>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Filters Section -->
<section class="filters-section">
    <div class="container">
        <div class="filters-container">
            <div class="filter-group">
                <h3>Category</h3>
                <div class="filter-options">
                    <a href="products.php?gender=<?php echo $gender; ?>&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $category == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="products.php?category=shirts&gender=<?php echo $gender; ?>&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $category == 'shirts' ? 'active' : ''; ?>">Shirts</a>
                    <a href="products.php?category=casual&gender=<?php echo $gender; ?>&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $category == 'casual' ? 'active' : ''; ?>">Casual</a>
                    <a href="products.php?category=formal&gender=<?php echo $gender; ?>&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $category == 'formal' ? 'active' : ''; ?>">Formal</a>
                </div>
            </div>
            
            <div class="filter-group">
                <h3>Gender</h3>
                <div class="filter-options">
                    <a href="products.php?category=<?php echo $category; ?>&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $gender == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="products.php?category=<?php echo $category; ?>&gender=men&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $gender == 'men' ? 'active' : ''; ?>">Men</a>
                    <a href="products.php?category=<?php echo $category; ?>&gender=women&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $gender == 'women' ? 'active' : ''; ?>">Women</a>
                    <a href="products.php?category=<?php echo $category; ?>&gender=unisex&sort=<?php echo $sort; ?>" 
                       class="filter-option <?php echo $gender == 'unisex' ? 'active' : ''; ?>">Unisex</a>
                </div>
            </div>
            
            <div class="sort-group">
                <h3>Sort By</h3>
                <select id="sort-select" onchange="updateSort()">
                    <option value="created_at DESC" <?php echo $sort == 'created_at DESC' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="created_at ASC" <?php echo $sort == 'created_at ASC' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="price ASC" <?php echo $sort == 'price ASC' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price DESC" <?php echo $sort == 'price DESC' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="rating DESC" <?php echo $sort == 'rating DESC' ? 'selected' : ''; ?>>Highest Rated</option>
                    <option value="name ASC" <?php echo $sort == 'name ASC' ? 'selected' : ''; ?>>Name: A to Z</option>
                </select>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="products-section">
    <div class="container">
        <div class="products-grid" id="products-grid">
            <?php 
            $product_count = 0;
            while ($row = $products_result->fetch(PDO::FETCH_ASSOC)): 
                $product_count++;
            ?>
                <div class="product-card fade-in" data-id="<?php echo $row['id']; ?>" data-name="<?php echo strtolower($row['name']); ?>" data-category="<?php echo $row['category']; ?>">
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
                        </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            
            <?php if ($product_count == 0): ?>
                <div class="no-products">
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Enhanced Quick View Modal -->
<div class="modal" id="quick-view-modal">
    <div class="modal-content">
        <span class="close" onclick="closeQuickView()">&times;</span>
        <div class="modal-body">
            <div class="modal-image">
                <img id="modal-product-image" src="assets/images/ph.jpg" alt="">
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
