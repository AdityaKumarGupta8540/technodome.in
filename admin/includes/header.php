<?php
// admin/includes/header.php - Admin Navigation Bar & HTML Top Boilerplate
if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard - Technodome Admin';
}
$csrf_token = get_csrf_token();
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 CDNs (Multi-CDN redundancy: cdnjs, jsDelivr, unpkg) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" />
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
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
                            dark: '#7b2cbf',
                            blue: '#3a86ff',
                            pink: '#ff006e',
                            emerald: '#10b981',
                            amber: '#f59e0b',
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
    <style>
        /* Smooth transitions */
        .sidebar-collapsed {
            width: 5rem !important;
        }
        .sidebar-collapsed .sidebar-brand-text,
        .sidebar-collapsed .sidebar-link-text,
        .sidebar-collapsed .sidebar-user-section {
            display: none !important;
        }
        .sidebar-collapsed #sidebar-toggle-icon {
            transform: rotate(180deg);
        }
        @media (min-width: 768px) {
            .main-content-expanded {
                margin-left: 5rem !important;
            }
        }
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #e2e8f0;
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: #cbd5e1;
        }
    </style>
</head>
<body class="bg-slate-50 font-inter text-slate-800 h-full antialiased flex flex-col">
    <!-- Include Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Wrapper with dynamic margin for sidebar -->
    <div id="main-wrapper" class="flex-1 flex flex-col md:ml-72 transition-all duration-300 min-h-screen">
        <!-- Top Sticky Header -->
        <header class="bg-white/80 backdrop-blur-xl border-b border-gray-100 sticky top-0 z-30 px-4 md:px-8 py-3.5 flex items-center justify-between shadow-xs">
            <!-- Left: Mobile Hamburger & Page Context -->
            <div class="flex items-center gap-3 md:gap-4">
                <!-- Mobile Hamburger Button -->
                <button type="button" onclick="openSidebarMobile()" class="md:hidden text-gray-500 hover:text-brand p-2.5 rounded-xl hover:bg-purple-50 transition-colors focus:outline-none" aria-label="Open sidebar">
                    <i class="fa-solid fa-bars-staggered text-lg"></i>
                </button>

                <div>
                    <h1 class="text-lg md:text-xl font-outfit font-extrabold text-gray-900 leading-tight">
                        <?php echo htmlspecialchars($pageHeading ?? 'Dashboard'); ?>
                    </h1>
                    <p class="hidden sm:block text-xs font-semibold text-gray-400">
                        <?php echo htmlspecialchars($pageSubheading ?? 'Technodome Course & Enquiry Management Portal'); ?>
                    </p>
                </div>
            </div>

            <!-- Right: Admin Status & Quick Actions -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2.5 text-xs font-bold text-gray-700 bg-gray-50/80 px-4 py-2 rounded-2xl border border-gray-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Admin: <strong class="text-brand"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></strong></span>
                </div>

                <a href="logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-2xl font-bold text-xs transition-all flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </header>

        <!-- Main Body Area -->
        <main class="flex-grow p-4 md:p-8 max-w-7xl w-full mx-auto">
            <!-- Flash Notification -->
            <?php if ($flash): ?>
                <?php
                    $alertStyles = [
                        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800 fa-circle-check text-emerald-600',
                        'error'   => 'bg-rose-50 border-rose-200 text-rose-800 fa-circle-xmark text-rose-600',
                        'warning' => 'bg-amber-50 border-amber-200 text-amber-800 fa-triangle-exclamation text-amber-600',
                        'info'    => 'bg-sky-50 border-sky-200 text-sky-800 fa-circle-info text-sky-600',
                    ];
                    $style = $alertStyles[$flash['type']] ?? $alertStyles['info'];
                    $icon = explode(' ', $style)[3] . ' ' . explode(' ', $style)[4];
                ?>
                <div class="mb-6 border p-4 rounded-3xl font-semibold flex items-center justify-between shadow-xs animate-fade-in <?php echo $style; ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?php echo $icon; ?> text-xl"></i>
                        <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600 ml-4">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>
