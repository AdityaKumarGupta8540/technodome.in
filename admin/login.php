<?php
// admin/login.php - Admin login for Technodome
require_once '../db_config.php';
require_once 'auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Technodome</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#9d4edd',
                            light: '#c77dff',
                            blue: '#3a86ff',
                            pink: '#ff006e',
                        }
                    },
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 font-inter min-h-screen flex items-center justify-center p-6">
    <!-- Background Decor -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-blue/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">
        <!-- Main Login Card -->
        <div class="bg-white/80 backdrop-blur-xl border border-white rounded-[2.5rem] shadow-2xl p-8 md:p-12">
            <div class="text-center mb-10">
                <a href="../index.html" class="inline-block mb-8">
                    <img src="../logo.png" alt="Technodome Logo" class="h-14 w-auto mx-auto object-contain">
                </a>
                <h2 class="text-3xl font-outfit font-extrabold text-gray-900 mb-2">Admin Panel</h2>
                <p class="text-gray-500 font-medium">Please enter your credentials to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-2xl text-sm font-semibold mb-6 flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-bold text-gray-700 mb-2 ml-1">Username</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="username" name="username" 
                            class="w-full bg-white border border-gray-100 pl-11 pr-4 py-4 rounded-2xl focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all placeholder:text-gray-400 font-medium"
                            placeholder="Entar your username" required>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-2 ml-1">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" id="password" name="password" 
                            class="w-full bg-white border border-gray-100 pl-11 pr-4 py-4 rounded-2xl focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all placeholder:text-gray-400 font-medium"
                            placeholder="••••••••" required>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-brand to-brand-blue text-white font-extrabold py-4 rounded-2xl shadow-xl shadow-brand/20 hover:shadow-brand/40 hover:-translate-y-1 transition-all flex items-center justify-center gap-3 group">
                        Sign In 
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center pt-8 border-t border-gray-50">
                <a href="../index.html" class="text-sm font-bold text-brand hover:text-brand-blue transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Website
                </a>
            </div>
        </div>
        
        <p class="text-center mt-8 text-gray-400 text-sm font-medium">
            &copy; 2026 Technodome. All rights reserved.
        </p>
    </div>
</body>
</html>
