<?php
declare(strict_types=1);

// Fix path encoding issues
$basePath = '/syokichem/admin/'; // Use literal space, not %20

// Secure session initialization
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => $basePath,
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => false,    // Disable in localhost
        'httponly' => true,
        'samesite' => 'Lax'   // More flexible than Strict for localhost
    ]);
    session_start();
}

/**
 * Check if user is logged in as admin
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn(): bool {
    // Check all session requirements
    return isset($_SESSION['admin_id']) && 
           !empty($_SESSION['admin_logged_in']) &&
           $_SESSION['admin_logged_in'] === true &&
           ($_SESSION['last_activity'] ?? 0) > (time() - 1800); // 30 min timeout
}

/**
 * Require authentication for protected pages
 * 
 * @return void
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit;
    }
    $_SESSION['last_activity'] = time(); // Update activity timestamp
}

/**
 * Generate CSRF token and store it in session
 * 
 * @return string The generated CSRF token
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Validate CSRF token
 * 
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken(string $token): bool {
    $result = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    error_log("CSRF Validation: ".($result ? "Valid" : "Invalid")." (Session: ".($_SESSION['csrf_token'] ?? 'null')." vs Submitted: $token)");
    return $result;
}

/**
 * Redirect with a flash message
 * 
 * @param string $url URL to redirect to
 * @param string $type Message type (success, error, warning, info)
 * @param string $message The message to display
 * @return void
 */
function redirectWithMessage(string $url, string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['flash_messages'] = $_SESSION['flash_messages'] ?? [];
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];

    header("Location: $url");
    exit;
}

/**
 * Display flash messages and clear them from session
 * 
 * @return void
 */
function displayFlashMessages(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!empty($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $message) {
            $alertClass = match($message['type']) {
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info',
                default => 'alert-primary'
            };
            
            echo '<div class="alert ' . htmlspecialchars($alertClass) . ' alert-dismissible fade show">';
            echo htmlspecialchars($message['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        
        // Clear messages after displaying
        unset($_SESSION['flash_messages']);
    }
}

/**
 * Log admin actions for audit trail
 * 
 * @param int $adminId The ID of the admin performing the action
 * @param string $action The action performed
 * @param string $details Additional details about the action
 * @return bool True if logged successfully, false otherwise
 */
function logAdminAction(int $adminId, string $action, string $details): bool {
    global $conn;

    try {
        $stmt = $conn->prepare("
            INSERT INTO admin_logs 
            (admin_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return $stmt->execute([$adminId, $action, $details, $ip, $userAgent]);
    } catch (PDOException $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current admin details
 * 
 * @param bool $forceRefresh Whether to force refresh from database
 * @return array|null Admin details or null if not found
 */
function getCurrentAdmin(bool $forceRefresh = false): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    // Return cached data if available and not forcing refresh
    if (isset($_SESSION['admin_data']) && !$forceRefresh) {
        return $_SESSION['admin_data'];
    }

    global $conn;

    try {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        // Cache the admin data in session
        if ($admin) {
            $_SESSION['admin_data'] = $admin;
        }
        
        return $admin;
    } catch (PDOException $e) {
        error_log("Failed to get admin details: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if admin has specific permission
 * 
 * @param string $permission The permission to check
 * @return bool True if has permission, false otherwise
 */
function hasPermission(string $permission): bool {
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }

    // Super admin has all permissions
    if ($admin['is_super_admin'] ?? false) {
        return true;
    }

    // Check role-based permissions
    if (isset($admin['permissions'])) {
        $permissions = json_decode($admin['permissions'], true) ?? [];
        return in_array($permission, $permissions);
    }

    return false;
}

/**
 * Get unread notification count for current admin
 * 
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount(): int {
    if (!isLoggedIn()) {
        return 0;
    }

    global $conn;

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_notifications WHERE admin_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['admin_id']]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Failed to get unread notifications: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get recent notifications for admin
 * 
 * @param int $limit Number of notifications to return
 * @return array Array of notifications
 */
function getRecentNotifications(int $limit = 5): array {
    if (!isLoggedIn()) {
        return [];
    }

    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT * FROM admin_notifications 
            WHERE admin_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$_SESSION['admin_id'], $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Failed to get recent notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Format timestamp as "time ago"
 * 
 * @param string $datetime The datetime string
 * @return string Formatted time ago string
 */
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'just now';
    }

    $intervals = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute'
    ];

    foreach ($intervals as $seconds => $unit) {
        $value = floor($diff / $seconds);
        if ($value >= 1) {
            return $value . ' ' . $unit . ($value > 1 ? 's' : '') . ' ago';
        }
    }

    return date('M j, Y', $time);
}

/**
 * Create a URL-friendly slug from a string
 */
function createSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Generate a unique slug by checking the database
 */
function getUniqueSlug(PDO $conn, string $slug): string {
    $originalSlug = $slug;
    $counter = 1;
    
    // Keep trying until we find a unique slug
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
        $stmt->execute([$slug]);
        
        // If no post found with this slug, it's available
        if (!$stmt->fetch()) {
            return $slug;
        }
        
        // Otherwise, append a number and try again
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
}

/**
 * Handle file uploads with validation
 */
function handleImageUpload(string $fieldName, array &$errors): ?string {
    if (empty($_FILES[$fieldName]['name'])) {
        return null;
    }
    
    $uploadDir = __DIR__ . '/../../images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileExt = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $filename = 'blog-' . uniqid() . '.' . $fileExt;
    $uploadFile = $uploadDir . $filename;
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES[$fieldName]['type'], $allowedTypes)) {
        $errors[$fieldName] = 'Only JPG, PNG, GIF, and WebP images are allowed';
        return null;
    }
    
    if ($_FILES[$fieldName]['size'] > 2 * 1024 * 1024) { // 2MB limit
        $errors[$fieldName] = 'Image size must be less than 2MB';
        return null;
    }
    
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $uploadFile)) {
        return $filename; // Return just the filename since we're using a consistent images directory
    }
    
    $errors[$fieldName] = 'File upload failed';
    return null;
}

/**
 * Sanitize input data
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}