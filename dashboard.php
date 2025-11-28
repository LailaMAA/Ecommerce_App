<?php
require_once 'config/config.php';
require_once 'classes/Security.php';
require_once 'classes/DashboardStats.php';

// Vérification de l'authentification
requireLogin();

// Vérification biométrique si nécessaire
if (isset($_SESSION['biometric_required']) && !isset($_SESSION['biometric_verified'])) {
    redirect('biometric-auth.php');
}

$stats = new DashboardStats($pdo);

// Mise à jour des statistiques quotidiennes
$stats->updateDailyStats();

// Récupération des statistiques
$salesStats = $stats->getSalesStats('30days');
$dailySales = $stats->generateSalesChart(30);
$topProducts = $stats->getTopProducts(5);
$categoryStats = $stats->generateCategoryChart();
$paymentStats = $stats->generatePaymentMethodChart();
$customerStats = $stats->getCustomerStats();
$inventoryStats = $stats->getInventoryStats();
$orderStatusStats = $stats->getOrderStatusStats();

// Log de l'accès au tableau de bord
Security::logActivity('dashboard_access', 'Accès au tableau de bord', $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produits</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">Panier</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><a class="dropdown-item" href="orders.php">Mes commandes</a></li>
                            <li><a class="dropdown-item" href="biometric-auth.php">Sécurité</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-tachometer-alt"></i> Tableau de Bord
                    <small class="text-muted">- Statistiques de vente</small>
                </h1>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card success">
                    <div class="stat-number"><?php echo formatPrice($salesStats['total_revenue']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-euro-sign"></i> Chiffre d'affaires (30j)
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card info">
                    <div class="stat-number"><?php echo $salesStats['total_orders']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-shopping-cart"></i> Commandes (30j)
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo formatPrice($salesStats['avg_order_value']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-chart-line"></i> Panier moyen
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $salesStats['unique_customers']; ?></div>
                    <div class="stat-label">
                        <i class="fas fa-users"></i> Clients uniques (30j)
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-line"></i> Évolution des ventes (30 derniers jours)</h5>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie"></i> Ventes par catégorie</h5>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5><i class="fas fa-credit-card"></i> Méthodes de paiement</h5>
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5><i class="fas fa-boxes"></i> Statut des commandes</h5>
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tableaux de données -->
        <div class="row">
            <div class="col-lg-8">
                <div class="table-container">
                    <h5><i class="fas fa-trophy"></i> Top 5 des produits</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>SKU</th>
                                    <th>Quantité vendue</th>
                                    <th>Chiffre d'affaires</th>
                                    <th>Commandes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['sku']); ?></span></td>
                                    <td><?php echo $product['total_sold']; ?></td>
                                    <td><?php echo formatPrice($product['total_revenue']); ?></td>
                                    <td><?php echo $product['order_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="table-container">
                    <h5><i class="fas fa-info-circle"></i> Statistiques générales</h5>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Total clients:</span>
                                <strong><?php echo $customerStats['total_customers']; ?></strong>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Nouveaux clients (30j):</span>
                                <strong><?php echo $customerStats['new_customers_30d']; ?></strong>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Clients actifs (7j):</span>
                                <strong><?php echo $customerStats['active_customers_7d']; ?></strong>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Total produits:</span>
                                <strong><?php echo $inventoryStats['total_products']; ?></strong>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Stock total:</span>
                                <strong><?php echo $inventoryStats['total_stock']; ?></strong>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Rupture de stock:</span>
                                <strong class="text-danger"><?php echo $inventoryStats['out_of_stock']; ?></strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <span>Stock faible:</span>
                                <strong class="text-warning"><?php echo $inventoryStats['low_stock']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Graphique des ventes quotidiennes
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dailySales['dates']); ?>,
                datasets: [{
                    label: 'Chiffre d\'affaires (€)',
                    data: <?php echo json_encode($dailySales['revenues']); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Nombre de commandes',
                    data: <?php echo json_encode($dailySales['orders']); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Graphique des catégories
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($categoryStats['categories']); ?>,
                datasets: [{
                    data: <?php echo json_encode($categoryStats['revenues']); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Graphique des méthodes de paiement
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($paymentStats['methods']); ?>,
                datasets: [{
                    label: 'Nombre de commandes',
                    data: <?php echo json_encode($paymentStats['counts']); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0'
                    ]
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique du statut des commandes
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(orderStatusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($orderStatusStats, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($orderStatusStats, 'count')); ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
