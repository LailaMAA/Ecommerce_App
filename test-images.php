<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test des Images - E-Commerce Multimédia Maroc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-image {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin: 10px;
            text-align: center;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-image img {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .test-image h5 {
            margin-top: 15px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">Test des Images de Produits</h1>
        <p class="text-center text-muted mb-5">Vérification de l'affichage des images de produits</p>
        
        <div class="row">
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/iphone-15-pro-max.jpg" alt="iPhone 15 Pro Max">
                    <h5>iPhone 15 Pro Max</h5>
                    <p class="text-success fw-bold">12,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/samsung-s24-ultra.jpg" alt="Samsung S24 Ultra">
                    <h5>Samsung S24 Ultra</h5>
                    <p class="text-success fw-bold">11,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/macbook-pro-m3.jpg" alt="MacBook Pro M3">
                    <h5>MacBook Pro M3</h5>
                    <p class="text-success fw-bold">18,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/airpods-pro-2.jpg" alt="AirPods Pro 2">
                    <h5>AirPods Pro 2</h5>
                    <p class="text-success fw-bold">2,499 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/ipad-pro-12-9.jpg" alt="iPad Pro 12.9">
                    <h5>iPad Pro 12.9"</h5>
                    <p class="text-success fw-bold">8,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/playstation-5.jpg" alt="PlayStation 5">
                    <h5>PlayStation 5</h5>
                    <p class="text-success fw-bold">4,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/xbox-series-x.jpg" alt="Xbox Series X">
                    <h5>Xbox Series X</h5>
                    <p class="text-success fw-bold">4,499 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/nintendo-switch-oled.jpg" alt="Nintendo Switch OLED">
                    <h5>Nintendo Switch OLED</h5>
                    <p class="text-success fw-bold">2,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/sony-wh-1000xm5.jpg" alt="Sony WH-1000XM5">
                    <h5>Sony WH-1000XM5</h5>
                    <p class="text-success fw-bold">3,299 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/apple-watch-series-9.jpg" alt="Apple Watch Series 9">
                    <h5>Apple Watch Series 9</h5>
                    <p class="text-success fw-bold">3,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/dell-xps-13.jpg" alt="Dell XPS 13">
                    <h5>Dell XPS 13</h5>
                    <p class="text-success fw-bold">12,999 DH</p>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3">
                <div class="test-image">
                    <img src="assets/images/products/surface-pro-9.jpg" alt="Surface Pro 9">
                    <h5>Surface Pro 9</h5>
                    <p class="text-success fw-bold">10,999 DH</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-home"></i> Retour à l'accueil
            </a>
            <a href="products.php" class="btn btn-outline-primary btn-lg ms-3">
                <i class="fas fa-shopping-bag"></i> Voir tous les produits
            </a>
        </div>
        
        <div class="alert alert-info mt-4">
            <h5><i class="fas fa-info-circle"></i> Informations</h5>
            <ul class="mb-0">
                <li><strong>Images créées :</strong> 12 images de produits avec design moderne</li>
                <li><strong>Taille :</strong> 600x600 pixels, optimisées pour le web</li>
                <li><strong>Format :</strong> JPEG haute qualité (95%)</li>
                <li><strong>Prix :</strong> Tous les prix sont en dirhams marocains (DH)</li>
                <li><strong>Design :</strong> Chaque produit a sa propre couleur et icône</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></script>
</body>
</html>

