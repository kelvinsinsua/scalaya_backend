<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;

class DashboardWidgetService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CustomerRepository $customerRepository,
        private OrderRepository $orderRepository,
        private SupplierRepository $supplierRepository
    ) {
    }

    /**
     * Get statistics widget data
     */
    public function getStatisticsWidget(): array
    {
        return [
            'totalProducts' => $this->productRepository->count([]),
            'totalCustomers' => $this->customerRepository->count([]),
            'totalOrders' => $this->orderRepository->count([]),
            'totalSuppliers' => $this->supplierRepository->count([]),
        ];
    }

    /**
     * Get recent activity widget data
     */
    public function getRecentActivityWidget(int $days = 7, int $limit = 10): array
    {
        $recentOrders = $this->orderRepository->findRecentOrders($days);
        
        return [
            'recentOrders' => array_slice($recentOrders, 0, $limit),
            'totalRecentOrders' => count($recentOrders),
            'days' => $days,
        ];
    }

    /**
     * Get low stock alerts widget data
     */
    public function getLowStockAlertsWidget(int $threshold = 10, int $limit = 10): array
    {
        $lowStockProducts = $this->productRepository->findLowStock($threshold);
        
        return [
            'lowStockProducts' => array_slice($lowStockProducts, 0, $limit),
            'totalLowStockProducts' => count($lowStockProducts),
            'threshold' => $threshold,
        ];
    }

    /**
     * Get pending orders widget data
     */
    public function getPendingOrdersWidget(int $limit = 10): array
    {
        $pendingOrders = $this->orderRepository->findByStatus(Order::STATUS_PENDING);
        $processingOrders = $this->orderRepository->findByStatus(Order::STATUS_PROCESSING);
        
        // Combine pending and processing orders as they both require attention
        $ordersRequiringAttention = array_merge($pendingOrders, $processingOrders);
        
        // Sort by creation date (oldest first for priority)
        usort($ordersRequiringAttention, function($a, $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        
        return [
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'ordersRequiringAttention' => array_slice($ordersRequiringAttention, 0, $limit),
            'totalOrdersRequiringAttention' => count($ordersRequiringAttention),
        ];
    }

    /**
     * Get revenue and order statistics widget data
     */
    public function getRevenueStatisticsWidget(): array
    {
        $orderStatistics = $this->orderRepository->getOrderStatistics();
        
        // Format order statistics for easier use
        $ordersByStatus = [];
        $totalRevenue = 0;
        $totalOrders = 0;
        
        foreach ($orderStatistics as $stat) {
            $ordersByStatus[$stat['status']] = [
                'count' => (int) $stat['count'],
                'totalAmount' => (float) $stat['totalAmount'],
                'averageAmount' => (float) $stat['averageAmount']
            ];
            $totalRevenue += (float) $stat['totalAmount'];
            $totalOrders += (int) $stat['count'];
        }
        
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        return [
            'ordersByStatus' => $ordersByStatus,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'averageOrderValue' => $averageOrderValue,
        ];
    }

    /**
     * Get all dashboard widget data
     */
    public function getAllWidgetData(): array
    {
        return [
            'statistics' => $this->getStatisticsWidget(),
            'recentActivity' => $this->getRecentActivityWidget(),
            'lowStockAlerts' => $this->getLowStockAlertsWidget(),
            'pendingOrders' => $this->getPendingOrdersWidget(),
            'revenueStatistics' => $this->getRevenueStatisticsWidget(),
        ];
    }

    /**
     * Get customer statistics for dashboard
     */
    public function getCustomerStatistics(): array
    {
        $activeCustomers = $this->customerRepository->countByStatus('active');
        $inactiveCustomers = $this->customerRepository->countByStatus('inactive');
        $blockedCustomers = $this->customerRepository->countByStatus('blocked');
        
        return [
            'active' => $activeCustomers,
            'inactive' => $inactiveCustomers,
            'blocked' => $blockedCustomers,
            'total' => $activeCustomers + $inactiveCustomers + $blockedCustomers,
        ];
    }

    /**
     * Get product statistics for dashboard
     */
    public function getProductStatistics(): array
    {
        $productCounts = $this->productRepository->countByStatus();
        
        return [
            'available' => $productCounts['available'] ?? 0,
            'out_of_stock' => $productCounts['out_of_stock'] ?? 0,
            'discontinued' => $productCounts['discontinued'] ?? 0,
            'total' => array_sum($productCounts),
        ];
    }

    /**
     * Get supplier statistics for dashboard
     */
    public function getSupplierStatistics(): array
    {
        $activeSuppliers = $this->supplierRepository->countByStatus('active');
        $inactiveSuppliers = $this->supplierRepository->countByStatus('inactive');
        
        return [
            'active' => $activeSuppliers,
            'inactive' => $inactiveSuppliers,
            'total' => $activeSuppliers + $inactiveSuppliers,
        ];
    }
}