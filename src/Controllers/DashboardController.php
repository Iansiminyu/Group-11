<?php

namespace SmartRestaurant\Controllers;

use SmartRestaurant\Services\AuthService;
use SmartRestaurant\Services\SessionService;

/**
 * Dashboard controller for authenticated users
 */
class DashboardController extends BaseController
{
    private AuthService $authService;

    public function __construct(AuthService $authService, SessionService $sessionService)
    {
        parent::__construct($sessionService);
        $this->authService = $authService;
    }

    /**
     * Show dashboard
     */
    public function index(): void
    {
        $this->requireAuth();

        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->redirectWithError('login.php', 'Session expired. Please login again.');
        }

        $this->render('dashboard/index', [
            'user' => $user,
            'pageTitle' => 'Dashboard - Smart Restaurant System'
        ]);
    }

    /**
     * Show user profile
     */
    public function profile(): void
    {
        $this->requireAuth();

        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->redirectWithError('login.php', 'Session expired. Please login again.');
        }

        $this->render('dashboard/profile', [
            'user' => $user,
            'pageTitle' => 'Profile - Smart Restaurant System'
        ]);
    }

    /**
     * Show 2FA settings
     */
    public function twoFactorSettings(): void
    {
        $this->requireAuth();

        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->redirectWithError('login.php', 'Session expired. Please login again.');
        }

        $this->render('dashboard/2fa_settings', [
            'user' => $user,
            'pageTitle' => '2FA Settings - Smart Restaurant System'
        ]);
    }

    /**
     * Handle 2FA settings update
     */
    public function update2FASettings(): void
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->redirect('enable_2fa.php');
        }

        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            $this->redirectWithError('login.php', 'Session expired. Please login again.');
        }

        $enabled = (bool)$this->getPost('enable_2fa', false);
        $type = $this->getPost('two_factor_type', 'email');

        try {
            $user->update2FASettings($enabled, $type);
            
            $message = $enabled 
                ? '2FA has been enabled successfully!' 
                : '2FA has been disabled successfully!';
                
            $this->redirectWithSuccess('enable_2fa.php', $message);
            
        } catch (\Exception $e) {
            $this->redirectWithError('enable_2fa.php', 'Failed to update 2FA settings. Please try again.');
        }
    }
}
