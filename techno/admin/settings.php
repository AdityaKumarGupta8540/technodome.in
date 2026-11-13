<?php
// admin/settings.php - Admin Settings Configuration
require_once '../db_config.php';
require_once 'auth.php';

require_login();

$pageTitle = 'Settings - Technodome Admin';
$pageHeading = 'Admin Settings';
$pageSubheading = 'Manage administrative credentials, system preferences, and security';

$admin_id = $_SESSION['admin_id'];

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('error', 'Security token mismatch.');
        header("Location: settings.php");
        exit();
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        set_flash_message('error', 'All password fields are required.');
    } elseif ($new_password !== $confirm_password) {
        set_flash_message('error', 'New password and confirmation do not match.');
    } elseif (strlen($new_password) < 6) {
        set_flash_message('error', 'New password must be at least 6 characters long.');
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $hash = $stmt->fetchColumn();

        if ($hash && password_verify($current_password, $hash)) {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update->execute([$new_hash, $admin_id]);
            set_flash_message('success', 'Admin password changed successfully!');
        } else {
            set_flash_message('error', 'Current password is incorrect.');
        }
    }

    header("Location: settings.php");
    exit();
}

require_once 'includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Admin Profile Card & System Info -->
    <div class="space-y-6">
        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm text-center">
            <div class="w-20 h-20 rounded-3xl bg-brand/10 text-brand flex items-center justify-center text-3xl mx-auto mb-4 font-bold">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <h3 class="text-xl font-outfit font-extrabold text-gray-900"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></h3>
            <span class="inline-block px-3 py-1 bg-purple-50 text-brand text-xs font-bold rounded-full mt-1">Super Administrator</span>
            <p class="text-xs text-gray-400 mt-3 font-medium">Session Active &bull; Fully Privileged Access</p>
        </div>

        <div class="bg-white p-6 rounded-[2.5rem] border border-gray-100 shadow-sm space-y-4">
            <h4 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">System Environment</h4>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500 font-medium">PHP Version</span>
                    <span class="font-bold text-gray-800"><?php echo phpversion(); ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500 font-medium">Database</span>
                    <span class="font-bold text-gray-800">MySQL (PDO)</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500 font-medium">Database Name</span>
                    <span class="font-bold text-brand">techno_db</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500 font-medium">Server Software</span>
                    <span class="font-bold text-gray-800"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP'); ?></span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-500 font-medium">CSRF Protection</span>
                    <span class="font-bold text-emerald-600 flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Enabled</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Settings Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm p-8">
            <div class="border-b border-gray-100 pb-6 mb-6">
                <h3 class="text-xl font-outfit font-extrabold text-gray-900">Security & Credentials</h3>
                <p class="text-xs font-medium text-gray-400 mt-1">Update your password to ensure secure administrative access</p>
            </div>

            <form action="settings.php" method="POST" class="space-y-5 max-w-lg">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="change_password">

                <div>
                    <label for="current_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                        Current Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="current_password" id="current_password" required 
                           class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-sm focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                           placeholder="Enter your current password">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                        New Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="new_password" id="new_password" required 
                           class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-sm focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                           placeholder="Minimum 6 characters">
                </div>

                <div>
                    <label for="confirm_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                           class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-sm focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                           placeholder="Re-type new password">
                </div>

                <div class="pt-4">
                    <button type="submit" 
                            class="px-6 py-3 rounded-2xl bg-brand hover:bg-brand-dark text-white font-bold text-xs shadow-md shadow-brand/25 transition-all">
                        <i class="fa-solid fa-key mr-1.5"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
