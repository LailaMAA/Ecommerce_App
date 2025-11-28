<?php
// Script de démonstration des produits avec images
require_once 'config/config.php';

echo "<h1>Démonstration des Produits E-Commerce Multimédia Maroc</h1>";
echo "<p>Voici les produits disponibles avec leurs images :</p>";

// Récupération des produits
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, pi.file_path as image_url
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    ORDER BY p.featured DESC, p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll();

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";

foreach ($products as $product) {
    echo "<div style='border: 1px solid #ddd; border-radius: 10px; padding: 15px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
    
    // Image du produit
    if ($product['image_url']) {
        echo "<img src='{$product['image_url']}' alt='{$product['name']}' style='width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;'>";
    } else {
        echo "<div style='width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 15px; color: #666;'>Pas d'image</div>";
    }
    
    // Informations du produit
    echo "<h3 style='margin: 0 0 10px 0; color: #333;'>{$product['name']}</h3>";
    echo "<p style='color: #666; margin: 0 0 10px 0; font-size: 14px;'>{$product['category_name']}</p>";
    echo "<p style='color: #555; margin: 0 0 15px 0; font-size: 14px;'>" . substr($product['short_description'], 0, 100) . "...</p>";
    
    // Prix
    echo "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;'>";
    echo "<span style='font-size: 18px; font-weight: bold; color: #28a745;'>" . formatPrice($product['price']) . "</span>";
    if ($product['featured']) {
        echo "<span style='background: #ffc107; color: #000; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>En vedette</span>";
    }
    echo "</div>";
    
    // Stock
    if ($product['stock_quantity'] > 0) {
        echo "<p style='color: #28a745; margin: 0; font-size: 14px;'>✓ En stock ({$product['stock_quantity']})</p>";
    } else {
        echo "<p style='color: #dc3545; margin: 0; font-size: 14px;'>✗ Rupture de stock</p>";
    }
    
    echo "</div>";
}

echo "</div>";

echo "<h2>Statistiques</h2>";
echo "<ul>";
echo "<li>Total des produits : " . count($products) . "</li>";
echo "<li>Produits en vedette : " . count(array_filter($products, function($p) { return $p['featured']; })) . "</li>";
echo "<li>Produits en stock : " . count(array_filter($products, function($p) { return $p['stock_quantity'] > 0; })) . "</li>";
echo "<li>Catégories : " . count(array_unique(array_column($products, 'category_name'))) . "</li>";
echo "</ul>";

echo "<h2>Prix en Dirhams Marocains</h2>";
echo "<p>Tous les prix sont affichés en dirhams marocains (DH) comme demandé.</p>";

echo "<h2>Images des Produits</h2>";
echo "<p>Chaque produit dispose d'une image de démonstration créée automatiquement.</p>";
echo "<p>Les images sont stockées dans le dossier <code>assets/images/products/</code></p>";

echo "<hr>";
echo "<p><strong>Note :</strong> Ce script de démonstration montre tous les produits avec leurs images et prix en dirhams marocains.</p>";
echo "<p>Pour accéder au site complet, visitez <a href='index.php'>index.php</a></p>";
?>
