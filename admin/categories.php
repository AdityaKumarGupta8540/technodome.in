<?php
// admin/categories.php - Course Categories Management
require_once '../db_config.php';
require_once 'auth.php';

require_login();

// Search and filter handling
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$query = "
    SELECT c.*, COUNT(p.id) AS plans_count 
    FROM categories c 
    LEFT JOIN pricing_plans p ON c.id = p.category_id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.category_name LIKE ? OR c.slug LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter) && in_array($status_filter, ['Active', 'Inactive'])) {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Statistics for Category Overview
$totalCategories = count($categories);
$activeCategories = 0;
$totalAssociatedPlans = 0;
foreach ($categories as $cat) {
    if ($cat['status'] === 'Active') $activeCategories++;
    $totalAssociatedPlans += $cat['plans_count'];
}

$pageTitle = 'Categories Management - Technodome Admin';
$pageHeading = 'Categories Management';
$pageSubheading = 'Configure and organize course clusters and curriculum paths';

require_once 'includes/header.php';
?>

<!-- Hidden Row Upload Form for Direct Inline Row Image Updates -->
<form id="rowImageUploadForm" action="actions/category_actions.php" method="POST" enctype="multipart/form-data" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="upload_row_image">
    <input type="hidden" name="id" id="rowUploadCatId" value="">
    <input type="file" name="row_image" id="rowImageFileInput" accept="image/jpeg,image/png,image/webp,image/gif" onchange="submitRowImageUpload()">
</form>

<!-- Statistics Summary -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-brand/10 rounded-2xl flex items-center justify-center text-brand text-xl">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <span class="text-[11px] font-extrabold text-brand bg-brand/5 uppercase tracking-wider px-3 py-1 rounded-full">All Categories</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Categories</h4>
        <div class="text-3xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $totalCategories; ?></div>
    </div>

    <div class="bg-white p-6 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <span class="text-[11px] font-extrabold text-emerald-600 bg-emerald-50 uppercase tracking-wider px-3 py-1 rounded-full">Published</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Categories</h4>
        <div class="text-3xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $activeCategories; ?></div>
    </div>

    <div class="bg-white p-6 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-brand-blue/10 rounded-2xl flex items-center justify-center text-brand-blue text-xl">
                <i class="fa-solid fa-tags"></i>
            </div>
            <span class="text-[11px] font-extrabold text-brand-blue bg-blue-50 uppercase tracking-wider px-3 py-1 rounded-full">Connected</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Associated Pricing Plans</h4>
        <div class="text-3xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $totalAssociatedPlans; ?></div>
    </div>
</div>

<!-- Main Table Container -->
<div class="bg-white rounded-[2.5rem] border border-gray-100/80 shadow-sm overflow-hidden">
    <!-- Header Actions -->
    <div class="px-8 py-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4">
        <div>
            <h2 class="text-xl font-outfit font-extrabold text-gray-900">Course Categories</h2>
            <p class="text-gray-400 font-medium text-xs mt-0.5">Manage domain classifications, 600x600 preview images, and pricing packages</p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Search Form -->
            <form method="GET" action="categories.php" class="relative flex-1 sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search category..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs font-medium focus:ring-2 focus:ring-brand/20 focus:border-brand outline-none transition-all">
                <?php if (!empty($search)): ?>
                    <a href="categories.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Filter Status -->
            <div class="flex items-center gap-2">
                <a href="categories.php" class="px-3 py-2 rounded-2xl text-xs font-bold <?php echo empty($status_filter) ? 'bg-purple-100 text-brand' : 'bg-gray-50 text-gray-500 hover:bg-gray-100'; ?>">
                    All
                </a>
                <a href="categories.php?status=Active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-2 rounded-2xl text-xs font-bold <?php echo $status_filter === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-50 text-gray-500 hover:bg-gray-100'; ?>">
                    Active
                </a>
                <a href="categories.php?status=Inactive<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-2 rounded-2xl text-xs font-bold <?php echo $status_filter === 'Inactive' ? 'bg-rose-100 text-rose-700' : 'bg-gray-50 text-gray-500 hover:bg-gray-100'; ?>">
                    Inactive
                </a>
            </div>

            <!-- Add Category Button -->
            <button type="button" onclick="openAddCategoryModal()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl bg-brand hover:bg-brand-dark text-white font-bold text-xs shadow-md shadow-brand/25 transition-all">
                <i class="fa-solid fa-plus"></i>
                <span>Add Category</span>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gradient-to-r from-slate-50 via-purple-50/20 to-slate-50 border-b border-gray-100/90">
                    <th class="px-5 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest text-center w-14">Sl. No.</th>
                    <th class="px-4 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest text-center w-24">Image</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest min-w-[300px]">Category Name</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">Packages</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest">Slug / Identifier</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest text-center">Status</th>
                    <th class="px-6 py-4 text-[11px] font-extrabold text-slate-400 uppercase tracking-widest text-center w-32">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100/70 bg-white">
                <?php if (count($categories) > 0): ?>
                    <?php 
                        $sl_no = 1;
                        foreach ($categories as $cat): 
                            // Split description into bullet features if any
                            $raw_desc = trim($cat['description'] ?? '');
                            $features = [];
                            if (!empty($raw_desc)) {
                                $lines = preg_split('/\r\n|\r|\n/', $raw_desc);
                                foreach ($lines as $line) {
                                    $line = trim($line);
                                    if (!empty($line)) {
                                        $features[] = $line;
                                    }
                                }
                            }
                            $featureCount = count($features);
                            $firstFeature = $featureCount > 0 ? $features[0] : null;
                            $remainingFeatures = $featureCount > 1 ? array_slice($features, 1) : [];

                            $catImage = !empty($cat['image']) ? '../' . ltrim($cat['image'], '/') : '';
                            $hasCustomImage = !empty($cat['image']) && file_exists(__DIR__ . '/../' . ltrim($cat['image'], '/'));
                    ?>
                        <tr class="hover:bg-purple-50/30 transition-all duration-200 group">
                            <!-- Sl. No. -->
                            <td class="px-5 py-5 text-center align-top">
                                <span class="w-8 h-8 rounded-xl bg-slate-50 group-hover:bg-white text-slate-600 group-hover:text-brand font-outfit font-extrabold text-xs inline-flex items-center justify-center border border-slate-200/60 shadow-xs transition-all">
                                    <?php echo sprintf('%02d', $sl_no++); ?>
                                </span>
                            </td>

                            <!-- Image with Row-Level Quick Upload Option -->
                            <td class="px-4 py-5 text-center align-top">
                                <div class="relative w-14 h-14 mx-auto group/img">
                                    <?php if ($hasCustomImage): ?>
                                        <img src="<?php echo htmlspecialchars($catImage); ?>?v=<?php echo time(); ?>" 
                                             alt="<?php echo htmlspecialchars($cat['category_name']); ?>"
                                             id="cat-thumb-<?php echo $cat['id']; ?>"
                                             class="w-14 h-14 rounded-2xl object-cover shadow-xs border border-purple-100/80 transition-transform group-hover/img:scale-105">
                                    <?php else: ?>
                                        <div id="cat-thumb-<?php echo $cat['id']; ?>" class="w-14 h-14 rounded-2xl bg-purple-50 group-hover:bg-purple-100/70 text-brand flex items-center justify-center mx-auto shadow-xs border border-purple-100/80 transition-transform group-hover/img:scale-105">
                                            <svg class="w-6 h-6 text-brand" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-1.5V9a3 3 0 0 0-3-3h-3.379a3 3 0 0 1-2.121-.879L8.379 3.999A3 3 0 0 0 6.258 3.12H4.5A3 3 0 0 0 1.5 6.12v11.88a3 3 0 0 0 3 3h15z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Quick Upload Overlay Button -->
                                    <button type="button" 
                                            onclick="triggerRowImageUpload(<?php echo $cat['id']; ?>)"
                                            title="Upload 600x600 Image"
                                            class="absolute inset-0 bg-slate-900/60 rounded-2xl opacity-0 group-hover/img:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[10px] font-bold cursor-pointer backdrop-blur-[2px]">
                                        <i class="fa-solid fa-camera text-xs mb-0.5"></i>
                                        <span>Change</span>
                                    </button>
                                </div>
                                <span class="block text-[10px] text-gray-400 font-semibold mt-1">600×600</span>
                            </td>

                            <!-- Category Name & Features with 'More' Option -->
                            <td class="px-6 py-5 align-top">
                                <div class="space-y-2 max-w-md">
                                    <h3 class="font-outfit font-extrabold text-slate-900 text-sm leading-snug tracking-tight">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </h3>

                                    <?php if ($featureCount > 0): ?>
                                        <!-- Primary Feature -->
                                        <div class="flex items-center gap-2 text-xs font-medium text-slate-700">
                                            <span class="w-4 h-4 rounded-full bg-brand/10 text-brand flex items-center justify-center text-[9px] shrink-0 font-bold">
                                                <svg class="w-2.5 h-2.5 text-brand" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="truncate"><?php echo htmlspecialchars($firstFeature); ?></span>
                                        </div>

                                        <!-- Collapsible Remaining Features -->
                                        <?php if (!empty($remainingFeatures)): ?>
                                            <div id="extra-features-cat-<?php echo $cat['id']; ?>" class="hidden space-y-1.5 pt-1 transition-all">
                                                <?php foreach ($remainingFeatures as $feat): ?>
                                                    <div class="flex items-center gap-2 text-xs font-medium text-slate-600 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">
                                                        <span class="w-3.5 h-3.5 rounded-full bg-brand/10 text-brand flex items-center justify-center text-[8px] shrink-0 font-bold">
                                                            <svg class="w-2 h-2 text-brand" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                        <span><?php echo htmlspecialchars($feat); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- More / Less Toggle Button -->
                                            <button type="button" 
                                                    onclick="toggleMoreFeatures(<?php echo $cat['id']; ?>)" 
                                                    id="more-btn-<?php echo $cat['id']; ?>"
                                                    class="inline-flex items-center gap-1.5 text-[11px] font-bold text-brand hover:text-brand-dark bg-purple-50/70 hover:bg-purple-100 px-2.5 py-1 rounded-lg transition-colors cursor-pointer">
                                                <svg id="more-icon-<?php echo $cat['id']; ?>" class="w-3 h-3 text-brand transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                </svg>
                                                <span id="more-text-<?php echo $cat['id']; ?>">+<?php echo count($remainingFeatures); ?> more features</span>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-xs text-slate-400 italic">No features specified</p>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Packages (Associated Plans) -->
                            <td class="px-6 py-5 align-top text-center">
                                <a href="pricing.php?category_id=<?php echo $cat['id']; ?>" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-50 text-brand hover:bg-brand hover:text-white border border-purple-100 shadow-2xs hover:shadow-md hover:shadow-brand/20 transition-all duration-200 group/btn">
                                    <svg class="w-3.5 h-3.5 text-brand group-hover/btn:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span><?php echo $cat['plans_count']; ?> Packages</span>
                                </a>
                            </td>

                            <!-- Slug / Identifier -->
                            <td class="px-6 py-5 align-top">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-mono font-bold bg-slate-100/90 text-slate-700 border border-slate-200/80 shadow-2xs">
                                    <span class="text-slate-400 font-bold">#</span>
                                    <?php echo htmlspecialchars($cat['slug']); ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-5 align-top text-center">
                                <form action="actions/category_actions.php" method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                    <button type="submit" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-extrabold cursor-pointer transition-all duration-200 hover:shadow-xs hover:scale-105 <?php echo $cat['status'] === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'; ?>"
                                            title="Click to toggle status">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $cat['status'] === 'Active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'; ?>"></span>
                                        <span><?php echo htmlspecialchars($cat['status']); ?></span>
                                    </button>
                                </form>
                            </td>

                            <!-- Action -->
                            <td class="px-6 py-5 align-top text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Direct Row Image Upload Button -->
                                    <button type="button" 
                                            onclick="triggerRowImageUpload(<?php echo $cat['id']; ?>)"
                                            class="w-8 h-8 rounded-xl bg-purple-50 text-brand hover:bg-brand hover:text-white border border-purple-100 transition-all duration-200 flex items-center justify-center text-xs shadow-2xs" 
                                            title="Upload 600x600 Image">
                                        <i class="fa-solid fa-arrow-up-from-bracket text-[11px]"></i>
                                    </button>

                                    <!-- Edit Button -->
                                    <button type="button" 
                                            onclick='openEditCategoryModal(<?php echo json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                            class="w-8 h-8 rounded-xl bg-blue-50/80 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 transition-all duration-200 flex items-center justify-center text-xs shadow-2xs" 
                                            title="Edit Category">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" 
                                            onclick="confirmDeleteCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes(htmlspecialchars($cat['category_name'])); ?>', <?php echo $cat['plans_count']; ?>)"
                                            class="w-8 h-8 rounded-xl bg-rose-50/80 text-rose-500 hover:bg-rose-600 hover:text-white border border-rose-100 transition-all duration-200 flex items-center justify-center text-xs shadow-2xs" 
                                            title="Delete Category">
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
                        <td colspan="7" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center justify-center gap-3 opacity-40">
                                <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center text-slate-400 text-2xl">
                                    <i class="fa-solid fa-folder-open"></i>
                                </div>
                                <h3 class="font-outfit font-extrabold text-lg text-gray-700">No Categories Found</h3>
                                <p class="text-xs text-gray-500 max-w-sm">No course categories match your search or filter criteria. Create your first category to get started.</p>
                                <button type="button" onclick="openAddCategoryModal()" class="mt-3 px-5 py-2.5 bg-brand hover:bg-brand-dark text-white rounded-2xl text-xs font-bold shadow-md shadow-brand/20 transition-all">
                                    <i class="fa-solid fa-plus mr-1.5"></i> Add Category
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add / Edit Category -->
<div id="categoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto" id="categoryModalCard">
        <!-- Modal Header -->
        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-50/40 to-transparent sticky top-0 bg-white/95 backdrop-blur-sm z-10">
            <div>
                <h3 id="categoryModalTitle" class="text-xl font-outfit font-extrabold text-gray-900">Add Course Category</h3>
                <p class="text-xs font-medium text-gray-400 mt-0.5">Enter category details and upload course preview image</p>
            </div>
            <button type="button" onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-2xl hover:bg-gray-100">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="actions/category_actions.php" method="POST" enctype="multipart/form-data" class="p-8 space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" id="categoryFormAction" value="create">
            <input type="hidden" name="id" id="categoryId" value="">
            <input type="hidden" name="remove_image" id="categoryRemoveImage" value="0">

            <div>
                <label for="category_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="category_name" id="category_name" required 
                       class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-sm focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                       placeholder="e.g., Full Stack Development" oninput="autoFillSlug(this.value)">
            </div>

            <div>
                <label for="category_slug" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    URL Slug <span class="text-gray-400 font-normal lowercase">(optional, auto-generated)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-mono font-bold">/</span>
                    <input type="text" name="slug" id="category_slug"
                           class="w-full pl-8 pr-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-mono text-xs focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all"
                           placeholder="full-stack-development">
                </div>
            </div>

            <!-- Category Image Upload (600x600) -->
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    Course Card Image <span class="text-brand font-semibold lowercase">(600×600 px auto-scaled)</span>
                </label>
                
                <div class="flex items-center gap-4 p-4 rounded-2xl bg-purple-50/40 border border-purple-100/70">
                    <!-- Preview Box -->
                    <div class="relative w-20 h-20 shrink-0 rounded-2xl overflow-hidden bg-white border border-gray-200 shadow-2xs flex items-center justify-center">
                        <img id="categoryImagePreview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                        <div id="categoryImagePlaceholder" class="flex flex-col items-center justify-center text-gray-400">
                            <i class="fa-solid fa-image text-xl mb-1 text-purple-300"></i>
                            <span class="text-[9px] font-bold uppercase">No Image</span>
                        </div>
                    </div>

                    <!-- Input Controls -->
                    <div class="flex-1 space-y-2">
                        <input type="file" name="category_image" id="category_image" accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewModalImage(this)"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand file:text-white hover:file:bg-brand-dark file:cursor-pointer transition-all">
                        <p class="text-[11px] text-gray-400">Recommended size: 600×600 px. Formats: JPG, PNG, WEBP.</p>
                        <button type="button" id="removeImageBtn" onclick="removeSelectedImage()" class="hidden text-[11px] font-bold text-rose-500 hover:text-rose-700 transition-colors">
                            <i class="fa-solid fa-trash-can mr-1"></i> Remove Image
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label for="category_description" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    Description & Key Highlights
                </label>
                <textarea name="description" id="category_description" rows="3"
                          class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-medium text-xs focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all placeholder:text-gray-400"
                          placeholder="Summarize key features (one per line for bullet points)..."></textarea>
            </div>

            <div>
                <label for="category_status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 ml-1">
                    Status
                </label>
                <select name="status" id="category_status" 
                        class="w-full px-4 py-3 rounded-2xl bg-gray-50 border border-gray-100 font-semibold text-xs focus:ring-4 focus:ring-brand/10 focus:border-brand outline-none transition-all">
                    <option value="Active">Active (Visible in course pricing forms & website)</option>
                    <option value="Inactive">Inactive (Hidden from public options)</option>
                </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                <button type="button" onclick="closeCategoryModal()" 
                        class="px-5 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition-colors">
                    Cancel
                </button>
                <button type="submit" id="categorySubmitBtn"
                        class="px-6 py-2.5 rounded-2xl bg-brand hover:bg-brand-dark text-white font-bold text-xs shadow-md shadow-brand/25 transition-all">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Confirmation -->
<div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 w-full max-w-md p-8 text-center transform scale-95 transition-transform duration-300" id="deleteModalCard">
        <div class="w-16 h-16 rounded-3xl bg-red-100 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="text-xl font-outfit font-extrabold text-gray-900 mb-2">Delete Category?</h3>
        <p id="deleteModalMessage" class="text-xs font-medium text-gray-500 mb-6">
            Are you sure you want to delete this category? This action cannot be reversed.
        </p>

        <form action="actions/category_actions.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteCategoryId" value="">

            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-5 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition-colors">
                    No, Cancel
                </button>
                <button type="submit" id="confirmDeleteSubmitBtn"
                        class="px-6 py-2.5 rounded-2xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-500/25 transition-all">
                    Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let isEditing = false;

    function autoFillSlug(text) {
        if (!isEditing) {
            const slug = text.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('category_slug').value = slug;
        }
    }

    // Direct Row Image Upload trigger
    function triggerRowImageUpload(catId) {
        document.getElementById('rowUploadCatId').value = catId;
        const fileInput = document.getElementById('rowImageFileInput');
        fileInput.value = '';
        fileInput.click();
    }

    // Submit row upload via AJAX with fallback
    function submitRowImageUpload() {
        const form = document.getElementById('rowImageUploadForm');
        const fileInput = document.getElementById('rowImageFileInput');
        const catId = document.getElementById('rowUploadCatId').value;

        if (!fileInput.files || fileInput.files.length === 0) return;

        const formData = new FormData(form);

        // Visual indicator on the row thumb
        const thumb = document.getElementById('cat-thumb-' + catId);
        if (thumb) {
            thumb.classList.add('opacity-40', 'animate-pulse');
        }

        fetch('actions/category_actions.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update image thumbnail in table row immediately
                if (thumb) {
                    const newSrc = '../' + data.image.replace(/^\/+/, '') + '?v=' + new Date().getTime();
                    if (thumb.tagName.toLowerCase() === 'img') {
                        thumb.src = newSrc;
                    } else {
                        // Replace placeholder div with an img element
                        const newImg = document.createElement('img');
                        newImg.id = 'cat-thumb-' + catId;
                        newImg.src = newSrc;
                        newImg.className = 'w-14 h-14 rounded-2xl object-cover shadow-xs border border-purple-100/80 transition-transform group-hover/img:scale-105';
                        thumb.parentNode.replaceChild(newImg, thumb);
                    }
                    if (newImg || thumb) {
                        (newImg || thumb).classList.remove('opacity-40', 'animate-pulse');
                    }
                }
                alert(data.message || 'Image uploaded successfully!');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to upload image.');
                if (thumb) thumb.classList.remove('opacity-40', 'animate-pulse');
            }
        })
        .catch(err => {
            // Fallback: standard form submit
            form.submit();
        });
    }

    function previewModalImage(input) {
        const preview = document.getElementById('categoryImagePreview');
        const placeholder = document.getElementById('categoryImagePlaceholder');
        const removeBtn = document.getElementById('removeImageBtn');
        document.getElementById('categoryRemoveImage').value = '0';

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                removeBtn.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeSelectedImage() {
        const input = document.getElementById('category_image');
        const preview = document.getElementById('categoryImagePreview');
        const placeholder = document.getElementById('categoryImagePlaceholder');
        const removeBtn = document.getElementById('removeImageBtn');

        input.value = '';
        preview.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
        document.getElementById('categoryRemoveImage').value = '1';
    }

    function openAddCategoryModal() {
        isEditing = false;
        document.getElementById('categoryModalTitle').innerText = 'Add Course Category';
        document.getElementById('categoryFormAction').value = 'create';
        document.getElementById('categoryId').value = '';
        document.getElementById('category_name').value = '';
        document.getElementById('category_slug').value = '';
        document.getElementById('category_description').value = '';
        document.getElementById('category_status').value = 'Active';
        document.getElementById('categorySubmitBtn').innerText = 'Create Category';

        // Reset image input and preview
        removeSelectedImage();
        document.getElementById('categoryRemoveImage').value = '0';

        const modal = document.getElementById('categoryModal');
        const card = document.getElementById('categoryModalCard');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function openEditCategoryModal(cat) {
        isEditing = true;
        document.getElementById('categoryModalTitle').innerText = 'Edit Course Category';
        document.getElementById('categoryFormAction').value = 'update';
        document.getElementById('categoryId').value = cat.id;
        document.getElementById('category_name').value = cat.category_name;
        document.getElementById('category_slug').value = cat.slug;
        document.getElementById('category_description').value = cat.description || '';
        document.getElementById('category_status').value = cat.status;
        document.getElementById('categorySubmitBtn').innerText = 'Save Changes';
        document.getElementById('category_image').value = '';
        document.getElementById('categoryRemoveImage').value = '0';

        // Set image preview if present
        const preview = document.getElementById('categoryImagePreview');
        const placeholder = document.getElementById('categoryImagePlaceholder');
        const removeBtn = document.getElementById('removeImageBtn');

        if (cat.image && cat.image.trim() !== '') {
            preview.src = '../' + cat.image.replace(/^\/+/, '') + '?v=' + new Date().getTime();
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
        } else {
            preview.src = '';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            removeBtn.classList.add('hidden');
        }

        const modal = document.getElementById('categoryModal');
        const card = document.getElementById('categoryModalCard');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function closeCategoryModal() {
        const modal = document.getElementById('categoryModal');
        const card = document.getElementById('categoryModalCard');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function confirmDeleteCategory(id, name, plansCount) {
        const modal = document.getElementById('deleteModal');
        const card = document.getElementById('deleteModalCard');
        const deleteSubmit = document.getElementById('confirmDeleteSubmitBtn');
        const msg = document.getElementById('deleteModalMessage');

        document.getElementById('deleteCategoryId').value = id;

        if (plansCount > 0) {
            msg.innerHTML = `<span class="text-rose-600 font-bold">Cannot Delete!</span> <strong>${name}</strong> has <strong>${plansCount}</strong> attached pricing plans. You must reassign or remove them first.`;
            deleteSubmit.disabled = true;
            deleteSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            msg.innerHTML = `Are you sure you want to permanently delete <strong>"${name}"</strong>?`;
            deleteSubmit.disabled = false;
            deleteSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        const card = document.getElementById('deleteModalCard');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function toggleMoreFeatures(catId) {
        const container = document.getElementById('extra-features-cat-' + catId);
        const icon = document.getElementById('more-icon-' + catId);
        const text = document.getElementById('more-text-' + catId);
        if (!container) return;

        const isHidden = container.classList.contains('hidden');
        if (isHidden) {
            container.classList.remove('hidden');
            if (icon) icon.classList.add('rotate-180');
            if (text) text.innerText = 'Show less';
        } else {
            container.classList.add('hidden');
            if (icon) icon.classList.remove('rotate-180');
            const totalRemaining = container.children.length;
            if (text) text.innerText = `+${totalRemaining} more features`;
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>
