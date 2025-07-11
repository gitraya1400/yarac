// Enhanced Yarac Fashion Store JavaScript with Forest Green Theme

// Global variables
let cart = JSON.parse(localStorage.getItem("yarac_cart")) || []
let currentProduct = null

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  initializeApp()
  updateCartUI()
  initializeNavbar()
  initializeAnimations()
})

// Initialize main app functionality
function initializeApp() {
  // Update cart count
  updateCartCount()

  // Initialize smooth scrolling
  initializeSmoothScrolling()

  // Initialize intersection observer for animations
  initializeScrollAnimations()

  // Initialize cart from localStorage
  loadCartFromStorage()

  // Initialize profile functionality
  initializeProfile()
    initializeHomepageFilters(); // <--- TAMBAHKAN BARIS INI

}

// Enhanced Navbar functionality
function initializeNavbar() {
  const navbar = document.getElementById("navbar")
  let lastScrollTop = 0

    window.addEventListener("scroll", () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Add scrolled class untuk styling yang berbeda
        if (scrollTop > 50) {
            navbar.classList.add("scrolled");
            console.log("Navbar: KECIL"); // Debug
        } else {
            navbar.classList.remove("scrolled");
            console.log("Navbar: BESAR"); // Debug
        }

    // Hide/show navbar on scroll
    navbar.style.transform = "translateX(-50%) translateY(0)";

    lastScrollTop = scrollTop
  })
}

// Enhanced Cart functionality
function toggleCart() {
  const cartDropdown = document.getElementById("cart-dropdown")
  const isVisible = cartDropdown.classList.contains("show")

  if (isVisible) {
    cartDropdown.classList.remove("show")
  } else {
    cartDropdown.classList.add("show")
    updateCartDisplay()
  }
}

// GANTI FUNGSI addToCart LAMA ANDA DENGAN INI

function addToCart(productId, size = "M", quantity = 1) {
  // [FIX] Cek apakah pengguna sudah login atau belum
  const authBtn = document.getElementById("auth-btn");
  if (authBtn && authBtn.textContent.trim().toUpperCase() === "SIGN IN") {
    // Jika tombol "SIGN IN" ada, berarti pengguna belum login
    alert("Silakan login terlebih dahulu untuk menambahkan barang ke keranjang.");
    window.location.href = 'login.php'; // Arahkan ke halaman login
    return; // Hentikan fungsi di sini
  }

  // Show loading state
  const button = event.target;
  const originalText = button.innerHTML;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
  button.disabled = true;

  // Fetch product details
  fetch(`api/get_product.php?id=${productId}`)
    .then((response) => response.json())
    .then((product) => {
      if (product.error) {
        throw new Error(product.error);
      }

      // Check if product already exists in cart
      const existingItem = cart.find((item) => item.id === productId && item.size === size);

      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        cart.push({
          id: productId,
          name: product.name,
          price: product.price,
          image: product.image,
          size: size,
          quantity: quantity,
          category: product.category,
        });
      }

      // Save to localStorage
      localStorage.setItem("yarac_cart", JSON.stringify(cart));

      // Update UI
      updateCartUI();
      showNotification(`${product.name} added to cart!`, "success");

      // Animate cart icon
      animateCartIcon();
    })
    .catch((error) => {
      console.error("Error adding to cart:", error);
      showNotification("Failed to add product to cart", "error");
    })
    .finally(() => {
      // Restore button state
      setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
      }, 1000);
    });
}

// GANTI FUNGSI addToCartFromModal LAMA DENGAN INI
function addToCartFromModal() {
  // [FIX] Cek dulu apakah currentProduct ada isinya
  if (!currentProduct) {
    alert("Gagal menambahkan ke keranjang. Silakan coba lagi.");
    return;
  }

  const productId = currentProduct.id;
  const size = document.getElementById("modal-size").value;
  const quantity = parseInt(document.getElementById("modal-quantity").value);

  addToCart(productId, size, quantity);
  closeQuickView(); // Tutup modal setelah berhasil
}

