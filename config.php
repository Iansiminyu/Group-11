<?php

/**
 * Legacy config file - now redirects to OOP bootstrap
 * This file is kept for backward compatibility
 */

// Load the new OOP bootstrap
require_once __DIR__ . '/src/bootstrap.php';

// For backward compatibility, create global $pdo variable
$pdo = SmartRestaurant\Core\Database::getInstance()->getConnection();
?>