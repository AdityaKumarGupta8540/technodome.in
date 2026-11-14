<?php
// setup_db.php - Setup Database Tables for Technodome
require_once 'db_config.php';

try {
    // 1. Create leads table
    $query_leads = "
    CREATE TABLE IF NOT EXISTS leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        location VARCHAR(255) NOT NULL,
        course VARCHAR(100) NOT NULL,
        computer_knowledge VARCHAR(10),
        learning_mode VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($query_leads);
    echo "Leads table created/verified successfully.<br>";

    // 2. Create admins table
    $query_admins = "
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($query_admins);
    echo "Admins table created/verified successfully.<br>";

    // 3. Create default admin if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $username = 'admin';
        $password = password_hash('password123', PASSWORD_BCRYPT);
        $stmt_insert = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt_insert->execute([$username, $password]);
        echo "Default admin user created: admin / password123 (Please change this after first login).<br>";
    } else {
        echo "Admin user 'admin' already exists.<br>";
    }

    // 4. Create categories table
    $query_categories = "
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(120) NOT NULL UNIQUE,
        description TEXT,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($query_categories);
    echo "Categories table created/verified successfully.<br>";

    // 5. Create pricing_plans table
    $query_pricing = "
    CREATE TABLE IF NOT EXISTS pricing_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_name VARCHAR(150) NOT NULL,
        category_id INT NOT NULL,
        course_duration VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        discount_price DECIMAL(10,2) DEFAULT NULL,
        description TEXT,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($query_pricing);
    echo "Pricing plans table created/verified successfully.<br>";

    // Seed initial categories if empty
    $cat_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($cat_count == 0) {
        $sample_categories = [
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
        $cat_stmt = $pdo->prepare("INSERT INTO categories (category_name, slug, description, status) VALUES (?, ?, ?, ?)");
        foreach ($sample_categories as $sc) {
            $cat_stmt->execute($sc);
        }
        echo "Sample categories created.<br>";

        // Seed initial pricing plans
        $smm_id     = $pdo->query("SELECT id FROM categories WHERE slug = 'social-media-marketing'")->fetchColumn();
        $web_id     = $pdo->query("SELECT id FROM categories WHERE slug = 'website-development'")->fetchColumn();
        $design_id  = $pdo->query("SELECT id FROM categories WHERE slug = 'graphic-design'")->fetchColumn();
        $video_id   = $pdo->query("SELECT id FROM categories WHERE slug = 'video-creation-ai-tools'")->fetchColumn();

        $sample_plans = [
            ['SMM Growth Masterclass', $smm_id, '3 Months', 18000.00, 14999.00, 'End-to-end social media growth, ads strategy and audience monetization.', 'Active'],
            ['Full Stack Web Development', $web_id, '6 Months', 25000.00, 19999.00, 'Build professional business websites, landing pages, hosting & domain setups.', 'Active'],
            ['Graphic & Brand Design Pro', $design_id, '2.5 Months', 15000.00, 11999.00, 'Branding, ad creative design, Canva and advanced visual design tools.', 'Active'],
            ['Video Editing & 25+ AI Tools', $video_id, '3 Months', 22000.00, 17499.00, 'Viral reels, video production workflows, and 25+ AI automation tools.', 'Active']
        ];
        $plan_stmt = $pdo->prepare("INSERT INTO pricing_plans (plan_name, category_id, course_duration, price, discount_price, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($sample_plans as $sp) {
            $plan_stmt->execute($sp);
        }
        echo "Sample pricing plans created.<br>";
    }

    echo "<h3>Database Setup Complete!</h3>";
    echo "<p><a href='index.php'>Go to Website</a> | <a href='admin/login.php'>Go to Admin Login</a></p>";

} catch (PDOException $e) {
    die("Setup Error: " . $e->getMessage());
}
?>
