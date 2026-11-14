<?php
/**
 * migrate.php - Database Migrations Runner for Technodome
 * 
 * Safely executes database schema updates, missing columns, indexes,
 * and initial configuration without destroying existing data.
 * Can be run via browser or CLI (`php migrate.php`).
 */
require_once __DIR__ . '/db_config.php';

$isCli = (php_sapi_name() === 'cli');
function logMessage($msg, $type = 'info') {
    global $isCli;
    if ($isCli) {
        $prefix = $type === 'success' ? '[SUCCESS] ' : ($type === 'error' ? '[ERROR] ' : '[INFO] ');
        echo $prefix . strip_tags($msg) . PHP_EOL;
    } else {
        $color = $type === 'success' ? 'green' : ($type === 'error' ? 'red' : '#333');
        echo "<div style='color: {$color}; margin: 4px 0; font-family: monospace; font-size: 14px;'>{$msg}</div>";
    }
}

logMessage("== Starting Database Migrations for Technodome ==");

try {
    // 1. Create leads table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            mobile VARCHAR(20) NOT NULL,
            location VARCHAR(255) NOT NULL,
            course VARCHAR(100) NOT NULL,
            computer_knowledge VARCHAR(10) DEFAULT NULL,
            learning_mode VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    logMessage("Table 'leads' verified/created.", 'success');

    // 2. Create admins table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    logMessage("Table 'admins' verified/created.", 'success');

    // Default admin if table is empty
    $adminCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($adminCount == 0) {
        $defaultUser = 'admin';
        $defaultPass = password_hash('password123', PASSWORD_BCRYPT);
        $insertAdmin = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $insertAdmin->execute([$defaultUser, $defaultPass]);
        logMessage("Default admin created: <strong>admin</strong> / <strong>password123</strong>", 'success');
    }

    // 3. Create categories table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(120) NOT NULL UNIQUE,
            description TEXT DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    logMessage("Table 'categories' verified/created.", 'success');

    // Check & Add 'image' column to categories if missing
    $catCols = $pdo->query("SHOW COLUMNS FROM categories LIKE 'image'")->fetchAll();
    if (empty($catCols)) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description");
        logMessage("Column 'image' added to table 'categories'.", 'success');
    } else {
        logMessage("Column 'image' already exists in 'categories'.");
    }

    // 4. Create pricing_plans table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pricing_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_name VARCHAR(150) NOT NULL,
            category_id INT NOT NULL,
            course_duration VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_price DECIMAL(10,2) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_pricing_category (category_id),
            INDEX idx_pricing_status (status),
            CONSTRAINT fk_pricing_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    logMessage("Table 'pricing_plans' verified/created.", 'success');

    // 5. Seed default categories if table is empty
    $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount == 0) {
        $initialCategories = [
            [
                'Social Media Marketing',
                'social-media-marketing',
                "Facebook, Instagram & YouTube Strategy\nIncrease Followers & Subscribers\nPaid Ads & Performance Marketing",
                'Active'
            ],
            [
                'Website Development',
                'website-development',
                "Create professional websites\nLanding pages for business growth\nBasics of domain, hosting & setup",
                'Active'
            ],
            [
                'Graphic Design',
                'graphic-design',
                "Social media creative\nBranding & ad design\nTools like Canva & advanced design concepts",
                'Active'
            ],
            [
                'Video Creation & AI Tools',
                'video-creation-ai-tools',
                "Reel & video editing techniques\nViral content strategy\nImplementation of 25+ AI Tools for faster growth",
                'Active'
            ]
        ];
        $insertCat = $pdo->prepare("INSERT INTO categories (category_name, slug, description, status) VALUES (?, ?, ?, ?)");
        foreach ($initialCategories as $c) {
            $insertCat->execute($c);
        }
        logMessage("Default categories inserted.", 'success');

        // Seed default pricing plans
        $smmId = $pdo->query("SELECT id FROM categories WHERE slug = 'social-media-marketing'")->fetchColumn();
        $webId = $pdo->query("SELECT id FROM categories WHERE slug = 'website-development'")->fetchColumn();
        $desId = $pdo->query("SELECT id FROM categories WHERE slug = 'graphic-design'")->fetchColumn();
        $vidId = $pdo->query("SELECT id FROM categories WHERE slug = 'video-creation-ai-tools'")->fetchColumn();

        $initialPlans = [
            ['SMM Growth Masterclass', $smmId, '3 Months', 18000.00, 14999.00, "Facebook, Instagram & YouTube Strategy\nPaid Ads & Performance Marketing\nAudience Monetization", 'Active'],
            ['Full Stack Web Development', $webId, '6 Months', 25000.00, 19999.00, "Create professional websites\nLanding pages for business growth\nBasics of domain, hosting & setup", 'Active'],
            ['Graphic & Brand Design Pro', $desId, '2.5 Months', 15000.00, 11999.00, "Social media creative\nBranding & ad design\nTools like Canva & advanced design concepts", 'Active'],
            ['Video Editing & 25+ AI Tools', $vidId, '3 Months', 22000.00, 17499.00, "Reel & video editing techniques\nViral content strategy\nImplementation of 25+ AI Tools for faster growth", 'Active']
        ];
        $insertPlan = $pdo->prepare("INSERT INTO pricing_plans (plan_name, category_id, course_duration, price, discount_price, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($initialPlans as $p) {
            $insertPlan->execute($p);
        }
        logMessage("Default pricing plans inserted.", 'success');
    }

    logMessage("== All Database Migrations Completed Successfully ==", 'success');

    if (!$isCli) {
        echo "<p style='margin-top:20px;'><a href='index.php'>&larr; Back to Website</a> | <a href='admin/'>Go to Admin Panel</a></p>";
    }

} catch (PDOException $e) {
    logMessage("Migration Failed: " . $e->getMessage(), 'error');
}
