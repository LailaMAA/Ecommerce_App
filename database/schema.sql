-- Script de création de la base de données E-Commerce Multimédia
-- Exécuter ce script dans phpMyAdmin ou MySQL Workbench

CREATE DATABASE IF NOT EXISTS ecommerce_multimedia 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE ecommerce_multimedia;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(10),
    country VARCHAR(100) DEFAULT 'Maroc',
    biometric_data TEXT COMMENT 'Données biométriques chiffrées',
    biometric_enabled BOOLEAN DEFAULT FALSE,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verification_token (verification_token),
    INDEX idx_reset_token (reset_token)
);

-- Table des catégories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_active (active)
);

-- Table des produits
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    category_id INT,
    sku VARCHAR(100) UNIQUE,
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    weight DECIMAL(8,2) DEFAULT 0,
    dimensions VARCHAR(100),
    featured BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_featured (featured),
    INDEX idx_active (active),
    INDEX idx_sku (sku)
);

-- Table des images de produits
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    original_filename VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    width INT,
    height INT,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    watermark_added BOOLEAN DEFAULT FALSE,
    watermark_type VARCHAR(50),
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_primary (is_primary),
    INDEX idx_sort_order (sort_order)
);

-- Table des attributs de produits
CREATE TABLE product_attributes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_attribute_name (attribute_name)
);

-- Table du panier
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user (user_id)
);

-- Table des sessions utilisateur
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    biometric_verified BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Table des adresses
CREATE TABLE addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('billing', 'shipping') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(100),
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Maroc',
    phone VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type)
);

-- Table des commandes
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50),
    payment_id VARCHAR(255),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    shipping_address TEXT,
    billing_address TEXT,
    notes TEXT,
    tracking_number VARCHAR(100),
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_order_number (order_number),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
);

-- Table des détails de commande
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
);

-- Table des coupons de réduction
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    maximum_discount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    used_count INT DEFAULT 0,
    user_limit INT DEFAULT 1,
    active BOOLEAN DEFAULT TRUE,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (active),
    INDEX idx_expires_at (expires_at)
);

-- Table d'utilisation des coupons
CREATE TABLE coupon_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX idx_coupon (coupon_id),
    INDEX idx_user (user_id),
    INDEX idx_order (order_id)
);

-- Table des statistiques de vente
CREATE TABLE sales_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    total_sales DECIMAL(10,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_products_sold INT DEFAULT 0,
    total_customers INT DEFAULT 0,
    average_order_value DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date),
    INDEX idx_date (date)
);

-- Table des logs d'activité
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Table des paramètres du site
CREATE TABLE site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);

-- Insertion des données de base
INSERT INTO categories (name, slug, description) VALUES
('Électronique', 'electronique', 'Appareils électroniques et gadgets technologiques'),
('Multimédia', 'multimedia', 'Produits multimédias, audio et vidéo'),
('Informatique', 'informatique', 'Ordinateurs, périphériques et accessoires informatiques'),
('Téléphonie', 'telephonie', 'Smartphones, tablettes et accessoires mobiles'),
('Gaming', 'gaming', 'Jeux vidéo, consoles et accessoires gaming');

INSERT INTO products (name, slug, description, short_description, price, category_id, sku, stock_quantity, featured) VALUES
('iPhone 15 Pro Max', 'iphone-15-pro-max', 'iPhone 15 Pro Max avec caméra 48MP, écran Super Retina XDR 6.7", puce A17 Pro, batterie longue durée', 'iPhone 15 Pro Max - Le smartphone premium d\'Apple', 12999.00, 4, 'IPH15PM001', 25, TRUE),
('Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Samsung Galaxy S24 Ultra avec S Pen, caméra 200MP, écran Dynamic AMOLED 2X 6.8", puce Snapdragon 8 Gen 3', 'Samsung Galaxy S24 Ultra avec S Pen', 11999.00, 4, 'SGS24U001', 30, TRUE),
('MacBook Pro M3', 'macbook-pro-m3', 'MacBook Pro 14" avec puce M3, 16GB RAM, SSD 512GB, écran Liquid Retina XDR', 'MacBook Pro M3 - Performance et créativité', 18999.00, 3, 'MBPM3001', 15, TRUE),
('AirPods Pro 2', 'airpods-pro-2', 'AirPods Pro 2ème génération avec réduction de bruit active, audio spatial, boîtier MagSafe', 'AirPods Pro 2 - Audio immersif sans fil', 2499.00, 2, 'APP2001', 50, TRUE),
('iPad Pro 12.9"', 'ipad-pro-12-9', 'iPad Pro 12.9" avec puce M2, écran Liquid Retina XDR, Apple Pencil compatible', 'iPad Pro 12.9" - Créativité sans limites', 8999.00, 2, 'IPADP001', 20, TRUE),
('PlayStation 5', 'playstation-5', 'Console PlayStation 5 avec lecteur de disques, SSD ultra-rapide, DualSense', 'PlayStation 5 - Nouvelle génération de gaming', 4999.00, 5, 'PS5001', 40, TRUE),
('Xbox Series X', 'xbox-series-x', 'Console Xbox Series X, 4K 120fps, SSD ultra-rapide, rétrocompatibilité', 'Xbox Series X - Gaming nouvelle génération', 4499.00, 5, 'XBSX001', 35, FALSE),
('Nintendo Switch OLED', 'nintendo-switch-oled', 'Nintendo Switch OLED avec écran OLED 7", 64GB de stockage, manettes Joy-Con', 'Nintendo Switch OLED - Gaming portable', 2999.00, 5, 'NSWOLED001', 60, FALSE),
('Sony WH-1000XM5', 'sony-wh-1000xm5', 'Casque Sony WH-1000XM5 avec réduction de bruit active, audio haute résolution, autonomie 30h', 'Sony WH-1000XM5 - Réduction de bruit premium', 3299.00, 2, 'SONYWH001', 45, FALSE),
('Apple Watch Series 9', 'apple-watch-series-9', 'Apple Watch Series 9 GPS, écran Always-On Retina, capteurs santé avancés', 'Apple Watch Series 9 - Santé et fitness', 3999.00, 1, 'AWS9001', 80, FALSE),
('Dell XPS 13', 'dell-xps-13', 'Dell XPS 13 avec processeur Intel i7, 16GB RAM, SSD 512GB, écran InfinityEdge', 'Dell XPS 13 - Ultrabook premium', 12999.00, 3, 'DELLXPS001', 25, FALSE),
('Surface Pro 9', 'surface-pro-9', 'Microsoft Surface Pro 9 avec processeur Intel i7, 16GB RAM, SSD 256GB, Surface Pen inclus', 'Surface Pro 9 - Tablette PC professionnelle', 10999.00, 3, 'SURFP9001', 30, FALSE);

