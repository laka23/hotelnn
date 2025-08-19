<?php

class DashboardController extends BaseController {
    public function index(): string {
        $pdo = Database::getConnection();
        $productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $stockSum = (int)$pdo->query('SELECT COALESCE(SUM(stock_qty),0) FROM products')->fetchColumn();
        $todaySales = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $openRepairs = (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status IN ('received','in_progress','ready')")->fetchColumn();
        return $this->render('dashboard/index', [
            'productCount' => $productCount,
            'stockSum' => $stockSum,
            'todaySales' => $todaySales,
            'openRepairs' => $openRepairs,
        ]);
    }
}

