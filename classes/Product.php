<?php
require_once __DIR__ . '/../config/yarac_db.php';

class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $price;
    public $category;
    public $gender;
    public $image;
    public $description;
    public $stock;
    public $rating;
    public $total_reviews;
    public $sizes;
    public $featured;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all products with enhanced filtering
    public function getAll($limit = null, $offset = null, $sort = 'created_at DESC') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE stock > 0 ORDER BY " . $sort;
        
        if ($limit) {
            $query .= " LIMIT " . $limit;
            if ($offset) {
                $query .= " OFFSET " . $offset;
            }
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get products by category
    public function getByCategory($category, $limit = null, $sort = 'created_at DESC') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE category = ? AND stock > 0 ORDER BY " . $sort;
        
        if ($limit) {
            $query .= " LIMIT " . $limit;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category);
        $stmt->execute();
        return $stmt;
    }

    // Get products by gender
    public function getByGender($gender, $limit = null, $sort = 'created_at DESC') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE gender = ? AND stock > 0 ORDER BY " . $sort;
        
        if ($limit) {
            $query .= " LIMIT " . $limit;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $gender);
        $stmt->execute();
        return $stmt;
    }

    // Enhanced search with sorting
    public function search($search_term, $category = null, $gender = null, $sort = 'created_at DESC') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE stock > 0 AND (name LIKE ? OR description LIKE ?)";
        
        $params = ["%$search_term%", "%$search_term%"];
        
        if ($category && $category != 'all') {
            $query .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($gender && $gender != 'all') {
            $query .= " AND gender = ?";
            $params[] = $gender;
        }
        
        $query .= " ORDER BY " . $sort;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    // Get single product with reviews
 // GANTI SELURUH FUNGSI getById DI classes/Product.php DENGAN INI

public function getById($id) {
    $query = "SELECT p.*, 
              (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id) as avg_rating,
              (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) as review_count
              FROM " . $this->table_name . " p WHERE p.id = ? LIMIT 1";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->price = $row['price'];
        $this->category = $row['category'];
        $this->gender = $row['gender'];
        $this->image = $row['image'];
        $this->description = $row['description'];
        $this->stock = $row['stock'];
        $this->rating = $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
        $this->total_reviews = $row['review_count'];

        // [FIX] Mengganti operator '??' dengan 'isset()' agar kompatibel
        $sizes_json = isset($row['sizes']) ? $row['sizes'] : '["S", "M", "L", "XL"]';
        $this->sizes = json_decode($sizes_json, true);

        $this->featured = $row['featured'];
        $this->created_at = $row['created_at'];
        return true;
    }
    return false;
}
    // Get featured products
    public function getFeatured($limit = 4) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE stock > 0 AND featured = 1 ORDER BY rating DESC, created_at DESC LIMIT " . $limit;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Create product
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, price=:price, category=:category, gender=:gender, 
                      image=:image, description=:description, stock=:stock, featured=:featured";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":featured", $this->featured);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update product
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, price=:price, category=:category, gender=:gender, 
                      image=:image, description=:description, stock=:stock, featured=:featured
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":featured", $this->featured);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete product
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get product reviews
    public function getReviews($product_id) {
        $query = "SELECT pr.*, u.first_name, u.last_name 
                  FROM product_reviews pr 
                  JOIN users u ON pr.user_id = u.id 
                  WHERE pr.product_id = ? 
                  ORDER BY pr.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        return $stmt;
    }
}
?>
