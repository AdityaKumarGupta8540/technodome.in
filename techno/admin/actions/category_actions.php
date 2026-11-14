<?php
// admin/actions/category_actions.php - Handle Category CRUD securely
require_once '../../db_config.php';
require_once '../auth.php';

require_login();

// Helper to create URL friendly slug
function make_slug($string) {
    $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower(trim($string)));
    return trim($slug, '-');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../categories.php");
    exit();
}

// Verify CSRF Token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash_message('error', 'Security token mismatch. Please try again.');
    header("Location: ../categories.php");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $category_name = trim($_POST['category_name'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $status        = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
        $custom_slug   = trim($_POST['slug'] ?? '');

        if (empty($category_name)) {
            set_flash_message('error', 'Category name is required.');
            header("Location: ../categories.php");
            exit();
        }

        $slug = !empty($custom_slug) ? make_slug($custom_slug) : make_slug($category_name);

        // Check if category name or slug already exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE category_name = ? OR slug = ?");
        $stmt->execute([$category_name, $slug]);
        if ($stmt->fetch()) {
            set_flash_message('error', 'A category with this name or slug already exists.');
            header("Location: ../categories.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, slug, description, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category_name, $slug, $description, $status]);
            set_flash_message('success', 'Category "' . htmlspecialchars($category_name) . '" created successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'update':
        $id            = intval($_POST['id'] ?? 0);
        $category_name = trim($_POST['category_name'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $status        = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
        $custom_slug   = trim($_POST['slug'] ?? '');

        if ($id <= 0 || empty($category_name)) {
            set_flash_message('error', 'Valid category ID and name are required.');
            header("Location: ../categories.php");
            exit();
        }

        $slug = !empty($custom_slug) ? make_slug($custom_slug) : make_slug($category_name);

        // Check duplicate name or slug on different ID
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE (category_name = ? OR slug = ?) AND id != ?");
        $stmt->execute([$category_name, $slug, $id]);
        if ($stmt->fetch()) {
            set_flash_message('error', 'Another category already exists with that name or slug.');
            header("Location: ../categories.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, slug = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$category_name, $slug, $description, $status, $id]);
            set_flash_message('success', 'Category updated successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'toggle_status':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash_message('error', 'Invalid category ID.');
            header("Location: ../categories.php");
            exit();
        }

        $stmt = $pdo->prepare("SELECT status FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if ($current !== false) {
            $new_status = ($current === 'Active') ? 'Inactive' : 'Active';
            $update = $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?");
            $update->execute([$new_status, $id]);
            set_flash_message('success', 'Category status changed to ' . $new_status . '.');
        } else {
            set_flash_message('error', 'Category not found.');
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash_message('error', 'Invalid category ID for deletion.');
            header("Location: ../categories.php");
            exit();
        }

        // Check for attached pricing plans
        $check = $pdo->prepare("SELECT COUNT(*) FROM pricing_plans WHERE category_id = ?");
        $check->execute([$id]);
        $plan_count = $check->fetchColumn();

        if ($plan_count > 0) {
            set_flash_message('error', 'Cannot delete category! It has ' . $plan_count . ' associated pricing plan(s). Please reassign or delete those plans first.');
            header("Location: ../categories.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Category successfully deleted.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Cannot delete category: ' . $e->getMessage());
        }
        break;

    default:
        set_flash_message('error', 'Invalid action specified.');
        break;
}

header("Location: ../categories.php");
exit();