function removeFromCart(productId, size) {
// [FIX] Kode Baru dengan Perbandingan yang Benar
cart = cart.filter((item) => !(Number(item.id) === Number(productId) && item.size === size))  
localStorage.setItem("yarac_cart", JSON.stringify(cart))
  updateCartUI()
  showNotification("Item removed from cart", "info")
}

function updateQuantity(productId, size, newQuantity) {
  const item = cart.find((item) => item.id === productId && item.size === size)
  if (item) {
    if (newQuantity <= 0) {
      removeFromCart(productId, size)
    } else {
      item.quantity = newQuantity
      localStorage.setItem("yarac_cart", JSON.stringify(cart))
      updateCartUI()
    }
  }
}

function updateCartUI() {
  updateCartCount()
  updateCartDisplay()
}

function updateCartCount() {
  const cartCount = document.getElementById("cart-count")
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0)

  cartCount.textContent = totalItems

  if (totalItems > 0) {
    cartCount.classList.add("show")
  } else {
    cartCount.classList.remove("show")
  }
}

function updateCartDisplay() {
  const cartItems = document.getElementById("cart-items")
  const cartTotal = document.getElementById("cart-total")

  if (cart.length === 0) {
    cartItems.innerHTML = '<p class="empty-cart">Your cart is empty</p>'
    cartTotal.textContent = "Rp 0"
    return
  }

  let total = 0
  cartItems.innerHTML = cart
    .map((item) => {
      const itemTotal = item.price * item.quantity
      total += itemTotal

      return `
            <div class="cart-item">
                <img src="assets/images/products/${item.image}" alt="${item.name}">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">Rp ${formatPrice(item.price)}</div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, '${item.size}', ${item.quantity - 1})">-</button>
                        <span>${item.quantity}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, '${item.size}', ${item.quantity + 1})">+</button>
                        <span style="margin-left: 10px; font-size: 12px; color: var(--moss-green);">Size: ${item.size}</span>
                    </div>
                </div>

<button class="remove-item" onclick="removeFromCart(${item.id}, '${item.size}')">Remove</button>
            </div>
        `
    })
    .join("")

  cartTotal.textContent = `Rp ${formatPrice(total)}`
}

function animateCartIcon() {
  const cartIcon = document.querySelector(".cart-icon")
  cartIcon.style.transform = "scale(1.2)"
  setTimeout(() => {
    cartIcon.style.transform = "scale(1)"
  }, 200)
}