-- Insertion des images de produits
INSERT INTO product_images (product_id, original_filename, file_path, file_type, file_size, width, height, is_primary, watermark_added) VALUES
(1, 'iphone-15-pro-max.jpg', 'iphone-15-pro-max.jpg', 'image/jpeg', 20651, 600, 600, TRUE, FALSE),
(2, 'samsung-s24-ultra.jpg', 'samsung-s24-ultra.jpg', 'image/jpeg', 20186, 600, 600, TRUE, FALSE),
(3, 'macbook-pro-m3.jpg', 'macbook-pro-m3.jpg', 'image/jpeg', 20279, 600, 600, TRUE, FALSE),
(4, 'airpods-pro-2.jpg', 'airpods-pro-2.jpg', 'image/jpeg', 20319, 600, 600, TRUE, FALSE),
(5, 'ipad-pro-12-9.jpg', 'ipad-pro-12-9.jpg', 'image/jpeg', 20727, 600, 600, TRUE, FALSE),
(6, 'playstation-5.jpg', 'playstation-5.jpg', 'image/jpeg', 21936, 600, 600, TRUE, FALSE),
(7, 'xbox-series-x.jpg', 'xbox-series-x.jpg', 'image/jpeg', 19851, 600, 600, TRUE, FALSE),
(8, 'nintendo-switch-oled.jpg', 'nintendo-switch-oled.jpg', 'image/jpeg', 21864, 600, 600, TRUE, FALSE),
(9, 'sony-wh-1000xm5.jpg', 'sony-wh-1000xm5.jpg', 'image/jpeg', 20786, 600, 600, TRUE, FALSE),
(10, 'apple-watch-series-9.jpg', 'apple-watch-series-9.jpg', 'image/jpeg', 18993, 600, 600, TRUE, FALSE),
(11, 'dell-xps-13.jpg', 'dell-xps-13.jpg', 'image/jpeg', 21759, 600, 600, TRUE, FALSE),
(12, 'surface-pro-9.jpg', 'surface-pro-9.jpg', 'image/jpeg', 20377, 600, 600, TRUE, FALSE);

INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'E-Commerce Multimédia Maroc', 'string', 'Nom du site'),
('site_description', 'Votre boutique en ligne sécurisée au Maroc avec des fonctionnalités avancées', 'string', 'Description du site'),
('currency', 'MAD', 'string', 'Devise principale'),
('tax_rate', '20', 'number', 'Taux de TVA (%)'),
('shipping_cost', '99', 'number', 'Coût de livraison standard (DH)'),
('free_shipping_threshold', '1000', 'number', 'Seuil pour livraison gratuite (DH)'),
('max_cart_items', '10', 'number', 'Nombre maximum d\'articles dans le panier'),
('biometric_enabled', 'true', 'boolean', 'Activation de l\'authentification biométrique'),
('captcha_enabled', 'true', 'boolean', 'Activation du captcha'),
('maintenance_mode', 'false', 'boolean', 'Mode maintenance');

-- Création des vues utiles
CREATE VIEW v_product_stats AS
SELECT 
    p.id,
    p.name,
    p.price,
    p.stock_quantity,
    COUNT(oi.id) as total_sold,
    SUM(oi.quantity) as units_sold,
    SUM(oi.total) as revenue
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('paid', 'shipped', 'delivered')
GROUP BY p.id, p.name, p.price, p.stock_quantity;

CREATE VIEW v_daily_sales AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as orders_count,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value
FROM orders 
WHERE status IN ('paid', 'shipped', 'delivered')
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;