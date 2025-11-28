<?php
require_once 'config/config.php';
require_once 'classes/Security.php';

class DashboardStats {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getSalesStats($period = '30days') {
        $dateCondition = $this->getDateCondition($period);
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(DISTINCT user_id) as unique_customers
            FROM orders 
            WHERE status IN ('paid', 'shipped', 'delivered') 
            AND created_at >= ?
        ");
        
        $stmt->execute([$dateCondition]);
        return $stmt->fetch();
    }
    
    public function getDailySales($days = 30) {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE(created_at) as sale_date,
                COUNT(*) as orders_count,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM orders 
            WHERE status IN ('paid', 'shipped', 'delivered')
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY sale_date ASC
        ");
        
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    public function getTopProducts($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.name,
                p.sku,
                SUM(oi.quantity) as total_sold,
                SUM(oi.total) as total_revenue,
                COUNT(DISTINCT oi.order_id) as order_count
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status IN ('paid', 'shipped', 'delivered')
            GROUP BY p.id, p.name, p.sku
            ORDER BY total_sold DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getCategoryStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.name as category_name,
                COUNT(DISTINCT oi.order_id) as order_count,
                SUM(oi.quantity) as total_sold,
                SUM(oi.total) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status IN ('paid', 'shipped', 'delivered')
            GROUP BY c.id, c.name
            ORDER BY total_revenue DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getPaymentMethodStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                payment_method,
                COUNT(*) as order_count,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM orders 
            WHERE status IN ('paid', 'shipped', 'delivered')
            GROUP BY payment_method
            ORDER BY total_revenue DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getCustomerStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers_30d,
                COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_customers_7d
            FROM users
        ");
        
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getInventoryStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_stock,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN stock_quantity <= min_stock_level THEN 1 END) as low_stock
            FROM products
            WHERE active = 1
        ");
        
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getOrderStatusStats() {
        $stmt = $this->pdo->prepare("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM orders
            GROUP BY status
            ORDER BY count DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    private function getDateCondition($period) {
        switch ($period) {
            case '7days':
                return date('Y-m-d H:i:s', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d H:i:s', strtotime('-30 days'));
            case '90days':
                return date('Y-m-d H:i:s', strtotime('-90 days'));
            case '1year':
                return date('Y-m-d H:i:s', strtotime('-1 year'));
            default:
                return date('Y-m-d H:i:s', strtotime('-30 days'));
        }
    }
    
    public function generateSalesChart($days = 30) {
        $data = $this->getDailySales($days);
        
        // Préparation des données pour JpGraph
        $dates = [];
        $revenues = [];
        $orders = [];
        
        foreach ($data as $row) {
            $dates[] = date('d/m', strtotime($row['sale_date']));
            $revenues[] = (float)$row['total_revenue'];
            $orders[] = (int)$row['orders_count'];
        }
        
        return [
            'dates' => $dates,
            'revenues' => $revenues,
            'orders' => $orders
        ];
    }
    
    public function generateCategoryChart() {
        $data = $this->getCategoryStats();
        
        $categories = [];
        $revenues = [];
        
        foreach ($data as $row) {
            $categories[] = $row['category_name'];
            $revenues[] = (float)$row['total_revenue'];
        }
        
        return [
            'categories' => $categories,
            'revenues' => $revenues
        ];
    }
    
    public function generatePaymentMethodChart() {
        $data = $this->getPaymentMethodStats();
        
        $methods = [];
        $counts = [];
        
        foreach ($data as $row) {
            $methods[] = ucfirst($row['payment_method']);
            $counts[] = (int)$row['order_count'];
        }
        
        return [
            'methods' => $methods,
            'counts' => $counts
        ];
    }
    
    public function updateDailyStats() {
        $today = date('Y-m-d');
        
        // Vérifier si les stats du jour existent déjà
        $stmt = $this->pdo->prepare("SELECT id FROM sales_stats WHERE date = ?");
        $stmt->execute([$today]);
        
        if ($stmt->fetch()) {
            // Mise à jour des stats existantes
            $stmt = $this->pdo->prepare("
                UPDATE sales_stats SET
                    total_sales = (
                        SELECT COALESCE(SUM(total_amount), 0)
                        FROM orders 
                        WHERE DATE(created_at) = ? 
                        AND status IN ('paid', 'shipped', 'delivered')
                    ),
                    total_orders = (
                        SELECT COUNT(*)
                        FROM orders 
                        WHERE DATE(created_at) = ? 
                        AND status IN ('paid', 'shipped', 'delivered')
                    ),
                    total_products_sold = (
                        SELECT COALESCE(SUM(quantity), 0)
                        FROM order_items oi
                        JOIN orders o ON oi.order_id = o.id
                        WHERE DATE(o.created_at) = ? 
                        AND o.status IN ('paid', 'shipped', 'delivered')
                    ),
                    total_customers = (
                        SELECT COUNT(DISTINCT user_id)
                        FROM orders 
                        WHERE DATE(created_at) = ? 
                        AND status IN ('paid', 'shipped', 'delivered')
                    ),
                    average_order_value = (
                        SELECT COALESCE(AVG(total_amount), 0)
                        FROM orders 
                        WHERE DATE(created_at) = ? 
                        AND status IN ('paid', 'shipped', 'delivered')
                    )
                WHERE date = ?
            ");
            
            $stmt->execute([$today, $today, $today, $today, $today, $today]);
        } else {
            // Création de nouvelles stats
            $stmt = $this->pdo->prepare("
                INSERT INTO sales_stats (date, total_sales, total_orders, total_products_sold, total_customers, average_order_value)
                SELECT 
                    ? as date,
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COUNT(*) as total_orders,
                    COALESCE((
                        SELECT SUM(quantity)
                        FROM order_items oi
                        JOIN orders o ON oi.order_id = o.id
                        WHERE DATE(o.created_at) = ?
                        AND o.status IN ('paid', 'shipped', 'delivered')
                    ), 0) as total_products_sold,
                    COUNT(DISTINCT user_id) as total_customers,
                    COALESCE(AVG(total_amount), 0) as average_order_value
                FROM orders 
                WHERE DATE(created_at) = ? 
                AND status IN ('paid', 'shipped', 'delivered')
            ");
            
            $stmt->execute([$today, $today, $today]);
        }
    }
}
?>
