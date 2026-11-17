<?php
// admin/actions/pricing_actions.php - Handle Pricing CRUD securely
require_once '../../db_config.php';
require_once '../auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pricing.php");
    exit();
}

// Verify CSRF Token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash_message('error', 'Security token mismatch. Please try again.');
    header("Location: ../pricing.php");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $plan_name       = trim($_POST['plan_name'] ?? '');
        $category_id     = intval($_POST['category_id'] ?? 0);
        $course_duration = trim($_POST['course_duration'] ?? '');
        $price           = floatval($_POST['price'] ?? 0);
        $discount_price  = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
        $course_mode     = in_array($_POST['course_mode'] ?? '', ['Online','Offline','Both']) ? $_POST['course_mode'] : 'Both';
        $monthly_fee     = !empty($_POST['monthly_fee']) ? floatval($_POST['monthly_fee']) : null;
        $description     = trim($_POST['description'] ?? '');
        $status          = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

        if (empty($plan_name) || $category_id <= 0 || empty($course_duration) || $price < 0) {
            set_flash_message('error', 'Please provide a valid plan name, course category, duration, and price.');
            header("Location: ../pricing.php");
            exit();
        }

        // Validate discount price is not higher than price
        if ($discount_price !== null && $discount_price > $price) {
            set_flash_message('error', 'Discounted price cannot be greater than regular price.');
            header("Location: ../pricing.php");
            exit();
        }

        // Validate that category exists
        $catCheck = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        $catCheck->execute([$category_id]);
        if (!$catCheck->fetch()) {
            set_flash_message('error', 'Selected course category does not exist.');
            header("Location: ../pricing.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO pricing_plans (plan_name, category_id, course_duration, price, discount_price, description, course_mode, monthly_fee, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$plan_name, $category_id, $course_duration, $price, $discount_price, $description, $course_mode, $monthly_fee, $status]);
            set_flash_message('success', 'Pricing plan "' . htmlspecialchars($plan_name) . '" created successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'update':
        $id              = intval($_POST['id'] ?? 0);
        $plan_name       = trim($_POST['plan_name'] ?? '');
        $category_id     = intval($_POST['category_id'] ?? 0);
        $course_duration = trim($_POST['course_duration'] ?? '');
        $price           = floatval($_POST['price'] ?? 0);
        $discount_price  = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
        $course_mode     = in_array($_POST['course_mode'] ?? '', ['Online','Offline','Both']) ? $_POST['course_mode'] : 'Both';
        $monthly_fee     = !empty($_POST['monthly_fee']) ? floatval($_POST['monthly_fee']) : null;
        $description     = trim($_POST['description'] ?? '');
        $status          = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

        if ($id <= 0 || empty($plan_name) || $category_id <= 0 || empty($course_duration) || $price < 0) {
            set_flash_message('error', 'Please check all required fields for the pricing plan.');
            header("Location: ../pricing.php");
            exit();
        }

        if ($discount_price !== null && $discount_price > $price) {
            set_flash_message('error', 'Discounted price cannot be greater than regular price.');
            header("Location: ../pricing.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE pricing_plans 
                SET plan_name = ?, category_id = ?, course_duration = ?, price = ?, discount_price = ?, description = ?, course_mode = ?, monthly_fee = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$plan_name, $category_id, $course_duration, $price, $discount_price, $description, $course_mode, $monthly_fee, $status, $id]);
            set_flash_message('success', 'Pricing plan updated successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'toggle_status':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash_message('error', 'Invalid pricing plan ID.');
            header("Location: ../pricing.php");
            exit();
        }

        $stmt = $pdo->prepare("SELECT status FROM pricing_plans WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if ($current !== false) {
            $new_status = ($current === 'Active') ? 'Inactive' : 'Active';
            $update = $pdo->prepare("UPDATE pricing_plans SET status = ? WHERE id = ?");
            $update->execute([$new_status, $id]);
            set_flash_message('success', 'Plan status updated to ' . $new_status . '.');
        } else {
            set_flash_message('error', 'Pricing plan not found.');
        }
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash_message('error', 'Invalid pricing plan ID for deletion.');
            header("Location: ../pricing.php");
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM pricing_plans WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Pricing plan deleted successfully.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Could not delete pricing plan: ' . $e->getMessage());
        }
        break;

    default:
        set_flash_message('error', 'Invalid action specified.');
        break;
}

header("Location: ../pricing.php");
exit();
