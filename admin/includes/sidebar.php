<?php
// admin/includes/sidebar.php - Dynamic, responsive, collapsible sidebar
if (!defined('CURRENT_PAGE')) {
    define('CURRENT_PAGE', basename($_SERVER['PHP_SELF'], '.php'));
}

// Active page indicator helper
function is_nav_active($page_names) {
    if (!is_array($page_names)) {
        $page_names = [$page_names];
    }
    return in_array(CURRENT_PAGE, $page_names);
}
?>
<!-- Mobile Overlay Backdrop -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden md:hidden transition-opacity duration-300 opacity-0" onclick="closeSidebarMobile()"></div>

<!-- Collapsible Sidebar -->
<aside id="admin-sidebar" class="fixed top-0 bottom-0 left-0 w-72 bg-white border-r border-gray-100 flex flex-col z-50 transition-all duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-lg md:shadow-sm">
    <!-- Sidebar Header / Brand -->
    <div class="h-20 flex items-center justify-between px-6 border-b border-gray-100/80 bg-white">
        <a href="dashboard.php" class="flex items-center gap-3 group overflow-hidden">
            <img src="../logo.png" alt="Technodome Logo" class="h-10 w-auto object-contain flex-shrink-0 transition-transform group-hover:scale-105">
            <div class="sidebar-brand-text flex flex-col transition-all duration-300 whitespace-nowrap">
                <span class="font-outfit font-extrabold text-xl text-gray-900 leading-tight">Admin <span class="text-brand">Panel</span></span>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Technodome</span>
            </div>
        </a>
        <!-- Mobile close button -->
        <button type="button" onclick="closeSidebarMobile()" class="md:hidden text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-50 focus:outline-none">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Quick User Status Badge -->
    <div class="sidebar-user-section px-5 py-4 border-b border-gray-100/70 bg-gradient-to-r from-purple-50/50 to-transparent">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand/10 text-brand flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="sidebar-brand-text flex-1 min-w-0 transition-all duration-300">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-bold text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></p>
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <p class="text-xs text-brand font-semibold capitalize">Super Admin</p>
            </div>
        </div>
    </div>

    <!-- Sidebar Navigation Links -->
    <div class="flex-1 overflow-y-auto px-4 py-5 space-y-1.5 custom-scrollbar">
        <div class="sidebar-brand-text px-3 pb-2 text-[11px] font-extrabold uppercase tracking-wider text-gray-400">
            Core Management
        </div>

        <!-- Dashboard -->
        <a href="dashboard.php" 
           title="Dashboard"
           class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm transition-all duration-200 <?php echo is_nav_active('dashboard') ? 'bg-brand text-white shadow-md shadow-brand/25' : 'text-gray-600 hover:text-brand hover:bg-purple-50/70'; ?>">
            <span class="w-6 text-center text-base flex-shrink-0"><i class="fa-solid fa-gauge-high"></i></span>
            <span class="sidebar-link-text truncate">Dashboard</span>
            <?php if (is_nav_active('dashboard')): ?>
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white flex-shrink-0"></span>
            <?php endif; ?>
        </a>

        <!-- Enquiry Records -->
        <a href="dashboard.php#enquiries" 
           title="Enquiry Records"
           class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm transition-all duration-200 <?php echo (isset($_GET['view']) && $_GET['view'] === 'enquiries') ? 'bg-brand text-white shadow-md shadow-brand/25' : 'text-gray-600 hover:text-brand hover:bg-purple-50/70'; ?>">
            <span class="w-6 text-center text-base flex-shrink-0"><i class="fa-solid fa-address-book"></i></span>
            <span class="sidebar-link-text truncate">Enquiry Records</span>
            <?php if (isset($totalLeads) && $totalLeads > 0): ?>
                <span class="sidebar-link-text ml-auto px-2 py-0.5 text-xs rounded-full font-extrabold <?php echo is_nav_active('dashboard') && (isset($_GET['view']) && $_GET['view'] === 'enquiries') ? 'bg-white/20 text-white' : 'bg-brand/10 text-brand'; ?>">
                    <?php echo $totalLeads; ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- Categories Management -->
        <a href="categories.php" 
           title="Categories Management"
           class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm transition-all duration-200 <?php echo is_nav_active('categories') ? 'bg-brand text-white shadow-md shadow-brand/25' : 'text-gray-600 hover:text-brand hover:bg-purple-50/70'; ?>">
            <span class="w-6 text-center text-base flex-shrink-0"><i class="fa-solid fa-layer-group"></i></span>
            <span class="sidebar-link-text truncate">Categories Management</span>
            <?php if (is_nav_active('categories')): ?>
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white flex-shrink-0"></span>
            <?php endif; ?>
        </a>

        <!-- Pricing Management -->
        <a href="pricing.php" 
           title="Pricing Management"
           class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm transition-all duration-200 <?php echo is_nav_active('pricing') ? 'bg-brand text-white shadow-md shadow-brand/25' : 'text-gray-600 hover:text-brand hover:bg-purple-50/70'; ?>">
            <span class="w-6 text-center text-base flex-shrink-0"><i class="fa-solid fa-tags"></i></span>
            <span class="sidebar-link-text truncate">Pricing Management</span>
            <?php if (is_nav_active('pricing')): ?>
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white flex-shrink-0"></span>
            <?php endif; ?>
        </a>

        <div class="sidebar-brand-text px-3 pt-4 pb-2 text-[11px] font-extrabold uppercase tracking-wider text-gray-400">
            System
        </div>

        <!-- Settings -->
        <a href="settings.php" 
           title="Settings"
           class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm transition-all duration-200 <?php echo is_nav_active('settings') ? 'bg-brand text-white shadow-md shadow-brand/25' : 'text-gray-600 hover:text-brand hover:bg-purple-50/70'; ?>">
            <span class="w-6 text-center text-base flex-shrink-0"><i class="fa-solid fa-sliders"></i></span>
            <span class="sidebar-link-text truncate">Settings</span>
            <?php if (is_nav_active('settings')): ?>
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white flex-shrink-0"></span>
            <?php endif; ?>
        </a>

        <!-- Front Website Link -->
        <a href="../index.html" target="_blank"
           title="View Website"
           class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm text-gray-500 hover:text-brand-blue hover:bg-blue-50/60 transition-all duration-200">
            <span class="w-6 text-center text-base flex-shrink-0"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
            <span class="sidebar-link-text truncate">View Website</span>
        </a>
    </div>

    <!-- Sidebar Footer / Logout & Collapse Toggle -->
    <div class="p-4 border-t border-gray-100 bg-white space-y-2">
        <a href="logout.php" 
           class="flex items-center gap-3.5 px-4 py-3 rounded-2xl font-bold text-sm text-red-600 bg-red-50/80 hover:bg-red-100 hover:text-red-700 transition-all">
            <span class="w-6 text-center text-base flex-shrink-0"><i class="fa-solid fa-right-from-bracket"></i></span>
            <span class="sidebar-link-text truncate">Logout</span>
        </a>

        <!-- Desktop Collapse / Expand Toggle Button -->
        <button type="button" 
                id="sidebar-toggle-btn" 
                onclick="toggleSidebarCollapse()" 
                class="hidden md:flex items-center justify-center w-full py-2 px-3 rounded-xl text-gray-400 hover:text-brand hover:bg-purple-50 transition-all text-xs font-bold gap-2">
            <i id="sidebar-toggle-icon" class="fa-solid fa-angles-left transition-transform duration-300"></i>
            <span class="sidebar-link-text">Collapse Menu</span>
        </button>
    </div>
</aside>
