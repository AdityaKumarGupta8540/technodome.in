<?php
// admin/pricing.php - Course Pricing Plans Management
require_once '../db_config.php';
require_once 'auth.php';

require_login();

// Fetch all categories for dropdown and filtering
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$allCategories = $cat_stmt->fetchAll();

// Search and filter handling
$search = trim($_GET['search'] ?? '');
$category_filter = intval($_GET['category_id'] ?? 0);
$status_filter = trim($_GET['status'] ?? '');
$sort_by = trim($_GET['sort'] ?? 'newest');

$query = "
    SELECT p.*, c.category_name, c.slug as category_slug 
    FROM pricing_plans p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.plan_name LIKE ? OR p.course_duration LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter) && in_array($status_filter, ['Active', 'Inactive'])) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
}

switch ($sort_by) {
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.plan_name ASC";
        break;
    case 'oldest':
        $query .= " ORDER BY p.created_at ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$plans = $stmt->fetchAll();

// Statistics
$totalPlans = count($plans);
$activePlans = 0;
$avgPrice = 0;
$totalPriceSum = 0;
foreach ($plans as $p) {
    if ($p['status'] === 'Active') $activePlans++;
    $totalPriceSum += $p['price'];
}
if ($totalPlans > 0) {
    $avgPrice = round($totalPriceSum / $totalPlans);
}

$pageTitle = 'Pricing Management - Technodome Admin';
$pageHeading = 'Pricing Management';
$pageSubheading = 'Configure course tiers, discount pricing, and duration packages';

require_once 'includes/header.php';
?>

<!-- Statistics Overview -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-brand/10 rounded-2xl flex items-center justify-center text-brand text-xl">
                <i class="fa-solid fa-tags"></i>
            </div>
            <span class="text-[11px] font-extrabold text-brand bg-brand/5 uppercase tracking-wider px-3 py-1 rounded-full">All Plans</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Pricing Plans</h4>
        <div class="text-3xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $totalPlans; ?></div>
    </div>

    <div class="bg-white p-6 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <span class="text-[11px] font-extrabold text-emerald-600 bg-emerald-50 uppercase tracking-wider px-3 py-1 rounded-full">Active</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Plans</h4>
        <div class="text-3xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $activePlans; ?></div>
    </div>

    <div class="bg-white p-6 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-brand-blue/10 rounded-2xl flex items-center justify-center text-brand-blue text-xl">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <span class="text-[11px] font-extrabold text-brand-blue bg-blue-50 uppercase tracking-wider px-3 py-1 rounded-full">Average</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Average Plan Fee</h4>
        <div class="text-3xl font-outfit font-extrabold text-gray-900 mt-1">₹<?php echo number_format($avgPrice, 0); ?></div>
    </div>
</div>

<!-- Main Table Container -->
<div class="bg-white rounded-[2.5rem] border border-gray-100/80 shadow-sm overflow-hidden">
    <!-- Header & Filter Bar -->
    <div class="p-6 md:p-8 border-b border-gray-100 flex flex-col space-y-4">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h2 class="text-xl font-outfit font-extrabold text-gray-900">Course Pricing Plans</h2>
                <p class="text-gray-400 font-medium text-xs mt-0.5">Manage fees, special discounts, and syllabus timeline durations</p>
            </div>

            <button type="button" onclick="openAddPlanModal()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl bg-brand hover:bg-brand-dark text-white font-bold text-xs shadow-md shadow-brand/25 transition-all">
                <i class="fa-solid fa-plus"></i>
                <span>Create Pricing Plan</span>
            </button>
        </div>

        <!-- Filters Form -->
        <form method="GET" action="pricing.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-2">
            <!-- Search -->
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search plans or keywords..." 
                       class="w-full pl-9 pr-3 py-2.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs font-medium focus:ring-2 focus:ring-brand/20 focus:border-brand outline-none transition-all">
            </div>

            <!-- Category Filter -->
            <div>
                <select name="category_id" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs font-semibold focus:ring-2 focus:ring-brand/20 focus:border-brand outline-none transition-all">
                    <option value="">All Categories</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs font-semibold focus:ring-2 focus:ring-brand/20 focus:border-brand outline-none transition-all">
                    <option value="">All Statuses</option>
                    <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active Plans Only</option>
                    <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive Plans Only</option>
                </select>
            </div>

            <!-- Sort By -->
            <div class="flex items-center gap-2">
                <select name="sort" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs font-semibold focus:ring-2 focus:ring-brand/20 focus:border-brand outline-none transition-all">
                    <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Sort: Newest First</option>
                    <option value="price_asc" <?php echo $sort_by === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort_by === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Sort: Oldest First</option>
                </select>

                <?php if (!empty($search) || !empty($status_filter) || $category_filter > 0 || $sort_by !== 'newest'): ?>
                    <a href="pricing.php" class="p-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-500 text-xs shrink-0" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Plans Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gradient-to-r from-slate-50 via-purple-50/20 to-slate-50 border-b border-gray-100/90">
                    <th class="px-5 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest text-center w-14">Sl. No.</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest">Plan & Category</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest">Duration</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest">Pricing</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest text-center">Status</th>
                    <th class="px-6 py-4 text-center text-[11px] font-extrabold text-slate-400 uppercase tracking-widest w-28">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100/70 bg-white">
                <?php if (count($plans) > 0): ?>
                    <?php 
                        $sl_no = 1;
                        foreach ($plans as $plan): 
                    ?>
                        <?php 
                            $hasDiscount = !empty($plan['discount_price']) && $plan['discount_price'] > 0 && $plan['discount_price'] < $plan['price'];
                            $savingPercent = $hasDiscount ? round((($plan['price'] - $plan['discount_price']) / $plan['price']) * 100) : 0;
                        ?>
                        <tr class="hover:bg-purple-50/30 transition-all duration-200 group">
                            <!-- Sl. No. -->
                            <td class="px-5 py-5 text-center align-middle">
                                <span class="w-8 h-8 rounded-xl bg-slate-50 group-hover:bg-white text-slate-600 group-hover:text-brand font-outfit font-extrabold text-xs inline-flex items-center justify-center border border-slate-200/60 shadow-xs transition-all">
                                    <?php echo sprintf('%02d', $sl_no++); ?>
                                </span>
                            </td>

                            <!-- Plan Name & Category -->
                            <td class="px-6 py-5">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-10 h-10 rounded-2xl bg-purple-50 text-brand flex items-center justify-center font-bold text-base shrink-0 mt-0.5 border border-purple-100/80">
                                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-gray-900 text-sm"><?php echo htmlspecialchars($plan['plan_name']); ?></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-brand bg-brand/5 px-2.5 py-0.5 rounded-lg border border-brand/10">
                                                <svg class="w-3 h-3 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                </svg>
                                                <?php echo htmlspecialchars($plan['category_name'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($plan['description'])): ?>
                                            <p class="text-xs text-gray-400 font-medium max-w-sm line-clamp-1 mt-1">
                                                <?php echo htmlspecialchars($plan['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Duration -->
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?php echo htmlspecialchars($plan['course_duration']); ?>
                                </span>
                            </td>

                            <!-- Price & Discount -->
                            <td class="px-6 py-5">
                                <div>
                                    <?php if ($hasDiscount): ?>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-base font-outfit font-extrabold text-emerald-600">
                                                ₹<?php echo number_format($plan['discount_price'], 2); ?>
                                            </span>
                                            <span class="text-xs font-semibold text-gray-400 line-through">
                                                ₹<?php echo number_format($plan['price'], 2); ?>
                                            </span>
                                        </div>
                                        <span class="inline-block text-[10px] font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md mt-0.5">
                                            Save <?php echo $savingPercent; ?>%
                                        </span>
                                    <?php else: ?>
                                        <div class="text-base font-outfit font-extrabold text-gray-900">
                                            ₹<?php echo number_format($plan['price'], 2); ?>
                                        </div>
                                        <span class="text-[10px] font-semibold text-gray-400">Regular Price</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Status Toggle -->
                            <td class="px-6 py-5 text-center">
                                <form action="actions/pricing_actions.php" method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                                    <button type="submit" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-extrabold cursor-pointer transition-transform hover:scale-105 <?php echo $plan['status'] === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500 border border-gray-200'; ?>"
                                            title="Click to toggle status">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $plan['status'] === 'Active' ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'; ?>"></span>
                                        <?php echo htmlspecialchars($plan['status']); ?>
                                    </button>
                                </form>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Edit Button -->
                                    <button type="button" 
                                            onclick='openEditPlanModal(<?php echo json_encode($plan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                            class="w-8 h-8 rounded-xl bg-blue-50/80 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 transition-all flex items-center justify-center text-xs shadow-2xs" 
                                            title="Edit Pricing Plan">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" 
                                            onclick="confirmDeletePlan(<?php echo $plan['id']; ?>, '<?php echo addslashes(htmlspecialchars($plan['plan_name'])); ?>')"
                                            class="w-8 h-8 rounded-xl bg-rose-50/80 text-rose-500 hover:bg-rose-600 hover:text-white border border-rose-100 transition-all flex items-center justify-center text-xs shadow-2xs" 
                                            title="Delete Pricing Plan">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-8 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3 opacity-40">
                                <i class="fa-solid fa-tags text-6xl text-gray-400"></i>
                                <h3 class="font-outfit font-extrabold text-lg text-gray-700">No Pricing Plans Found</h3>
                                <p class="text-xs text-gray-500 max-w-sm">No plans match your current criteria. Create pricing tiers for students to enroll in.</p>
                                <button type="button" onclick="openAddPlanModal()" class="mt-2 px-4 py-2 bg-brand text-white rounded-xl text-xs font-bold">
                                    <i class="fa-solid fa-plus mr-1"></i> Create Pricing Plan
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add / Edit Pricing Plan -->
<div id="planModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 w-full max-w-xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] flex flex-col" id="planModalCard">
        <!-- Modal Header -->
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-50/40 to-transparent flex-shrink-0">
            <div>
                <h3 id="planModalTitle" class="text-xl font-outfit font-extrabold text-gray-900">Create Pricing Plan</h3>
                <p class="text-xs font-medium text-gray-400 mt-0.5">Specify package name, associated category, and fees</p>
            </div>
            <button type="button" onclick="closePlanModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-2xl hover:bg-gray-100">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="actions/pricing_actions.php" method="POST" class="p-8 space-y-4 overflow-y-auto custom-scrollbar flex-1">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="planFormAction" value="create">
            <input type="hidden" name="id" id="planId" value="">

            <div>
                <label for="plan_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    Plan Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="plan_name" id="plan_name" required 
                       class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-sm focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                       placeholder="e.g., Full Stack Masterclass (Pro)">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="plan_category_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                        Course Category <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" id="plan_category_id" required 
                            class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-xs focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="course_duration" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                        Course Duration <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="course_duration" id="course_duration" required 
                           class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-xs focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                           placeholder="e.g., 3 Months / 60 Hours">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="price" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                        Original Price (₹) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-xs">₹</span>
                        <input type="number" step="0.01" min="0" name="price" id="price" required 
                               class="w-full pl-8 pr-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-bold text-sm focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                               placeholder="19999.00">
                    </div>
                </div>

                <div>
                    <label for="discount_price" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                        Discounted Price (₹) <span class="text-gray-400 font-normal lowercase">(optional)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-xs">₹</span>
                        <input type="number" step="0.01" min="0" name="discount_price" id="discount_price"
                               class="w-full pl-8 pr-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-bold text-sm focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                               placeholder="14999.00">
                    </div>
                </div>
            </div>

            <div>
                <label for="plan_description" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    Features / Plan Description
                </label>
                <textarea name="description" id="plan_description" rows="3"
                          class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-medium text-xs focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all placeholder:text-gray-400"
                          placeholder="Include syllabus highlights, certifications, project mentoring or perks..."></textarea>
            </div>

            <div>
                <label for="plan_status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    Status
                </label>
                <select name="status" id="plan_status" 
                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-xs focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all">
                    <option value="Active">Active (Accepting enrollments)</option>
                    <option value="Inactive">Inactive (Hidden from plans)</option>
                </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                <button type="button" onclick="closePlanModal()" 
                        class="px-5 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition-colors">
                    Cancel
                </button>
                <button type="submit" id="planSubmitBtn"
                        class="px-6 py-2.5 rounded-2xl bg-brand hover:bg-brand-dark text-white font-bold text-xs shadow-md shadow-brand/25 transition-all">
                    Save Pricing Plan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Plan Confirmation -->
<div id="deletePlanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 w-full max-w-md p-8 text-center transform scale-95 transition-transform duration-300" id="deletePlanModalCard">
        <div class="w-16 h-16 rounded-3xl bg-red-100 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 class="text-xl font-outfit font-extrabold text-gray-900 mb-2">Delete Pricing Plan?</h3>
        <p id="deletePlanModalMessage" class="text-xs font-medium text-gray-500 mb-6">
            Are you sure you want to permanently delete this pricing plan?
        </p>

        <form action="actions/pricing_actions.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deletePlanId" value="">

            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeletePlanModal()" 
                        class="px-5 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition-colors">
                    No, Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-2xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-500/25 transition-all">
                    Yes, Delete Plan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddPlanModal() {
        document.getElementById('planModalTitle').innerText = 'Create Pricing Plan';
        document.getElementById('planFormAction').value = 'create';
        document.getElementById('planId').value = '';
        document.getElementById('plan_name').value = '';
        document.getElementById('plan_category_id').value = '';
        document.getElementById('course_duration').value = '';
        document.getElementById('price').value = '';
        document.getElementById('discount_price').value = '';
        document.getElementById('plan_description').value = '';
        document.getElementById('plan_status').value = 'Active';
        document.getElementById('planSubmitBtn').innerText = 'Save Pricing Plan';

        const modal = document.getElementById('planModal');
        const card = document.getElementById('planModalCard');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function openEditPlanModal(plan) {
        document.getElementById('planModalTitle').innerText = 'Edit Pricing Plan';
        document.getElementById('planFormAction').value = 'update';
        document.getElementById('planId').value = plan.id;
        document.getElementById('plan_name').value = plan.plan_name;
        document.getElementById('plan_category_id').value = plan.category_id;
        document.getElementById('course_duration').value = plan.course_duration;
        document.getElementById('price').value = plan.price;
        document.getElementById('discount_price').value = plan.discount_price || '';
        document.getElementById('plan_description').value = plan.description || '';
        document.getElementById('plan_status').value = plan.status;
        document.getElementById('planSubmitBtn').innerText = 'Save Changes';

        const modal = document.getElementById('planModal');
        const card = document.getElementById('planModalCard');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function closePlanModal() {
        const modal = document.getElementById('planModal');
        const card = document.getElementById('planModalCard');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function confirmDeletePlan(id, name) {
        const modal = document.getElementById('deletePlanModal');
        const card = document.getElementById('deletePlanModalCard');
        document.getElementById('deletePlanId').value = id;
        document.getElementById('deletePlanModalMessage').innerHTML = `Are you sure you want to permanently delete plan <strong>"${name}"</strong>?`;

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function closeDeletePlanModal() {
        const modal = document.getElementById('deletePlanModal');
        const card = document.getElementById('deletePlanModalCard');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
</script>

<?php require_once 'includes/footer.php'; ?>
