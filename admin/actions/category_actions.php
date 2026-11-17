<?php
// admin/actions/category_actions.php - Handle Category CRUD securely
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../auth.php';

require_login();

// Helper to create URL friendly slug
function make_slug($string) {
    $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower(trim($string)));
    return trim($slug, '-');
}

/**
 * Resize/Crop image to 600x600 and save
 * Returns relative path e.g. "uploads/categories/cat_xxx.jpg" or null on failure
 */
function process_category_image($file, $targetWidth = 600, $targetHeight = 600) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mimeType = null;

    if (function_exists('finfo_open')) {
        $fileInfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo) {
            $mimeType = finfo_file($fileInfo, $file['tmp_name']);
            finfo_close($fileInfo);
        }
    }

    if (!$mimeType && function_exists('mime_content_type')) {
        $mimeType = @mime_content_type($file['tmp_name']);
    }

    if (!$mimeType && function_exists('getimagesize')) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!empty($imageInfo['mime'])) {
            $mimeType = $imageInfo['mime'];
        }
    }

    if (!in_array($mimeType, $allowedMimes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, WEBP, and GIF images are allowed.'];
    }

    // Target upload directory
    $uploadDir = realpath(__DIR__ . '/../../') . '/uploads/categories/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = 'jpg';
    if ($mimeType === 'image/png') $ext = 'png';
    elseif ($mimeType === 'image/webp') $ext = 'webp';
    elseif ($mimeType === 'image/gif') $ext = 'gif';

    $fileName = 'cat_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    // Process with GD if loaded
    if (extension_loaded('gd')) {
        $srcImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                $srcImage = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : null;
                break;
            case 'image/gif':
                $srcImage = @imagecreatefromgif($file['tmp_name']);
                break;
        }

        if ($srcImage) {
            $origWidth = imagesx($srcImage);
            $origHeight = imagesy($srcImage);

            // Compute center-crop rectangle to fit 600x600 ratio
            $targetRatio = $targetWidth / $targetHeight;
            $origRatio = $origWidth / $origHeight;

            if ($origRatio > $targetRatio) {
                // Original is wider: crop sides
                $cropHeight = $origHeight;
                $cropWidth = (int)($origHeight * $targetRatio);
                $cropX = (int)(($origWidth - $cropWidth) / 2);
                $cropY = 0;
            } else {
                // Original is taller: crop top & bottom
                $cropWidth = $origWidth;
                $cropHeight = (int)($origWidth / $targetRatio);
                $cropX = 0;
                $cropY = (int)(($origHeight - $cropHeight) / 2);
            }

            $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);

            // Preserve transparency for PNG and WEBP
            if ($mimeType === 'image/png' || $mimeType === 'image/webp' || $mimeType === 'image/gif') {
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
                imagefilledrectangle($dstImage, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            imagecopyresampled($dstImage, $srcImage, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

            $saved = false;
            if ($ext === 'png') {
                $saved = imagepng($dstImage, $targetPath, 8);
            } elseif ($ext === 'webp') {
                $saved = imagewebp($dstImage, $targetPath, 88);
            } elseif ($ext === 'gif') {
                $saved = imagegif($dstImage, $targetPath);
            } else {
                $saved = imagejpeg($dstImage, $targetPath, 90);
            }

            imagedestroy($srcImage);
            imagedestroy($dstImage);

            if ($saved) {
                return ['path' => 'uploads/categories/' . $fileName];
            }
        }
    }

    // Fallback if GD is unavailable or fails
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['path' => 'uploads/categories/' . $fileName];
    }

    return ['error' => 'Failed to save uploaded image. Please check directory permissions.'];
}

function remove_old_image($relativePath) {
    if (empty($relativePath)) return;
    $fullPath = realpath(__DIR__ . '/../../') . '/' . ltrim($relativePath, '/\\');
    if (file_exists($fullPath) && is_file($fullPath)) {
        @unlink($fullPath);
    }
}

// Ensure POST request
if (php_sapi_name() !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: ../categories.php");
    exit();
}

// Only execute controller logic if called directly via HTTP request
if (php_sapi_name() === 'cli' && empty($_POST['action'])) {
    return;
}

