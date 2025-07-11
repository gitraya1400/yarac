-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 08, 2025 at 09:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yarac`
--

-- --------------------------------------------------------

--
-- Table structure for table `advertisements`
--

CREATE TABLE `advertisements` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisements`
--

INSERT INTO `advertisements` (`id`, `title`, `image`, `link`, `active`, `sort_order`, `created_at`) VALUES
(1, 'Summer Collection 2024', 'iklan-baju-england.jpg', 'products.php?category=casual', 1, 1, '2025-07-07 16:52:22'),
(2, 'Formal Wear Sale', 'Adv2.png', 'products.php?category=formal', 1, 2, '2025-07-07 16:52:22'),
(3, 'New Arrivals', 'Adv3.png', 'products.php', 1, 3, '2025-07-07 16:52:22'),
(4, 'Winter Collection 2025', 'winter-collection.jpg', 'products.php?category=formal', 1, 4, '2025-07-08 03:00:00'),
(5, 'Sports Wear Special', 'sports-wear.jpg', 'products.php?category=casual', 1, 5, '2025-07-08 03:30:00'),
(6, 'Premium Suits', 'premium-suits.jpg', 'products.php?category=formal', 1, 6, '2025-07-08 04:00:00'),
(7, 'Casual Friday', 'casual-friday.jpg', 'products.php?category=casual', 1, 7, '2025-07-08 04:30:00'),
(8, 'Back to School', 'back-to-school.jpg', 'products.php?category=shirts', 1, 8, '2025-07-08 05:00:00'),
(9, 'Holiday Special', 'holiday-special.jpg', 'products.php', 1, 9, '2025-07-08 05:30:00'),
(10, 'End of Season Sale', 'end-season-sale.jpg', 'products.php', 1, 10, '2025-07-08 06:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `subscribed_at`) VALUES
(1, 'rcool945@gmail.com', '2025-07-08 04:56:57'),
(2, 'rayacool04@gmail.com', '2025-07-08 07:21:33'),
(3, 'subscriber1@gmail.com', '2025-07-08 01:00:00'),
(4, 'subscriber2@yahoo.com', '2025-07-08 01:30:00'),
(5, 'subscriber3@hotmail.com', '2025-07-08 02:00:00'),
(6, 'subscriber4@outlook.com', '2025-07-08 02:30:00'),
(7, 'subscriber5@gmail.com', '2025-07-08 03:00:00'),
(8, 'subscriber6@yahoo.com', '2025-07-08 03:30:00'),
(9, 'subscriber7@gmail.com', '2025-07-08 04:00:00'),
(10, 'subscriber8@hotmail.com', '2025-07-08 04:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `shipping_address`, `phone`, `notes`, `created_at`, `updated_at`) VALUES
(1, 9, 998000.00, 'pending', 'Jl. Air Kuning, BTN Kanawa Blok A1/15', '0813444982676', NULL, '2025-07-08 16:14:10', '2025-07-08 16:14:10'),
(2, 8, 729000.00, 'processing', 'Jl. Sudirman No. 123, Jakarta Pusat', '082199565423', 'Kirim pagi hari', '2025-07-08 07:00:00', '2025-07-08 07:30:00'),
(3, 5, 449000.00, 'shipped', 'Jl. Malioboro No. 45, Yogyakarta', '081387513506', NULL, '2025-07-08 06:00:00', '2025-07-08 08:00:00'),
(4, 2, 578000.00, 'delivered', 'Jl. Braga No. 78, Bandung', '081234567891', 'Terima kasih', '2025-07-08 03:00:00', '2025-07-08 09:00:00'),
(5, 3, 329000.00, 'pending', 'Jl. Tunjungan No. 90, Surabaya', '081234567892', 'Tolong dikemas rapi', '2025-07-08 08:30:00', '2025-07-08 08:30:00'),
(6, 4, 648000.00, 'processing', 'Jl. Imam Bonjol No. 56, Medan', '081234567893', NULL, '2025-07-08 05:00:00', '2025-07-08 06:00:00'),
(7, 9, 399000.00, 'shipped', 'Jl. Gajah Mada No. 34, Semarang', '0813444982676', 'Express delivery', '2025-07-08 04:00:00', '2025-07-08 07:00:00'),
(8, 7, 558000.00, 'delivered', 'Jl. Diponegoro No. 67, Solo', '0123456789', NULL, '2025-07-08 02:00:00', '2025-07-08 10:00:00'),
(9, 8, 229000.00, 'cancelled', 'Jl. Ahmad Yani No. 89, Bekasi', '082199565423', 'Batalkan pesanan', '2025-07-08 09:00:00', '2025-07-08 09:15:00'),
(10, 5, 768000.00, 'pending', 'Jl. Veteran No. 12, Malang', '081387513506', 'Kirim sore hari', '2025-07-08 10:00:00', '2025-07-08 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `size` varchar(10) DEFAULT 'M',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `size`, `created_at`) VALUES
(1, 1, 3, 1, 699000.00, 'M', '2025-07-08 16:14:10'),
(2, 1, 1, 1, 299000.00, 'M', '2025-07-08 16:14:10'),
(3, 2, 2, 1, 459000.00, 'L', '2025-07-08 07:00:00'),
(4, 2, 7, 2, 149000.00, 'M', '2025-07-08 07:00:00'),
(5, 3, 1, 1, 299000.00, 'L', '2025-07-08 06:00:00'),
(6, 3, 7, 1, 149000.00, 'S', '2025-07-08 06:00:00'),
(7, 4, 5, 1, 349000.00, 'XL', '2025-07-08 03:00:00'),
(8, 4, 10, 1, 229000.00, 'M', '2025-07-08 03:00:00'),
(9, 5, 9, 1, 329000.00, 'S', '2025-07-08 08:30:00'),
(10, 6, 3, 1, 699000.00, 'L', '2025-07-08 05:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` enum('shirts','casual','formal') NOT NULL,
  `gender` enum('men','women','unisex') NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT 'placeholder.jpg',
  `stock` int(11) NOT NULL DEFAULT 0,
  `rating` decimal(2,1) DEFAULT 0.0,
  `total_reviews` int(11) DEFAULT 0,
  `sizes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '["S", "M", "L", "XL"]' CHECK (json_valid(`sizes`)),
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `gender`, `image`, `stock`, `rating`, `total_reviews`, `sizes`, `featured`, `created_at`, `updated_at`) VALUES
(1, 'Classic White Shirt', 'Kemeja putih klasik yang cocok untuk berbagai acara formal maupun kasual. Dibuat dari bahan katun premium yang nyaman dan breathable.', 299000.00, 'shirts', 'men', 'baju england.jpg', 50, 4.5, 23, '[\"S\", \"M\", \"L\", \"XL\"]', 1, '2025-07-07 16:52:22', '2025-07-07 18:14:39'),
(2, 'Casual Denim Jacket', 'Jaket denim kasual yang trendy dan nyaman untuk gaya sehari-hari. Perfect untuk layering dan memberikan kesan stylish.', 459000.00, 'casual', 'women', 'casual-denim-jacket.jpg', 30, 4.2, 18, '[\"S\", \"M\", \"L\", \"XL\"]', 1, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(3, 'Formal Black Blazer', 'Blazer hitam formal untuk penampilan profesional yang elegan. Cut yang sempurna dengan detail finishing berkualitas tinggi.', 699000.00, 'formal', 'men', 'blazer.jpg', 25, 4.7, 31, '[\"S\", \"M\", \"L\", \"XL\"]', 1, '2025-07-07 16:52:22', '2025-07-07 21:08:09'),
(4, 'Trendy Crop Top', 'Crop top trendy untuk gaya kasual yang stylish dan modern. Cocok untuk berbagai aktivitas santai dan hangout.', 199000.00, 'casual', 'women', 'trendy-crop-top.jpg', 40, 4.1, 15, '[\"S\", \"M\", \"L\", \"XL\"]', 1, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(5, 'Business Shirt', 'Kemeja bisnis berkualitas tinggi dengan bahan premium. Ideal untuk meeting dan acara formal lainnya.', 349000.00, 'shirts', 'men', 'business-shirt.jpg', 35, 4.4, 27, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(6, 'Elegant Blouse', 'Blouse elegan untuk wanita dengan desain yang sophisticated. Perfect untuk office look yang chic.', 279000.00, 'formal', 'women', 'elegant-blouse.jpg', 28, 4.3, 22, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(7, 'Casual T-Shirt', 'Kaos kasual yang nyaman untuk aktivitas sehari-hari. Bahan cotton combed yang lembut dan tahan lama.', 149000.00, 'casual', 'unisex', 'casual-tshirt.jpg', 60, 4.0, 45, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(8, 'Formal Pants', 'Celana formal dengan potongan yang sempurna. Bahan berkualitas tinggi dengan fit yang comfortable.', 399000.00, 'formal', 'men', 'formal-pants.jpg', 20, 4.6, 19, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(9, 'Summer Dress', 'Dress musim panas yang ringan dan airy. Perfect untuk cuaca tropis dengan style yang feminine.', 329000.00, 'casual', 'women', 'summer-dress.jpg', 32, 4.2, 28, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(10, 'Polo Shirt', 'Polo shirt klasik yang versatile untuk berbagai occasion. Bahan pique cotton yang breathable.', 229000.00, 'shirts', 'unisex', 'polo-shirt.jpg', 45, 4.1, 33, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(11, 'Cardigan Sweater', 'Cardigan sweater yang cozy untuk cuaca dingin. Layer piece yang stylish dan functional.', 389000.00, 'casual', 'women', 'cardigan-sweater.jpg', 22, 4.4, 16, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(12, 'Chino Pants', 'Celana chino yang comfortable untuk daily wear. Style yang timeless dengan berbagai pilihan warna.', 319000.00, 'casual', 'men', 'chino-pants.jpg', 36, 4.3, 24, '[\"S\", \"M\", \"L\", \"XL\"]', 0, '2025-07-07 16:52:22', '2025-07-08 13:15:48');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `review`, `created_at`) VALUES
(1, 1, 2, 5, 'Kemeja yang sangat berkualitas, bahan nyaman dan cutting sempurna!', '2025-07-07 16:52:22'),
(2, 1, 3, 4, 'Good quality shirt, recommended untuk formal events.', '2025-07-07 16:52:22'),
(3, 2, 2, 4, 'Jaket denim yang stylish, cocok untuk gaya kasual sehari-hari.', '2025-07-07 16:52:22'),
(4, 3, 3, 5, 'Blazer yang sangat elegan, perfect untuk meeting penting.', '2025-07-07 16:52:22'),
(5, 4, 2, 4, 'Crop top yang trendy, bahan nyaman dan design menarik.', '2025-07-07 16:52:22'),
(6, 5, 4, 4, 'Business shirt yang berkualitas, cocok untuk meeting formal.', '2025-07-08 03:00:00'),
(7, 6, 8, 5, 'Blouse yang sangat elegan, bahan premium dan nyaman dipakai.', '2025-07-08 04:00:00'),
(8, 7, 5, 4, 'Kaos kasual yang nyaman, bahan cotton yang lembut.', '2025-07-08 05:00:00'),
(9, 8, 9, 5, 'Celana formal dengan cutting yang sempurna, sangat recommended!', '2025-07-08 06:00:00'),
(10, 9, 7, 4, 'Summer dress yang cantik dan nyaman, cocok untuk cuaca panas.', '2025-07-08 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,xx 
  `address` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `address`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'Yarac', 'admin@yarac.com', 'admin1', '+6281234567890', 'Jakarta, Indonesia', 'admin', '2025-07-07 16:52:22', '2025-07-08 10:30:41'),
(2, 'John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+6281234567891', 'Bandung, Indonesia', 'user', '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(3, 'Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+6281234567892', 'Surabaya, Indonesia', 'user', '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(4, 'Mike', 'Johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+6281234567893', 'Medan, Indonesia', 'user', '2025-07-07 16:52:22', '2025-07-07 16:52:22'),
(5, 'Rezky', 'Raya', 'zahahah@jamal.speed', 'raya1234', '081387513506', 'Jl. Air Kuning, BTN Kanawa Blok A1/15', 'user', '2025-07-08 10:17:29', '2025-07-08 10:17:29'),
(7, 'Mario', 'Hoki', 'adminbaru@yarac.com', '$2y$10$DNUj5e72Cs0I6f8xInew3uh2f2KuKvZwtnaXPSaC0ga5nrHwo7X7K', '0123456789', 'Jl. Air Kuning, BTN Kanawa Blok A1/15', 'admin', '2025-07-08 10:44:07', '2025-07-08 10:53:04'),
(8, 'Rezkya', 'Raya', 'rayacool04@gmail.com', '$2y$10$gumh.yse6BtOk63zZLcZM.YUBTr6gDZJThXW1xYZRLJfu5tCscgdO', '082199565423', 'Jl. Air Kuning, BTN Kanawa Blok A1/15', 'user', '2025-07-08 11:42:44', '2025-07-08 11:45:54'),
(9, 'Ignatius', 'Dwi Putra', 'hahaha@fore.com', '$2y$10$v8TZZ6VNMh3HPxx5zTstgOcs2CMiQX/9Fq2m2Y1IvALq9Hxp7qxfe', '0813444982676', 'Jl. Air Kuning, BTN Kanawa Blok A1/15', 'user', '2025-07-08 15:47:41', '2025-07-08 15:47:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advertisements`
--
ALTER TABLE `advertisements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