// Enhanced Quick View functionality
// GANTI FUNGSI quickView LAMA DENGAN INI
function quickView(productId) {
  const modal = document.getElementById("quick-view-modal");
  modal.style.display = "block";
  const modalContent = modal.querySelector(".modal-content");
  modalContent.classList.add("loading");

  fetch(`api/get_product.php?id=${productId}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((product) => {
      if (product.error) {
        throw new Error(product.error);
      }

      // Data produk berhasil didapat, simpan di variabel global
      currentProduct = product;

      // Mengisi modal dengan data, termasuk review
      document.getElementById("modal-product-image").src = `assets/images/products/${product.image}`;
      document.getElementById("modal-product-category").textContent = product.category.toUpperCase();
      document.getElementById("modal-product-name").textContent = product.name;
      document.getElementById("modal-product-price").textContent = `Rp ${new Intl.NumberFormat('id-ID').format(product.price)}`;
      document.getElementById("modal-product-description").textContent = product.description || "No description available.";

      const starsContainer = document.getElementById("modal-stars");
      const rating = product.rating || 0;
      starsContainer.innerHTML = "";
      for (let i = 1; i <= 5; i++) {
        const star = document.createElement("span");
        star.className = `star ${i <= rating ? "" : "empty"}`;
        star.textContent = "★";
        starsContainer.appendChild(star);
      }
      document.getElementById("modal-rating-text").textContent = `(${product.total_reviews || 0} reviews)`;

      if (product.sizes && Array.isArray(product.sizes)) {
        const sizeSelect = document.getElementById("modal-size");
        sizeSelect.innerHTML = product.sizes.map((size) => `<option value="${size}">${size}</option>`).join("");
      }
    })
    .catch((error) => {
      console.error("Error fetching product:", error);
      showNotification("Failed to load product details", "error");
      // Jangan tutup modal, biarkan pengguna melihat error
    })
    .finally(() => {
      modalContent.classList.remove("loading");
    });
}

function closeQuickView() {
  const modal = document.getElementById("quick-view-modal")
  modal.style.display = "none"
  currentProduct = null
}

// GANTI FUNGSI checkout LAMA ANDA DENGAN YANG INI
// assets/js/script.js

// GANTI FUNGSI checkout LAMA DENGAN INI
async function checkout() {
  if (cart.length === 0) {
    showNotification("Keranjang Anda kosong.", "error");
    return;
  }

  const authBtn = document.getElementById("auth-btn");
  if (authBtn && authBtn.textContent.trim().toUpperCase() === "SIGN IN") {
    showNotification("Silakan login untuk melanjutkan checkout.", "warning");
    setTimeout(() => window.location.href = 'login.php', 1500);
    return;
  }

  const checkoutButton = document.querySelector(".btn-checkout");
  const originalButtonHTML = checkoutButton.innerHTML;
  checkoutButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
  checkoutButton.disabled = true;

  try {
    // ---- [PERBAIKI BAGIAN INI] ----
    // Ganti 'Yarac-store-Raya' dengan nama folder proyek Anda yang sebenarnya.
    // Pastikan diawali dengan tanda '/'
 // ---- [FIXED] ----
    // Path is now relative to the current page.
    const response = await fetch('api/create_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cart: cart }),
    });
    if (!response.ok) {
        // Jika statusnya 404 atau error server lainnya, tampilkan pesan.
        throw new Error(`Gagal menghubungi server (Error: ${response.status})`);
    }

    const result = await response.json();
    
    if (result.success) {
      cart = [];
      localStorage.removeItem("yarac_cart");
      updateCartUI();
      
      showNotification("Pesanan berhasil! Anda akan diarahkan ke WhatsApp.", "success");
      const whatsappURL = `https://wa.me/${result.whatsappNumber}?text=${encodeURIComponent(result.whatsappMessage)}`;
      window.open(whatsappURL, "_blank");
    } else {
      throw new Error(result.message || "Gagal membuat pesanan.");
    }

  } catch (error) {
    console.error("Checkout error:", error);
    // Tampilkan pesan error yang lebih spesifik kepada pengguna
    showNotification(error.message, "error");

  } finally {
    checkoutButton.innerHTML = originalButtonHTML;
    checkoutButton.disabled = false;
  }
}

// Profile functionality
function initializeProfile() {
  // Check if user is logged in
  const authBtn = document.getElementById("auth-btn")
  if (authBtn && authBtn.textContent.trim() !== "SIGN IN") {
    // User is logged in, initialize profile features
    setupProfileEventListeners()
  }
}

function setupProfileEventListeners() {
  // Profile popup event listeners are already set in the HTML
}

function showProfile() {
  const profilePopup = document.getElementById("profile-popup")
  if (profilePopup) {
    profilePopup.style.display = "block"
  }
}

function closeProfile() {
  const profilePopup = document.getElementById("profile-popup")
  if (profilePopup) {
    profilePopup.style.display = "none"
  }
}

function logout() {
  if (confirm("Apakah Anda yakin ingin keluar?")) {
    fetch("api/logout.php", {
      method: "POST",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // [PENTING] Hapus data keranjang dari localStorage saat logout
          localStorage.removeItem('yarac_cart');
          showNotification("Berhasil keluar", "success");
          setTimeout(() => {
            window.location.href = "login.php"; // Arahkan ke halaman login
          }, 1000);
        }
      })
      .catch((error) => {
        console.error("Logout error:", error);
        // Tetap paksa keluar dan bersihkan cart jika ada error
        localStorage.removeItem('yarac_cart');
        window.location.href = "login.php";
      });
  }
}

// Enhanced Notification system
function showNotification(message, type = "info") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification")
  existingNotifications.forEach((notification) => notification.remove())

  // Create notification element
  const notification = document.createElement("div")
  notification.className = `notification notification-${type}`
  notification.innerHTML = `
        <i class="fas ${getNotificationIcon(type)}"></i>
        <span>${message}</span>
    `

  // Add to page
  document.body.appendChild(notification)

  // Auto remove after 4 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.style.opacity = "0"
      notification.style.transform = "translateX(100%)"
      setTimeout(() => {
        notification.remove()
      }, 300)
    }
  }, 4000)
}

