<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Restaurant System - Advanced Restaurant Management Platform</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* Enhanced Hero Section */
.hero-section {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 300"><defs><pattern id="restaurant" patternUnits="userSpaceOnUse" width="100" height="100"><circle cx="50" cy="50" r="2" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100%25" height="100%25" fill="url(%23restaurant)"/></svg>');
    padding: 80px 0;
    text-align: center;
    color: white;
    margin: -50px -40px 40px -40px;
    border-radius: 0 0 30px 30px;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    background: linear-gradient(45deg, #ffffff, #f0f8ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 1.3rem;
    margin-bottom: 40px;
    opacity: 0.95;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 40px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Feature Cards */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin: 40px 0;
}

.feature-card {
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(45deg, #667eea, #764ba2);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 20px;
    display: block;
}

.feature-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #2c3e50;
}

.feature-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}

.feature-list {
    list-style: none;
    padding: 0;
    text-align: left;
}

.feature-list li {
    padding: 5px 0;
    color: #555;
}

.feature-list li::before {
    content: '✓';
    color: #28a745;
    font-weight: bold;
    margin-right: 10px;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 50px;
    border-radius: 20px;
    text-align: center;
    margin: 40px 0;
    position: relative;
    overflow: hidden;
}

.cta-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></svg>') repeat;
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

.cta-content {
    position: relative;
    z-index: 2;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 30px;
}

.btn-hero {
    padding: 15px 30px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 50px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primary {
    background: white;
    color: #28a745;
    border: 2px solid white;
}

.btn-primary:hover {
    background: transparent;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.btn-outline {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-outline:hover {
    background: white;
    color: #28a745;
    transform: translateY(-2px);
}

/* System Status */
.status-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 30px;
    border-radius: 20px;
    margin: 40px 0;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.status-item {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    backdrop-filter: blur(10px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title { font-size: 2.5rem; }
    .hero-stats { gap: 20px; }
    .features-grid { grid-template-columns: 1fr; }
    .cta-buttons { flex-direction: column; align-items: center; }
    .hero-section { margin: -50px -20px 40px -20px; padding: 60px 20px; }
}
</style>
</head>
<body>
<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <h1 class="hero-title">🍽️ Smart Restaurant System</h1>
        <p class="hero-subtitle">
            Revolutionary restaurant management platform with advanced 2FA security, 
            real-time reservations, and comprehensive order management
        </p>
        
        <div class="hero-stats">
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
    <div class="cta-section">
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
                $userCount = $result['count'];
                
                echo "<div class='status-item'>
                        <h4 style='margin: 0 0 10px 0; color: #4ade80;'>✅ Database</h4>
                        <p style='margin: 0; font-size: 0.9rem;'>Connected & Operational</p>
                        <p style='margin: 5px 0 0 0; font-weight: bold;'>{$userCount} Registered Users</p>
                      </div>";
                
                // Check if tables exist
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'");
                    $tableCount = $stmt->fetchColumn();
                    
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
                if (defined('SMTP_PASSWORD') && SMTP_PASSWORD !== 'your_gmail_app_password') {
                    echo "<div class='status-item'>
                            <h4 style='margin: 0 0 10px 0; color: #4ade80;'>📧 Email</h4>
                            <p style='margin: 0; font-size: 0.9rem;'>SMTP Configured</p>
                            <p style='margin: 5px 0 0 0; font-weight: bold;'>2FA Ready</p>
                          </div>";
                } else {
                    echo "<div class='status-item'>
                            <h4 style='margin: 0 0 10px 0; color: #fbbf24;'>⚠️ Email</h4>
                            <p style='margin: 0; font-size: 0.9rem;'>Configuration Needed</p>
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