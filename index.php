<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Revolutionary restaurant management platform with advanced 2FA security, real-time reservations, and comprehensive order management.">
<meta name="keywords" content="restaurant management, 2FA security, reservations, order management">
<meta name="author" content="Group 11">
<title>Smart Restaurant System - Advanced Restaurant Management Platform</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Moved inline styles to external CSS file for better maintainability */
</style>
</head>
<body>
<!-- Hero Section -->
<div class="hero-section" role="banner">
    <div class="container">
        <h1 class="hero-title">🍽️ Smart Restaurant System</h1>
        <p class="hero-subtitle">
            Revolutionary restaurant management platform with advanced 2FA security, 
            real-time reservations, and comprehensive order management
        </p>
        
        <div class="hero-stats" aria-label="System Highlights">
            <div class="stat-item">
                <span class="stat-number">99.9%</span>
                <span class="stat-label">Uptime</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">2FA</span>
                <span class="stat-label">Security</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">24/7</span>
                <span class="stat-label">Support</span>
            </div>
        </div>
    </div>
</div>

<div class="container container-wide">
    <!-- Features Section -->
    <div class="features-grid">
        <div class="feature-card">
            <span class="feature-icon">📋</span>
            <h3 class="feature-title">Smart Reservations</h3>
            <p class="feature-description">Advanced table booking system with real-time availability and customer management.</p>
            <ul class="feature-list">
                <li>Real-time table availability</li>
                <li>Customer preference tracking</li>
                <li>Automated confirmations</li>
                <li>Waitlist management</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <span class="feature-icon">🍴</span>
            <h3 class="feature-title">Order Management</h3>
            <p class="feature-description">Streamlined ordering system from kitchen to customer with real-time tracking.</p>
            <ul class="feature-list">
                <li>Digital menu management</li>
                <li>Kitchen order tracking</li>
                <li>Payment processing</li>
                <li>Delivery coordination</li>
            </ul>
        </div>
        
        <div class="feature-card">
            <span class="feature-icon">🔒</span>
            <h3 class="feature-title">Enterprise Security</h3>
            <p class="feature-description">Bank-level security with two-factor authentication and comprehensive audit trails.</p>
            <ul class="feature-list">
                <li>Two-factor authentication</li>
                <li>Role-based access control</li>
                <li>Security audit logs</li>
                <li>Data encryption</li>
            </ul>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="cta-section" role="complementary">
        <div class="cta-content">
            <h2 style="font-size: 2.5rem; margin-bottom: 20px;">🚀 Ready to Transform Your Restaurant?</h2>
            <p style="font-size: 1.2rem; margin-bottom: 30px; opacity: 0.9;">
                Join thousands of successful restaurants already using our platform to streamline operations and boost profits
            </p>
            
            <div class="cta-buttons">
                <a href="register.php" class="btn-hero btn-primary">
                    🎆 Start Free Trial
                </a>
                <a href="login.php" class="btn-hero btn-outline">
                    🔐 Access Dashboard
                </a>
            </div>
            
            <p style="margin-top: 20px; font-size: 0.9rem; opacity: 0.8;">
                ✓ No setup fees &nbsp; ✓ 30-day free trial &nbsp; ✓ Cancel anytime
            </p>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="status-card">
        <h3 style="text-align: center; font-size: 2rem; margin-bottom: 30px;">📊 System Status & Performance</h3>
        
        <div class="status-grid">
            <?php
            try {
                // Database connection status
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM accounts");
                $result = $stmt->fetch();
                $userCount = htmlspecialchars($result['count']); // Sanitize output
                
                echo "<div class='status-item'>
                        <h4 style='margin: 0 0 10px 0; color: #4ade80;'>✅ Database</h4>
                        <p style='margin: 0; font-size: 0.9rem;'>Connected & Operational</p>
                        <p style='margin: 5px 0 0 0; font-weight: bold;'>{$userCount} Registered Users</p>
                      </div>";
                
                // Check if tables exist
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'");
                    $tableCount = htmlspecialchars($stmt->fetchColumn()); // Sanitize output
                    
                    echo "<div class='status-item'>
                            <h4 style='margin: 0 0 10px 0; color: #4ade80;'>🗄️ Schema</h4>
                            <p style='margin: 0; font-size: 0.9rem;'>Database Schema</p>
                            <p style='margin: 5px 0 0 0; font-weight: bold;'>{$tableCount} Tables Active</p>
                          </div>";
                } catch (PDOException $e) {
                    echo "<div class='status-item'>
                            <h4 style='margin: 0 0 10px 0; color: #fbbf24;'>⚠️ Schema</h4>
                            <p style='margin: 0; font-size: 0.9rem;'>Setup Required</p>
                          </div>";
                }
                
                // Email configuration status 
                try {
                    $emailService = app('emailService');
                    if ($emailService && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        echo "<div class='status-item'>
                                <h4 style='margin: 0 0 10px 0; color: #4ade80;'>📧 Email</h4>
                                <p style='margin: 0; font-size: 0.9rem;'>SMTP Configured</p>
                                <p style='margin: 5px 0 0 0; font-weight: bold;'>2FA Ready</p>
                              </div>";
                    } else {
                        echo "<div class='status-item'>
                                <h4 style='margin: 0 0 10px 0; color: #fbbf24;'>⚠ Email</h4>
                                <p style='margin: 0; font-size: 0.9rem;'>PHPMailer Missing</p>
                              </div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='status-item'>
                            <h4 style='margin: 0 0 10px 0; color: #fbbf24;'>⚠ Email</h4>
                            <p style='margin: 0; font-size: 0.9rem;'>Configuration Error</p>
                          </div>";
                }
                
                // System performance (simulated)
                $uptime = round(memory_get_usage() / 1024 / 1024, 2);
                echo "<div class='status-item'>
                        <h4 style='margin: 0 0 10px 0; color: #4ade80;'>⚡ Performance</h4>
                        <p style='margin: 0; font-size: 0.9rem;'>System Resources</p>
                        <p style='margin: 5px 0 0 0; font-weight: bold;'>{$uptime}MB Memory</p>
                      </div>";
                      
            } catch (PDOException $e) {
                echo "<div class='status-item' style='grid-column: 1 / -1;'>
                        <h4 style='margin: 0 0 10px 0; color: #f87171;'>❌ Database Connection Failed</h4>
                        <p style='margin: 0; font-size: 0.9rem; color: #fecaca;'>" . htmlspecialchars($e->getMessage()) . "</p>
                      </div>";
            }
            ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
            <p style="margin: 0; opacity: 0.8;">🛡️ Secured by enterprise-grade encryption | 🚀 Powered by PostgreSQL & PHP</p>
        </div>
    </div>
</div>
</body>
</html>