function getNotificationIcon(type) {
  switch (type) {
    case "success":
      return "fa-check-circle"
    case "error":
      return "fa-exclamation-circle"
    case "warning":
      return "fa-exclamation-triangle"
    default:
      return "fa-info-circle"
  }
}

// Utility functions
function formatPrice(price) {
  return new Intl.NumberFormat("id-ID").format(price)
}

function loadCartFromStorage() {
  const savedCart = localStorage.getItem("yarac_cart")
  if (savedCart) {
    cart = JSON.parse(savedCart)
  }
}

// Enhanced smooth scrolling
function initializeSmoothScrolling() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute("href"))
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        })
      }
    })
  })
}

// Enhanced scroll animations
function initializeScrollAnimations() {
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("fade-in")
        observer.unobserve(entry.target)
      }
    })
  }, observerOptions)

  // Observe elements that should animate
  document.querySelectorAll(".product-card, .category-card, .value-card").forEach((el) => {
    observer.observe(el)
  })
}

function initializeAnimations() {
  // Add stagger animation to product cards
  const productCards = document.querySelectorAll(".product-card")
  productCards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`
  })
}

// Close modals when clicking outside
window.addEventListener("click", (event) => {
  const quickViewModal = document.getElementById("quick-view-modal")
  const profilePopup = document.getElementById("profile-popup")
  const cartDropdown = document.getElementById("cart-dropdown")

  // Close quick view modal
  if (event.target === quickViewModal) {
    closeQuickView()
  }

  // Close profile popup
  if (event.target === profilePopup) {
    closeProfile()
  }

  // Close cart dropdown
  if (!event.target.closest(".cart-icon") && !event.target.closest(".cart-dropdown")) {
    cartDropdown.classList.remove("show")
  }
})

// Keyboard navigation
document.addEventListener("keydown", (event) => {
  // Close modals with Escape key
  if (event.key === "Escape") {
    closeQuickView()
    closeProfile()
    document.getElementById("cart-dropdown").classList.remove("show")
  }
})

// Enhanced error handling
window.addEventListener("error", (event) => {
  console.error("JavaScript error:", event.error)
  // Don't show error notifications to users in production
  // showNotification('An error occurred. Please refresh the page.', 'error');
})

// Performance optimization
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Lazy loading for images
function initializeLazyLoading() {
  const images = document.querySelectorAll("img[data-src]")
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target
        img.src = img.dataset.src
        img.classList.remove("lazy")
        imageObserver.unobserve(img)
      }
    })
  })

  images.forEach((img) => imageObserver.observe(img))
}

// Initialize lazy loading if needed
document.addEventListener("DOMContentLoaded", initializeLazyLoading)

// Hilangkan kelas 'loading' dari body setelah halaman selesai dimuat
window.addEventListener('load', () => {
 document.body.classList.remove('loading');
});

// Export functions for global access
window.yaracStore = {
  addToCart,
  removeFromCart,
  updateQuantity,
  quickView,
  closeQuickView,
  checkout,
  showProfile,
  closeProfile,
  logout,
  showNotification,
  toggleCart,
}
function initializeHomepageFilters() {
    const filterButtons = document.querySelectorAll('.hero-filters .filter-btn');
    const productCards = document.querySelectorAll('#featured-products .product-card');

    if (!filterButtons.length) return;

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Hapus kelas aktif dari semua tombol
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Tambahkan kelas aktif ke tombol yang diklik
            button.classList.add('active');

            const filterValue = button.getAttribute('data-filter');

            productCards.forEach(card => {
                const cardGender = card.getAttribute('data-gender');
                
                // Tampilkan kartu jika cocok dengan filter atau jika filter adalah 'all'
                if (filterValue === 'all' || cardGender === filterValue) {
                    card.classList.remove('product-hidden');
                } else {
                    card.classList.add('product-hidden');
                }
            });
        });
    });
}