// Verify CSRF Token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    // If request was AJAX, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please reload.']);
        exit();
    }
    set_flash_message('error', 'Security token mismatch. Please try again.');
    header("Location: ../categories.php");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'upload_row_image':
        $id = intval($_POST['id'] ?? 0);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($id <= 0) {
            $msg = 'Invalid category ID.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            set_flash_message('error', $msg);
            header("Location: ../categories.php");
            exit();
        }

        if (!isset($_FILES['row_image']) || $_FILES['row_image']['error'] === UPLOAD_ERR_NO_FILE) {
            $msg = 'Please select an image file to upload.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            set_flash_message('error', $msg);
            header("Location: ../categories.php");
            exit();
        }

        $res = process_category_image($_FILES['row_image'], 600, 600);
        if (isset($res['error'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $res['error']]);
                exit();
            }
            set_flash_message('error', $res['error']);
            header("Location: ../categories.php");
            exit();
        }

        if (isset($res['path'])) {
            $newImagePath = $res['path'];

            // Get old image to delete
            $stmt = $pdo->prepare("SELECT image, category_name FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $cat = $stmt->fetch();
            if ($cat && !empty($cat['image'])) {
                remove_old_image($cat['image']);
            }

            $update = $pdo->prepare("UPDATE categories SET image = ? WHERE id = ?");
            $update->execute([$newImagePath, $id]);

            $successMsg = 'Image for "' . ($cat['category_name'] ?? 'category') . '" updated successfully (600x600).';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $successMsg,
                    'image' => $newImagePath
                ]);
                exit();
            }
            set_flash_message('success', $successMsg);
        }
        header("Location: ../categories.php");
        exit();

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

        // Handle Image Upload if provided
        $imagePath = null;
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imgRes = process_category_image($_FILES['category_image'], 600, 600);
            if (isset($imgRes['error'])) {
                set_flash_message('error', $imgRes['error']);
                header("Location: ../categories.php");
                exit();
            }
            if (isset($imgRes['path'])) {
                $imagePath = $imgRes['path'];
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, slug, description, image, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category_name, $slug, $description, $imagePath, $status]);
            set_flash_message('success', 'Category "' . htmlspecialchars($category_name) . '" created successfully.');
        } catch (PDOException $e) {
            if ($imagePath) remove_old_image($imagePath);
            set_flash_message('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'update':
        $id            = intval($_POST['id'] ?? 0);
        $category_name = trim($_POST['category_name'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $status        = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
        $custom_slug   = trim($_POST['slug'] ?? '');
        $remove_image  = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

        if ($id <= 0 || empty($category_name)) {
            set_flash_message('error', 'Valid category ID and name are required.');
            header("Location: ../categories.php");
            exit();
        }

        $slug = !empty($custom_slug) ? make_slug($custom_slug) : make_slug($category_name);

        // Check duplicate name or slug on different ID
        $stmt = $pdo->prepare("SELECT id, image FROM categories WHERE (category_name = ? OR slug = ?) AND id != ?");
        $stmt->execute([$category_name, $slug, $id]);
        if ($stmt->fetch()) {
            set_flash_message('error', 'Another category already exists with that name or slug.');
            header("Location: ../categories.php");
            exit();
        }

        // Fetch current category data
        $currStmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $currStmt->execute([$id]);
        $currentRecord = $currStmt->fetch();
        $imagePath = $currentRecord['image'] ?? null;

        // Process new image upload if provided
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imgRes = process_category_image($_FILES['category_image'], 600, 600);
            if (isset($imgRes['error'])) {
                set_flash_message('error', $imgRes['error']);
                header("Location: ../categories.php");
                exit();
            }
            if (isset($imgRes['path'])) {
                if (!empty($imagePath)) {
                    remove_old_image($imagePath);
                }
                $imagePath = $imgRes['path'];
            }
        } elseif ($remove_image) {
            if (!empty($imagePath)) {
                remove_old_image($imagePath);
            }
            $imagePath = null;
        }

        try {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, slug = ?, description = ?, image = ?, status = ? WHERE id = ?");
            $stmt->execute([$category_name, $slug, $description, $imagePath, $status, $id]);
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

        // Clean up image file
        $imgCheck = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $imgCheck->execute([$id]);
        $imgToDelete = $imgCheck->fetchColumn();
        if ($imgToDelete) {
            remove_old_image($imgToDelete);
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
