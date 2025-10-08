<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Security\AdminSectionVoter;
use App\Service\DashboardWidgetService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private DashboardWidgetService $dashboardWidgetService
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Get all dashboard widget data
        $widgetData = $this->dashboardWidgetService->getAllWidgetData();
        
        return $this->render('admin/dashboard.html.twig', [
            'widgets' => $widgetData,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Dropshipping Admin Panel')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        // Products - accessible by users with VIEW_PRODUCTS permission
        if ($this->isGranted(AdminSectionVoter::VIEW_PRODUCTS)) {
            yield MenuItem::linkToCrud('Products', 'fas fa-box', Product::class);
        }
        
        // Customers - accessible by users with VIEW_CUSTOMERS permission
        if ($this->isGranted(AdminSectionVoter::VIEW_CUSTOMERS)) {
            yield MenuItem::linkToCrud('Customers', 'fas fa-users', Customer::class);
        }
        
        // Orders - accessible by users with VIEW_ORDERS permission
        if ($this->isGranted(AdminSectionVoter::VIEW_ORDERS)) {
            yield MenuItem::linkToCrud('Orders', 'fas fa-shopping-cart', Order::class);
        }
        
        // Suppliers - accessible by users with VIEW_SUPPLIERS permission
        if ($this->isGranted(AdminSectionVoter::VIEW_SUPPLIERS)) {
            yield MenuItem::linkToCrud('Suppliers', 'fas fa-truck', Supplier::class);
        }
        
        // Admin Users - accessible by users with VIEW_ADMIN_USERS permission
        if ($this->isGranted(AdminSectionVoter::VIEW_ADMIN_USERS)) {
            yield MenuItem::linkToCrud('Admin Users', 'fas fa-user-shield', AdminUser::class);
        }
    }


}