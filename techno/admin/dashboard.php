<?php
// admin/dashboard.php - Admin Dashboard for Technodome
require_once '../db_config.php';
require_once 'auth.php';

require_login();

// Handle Lead Deletion via POST (with CSRF protection) or legacy GET with confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_lead') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('error', 'Security token mismatch.');
        header("Location: dashboard.php");
        exit();
    }
    $delete_id = intval($_POST['lead_id'] ?? 0);
    if ($delete_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->execute([$delete_id]);
        set_flash_message('success', 'Lead record has been successfully deleted.');
    }
    header("Location: dashboard.php");
    exit();
}

// Fallback for legacy GET deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
    $stmt->execute([$delete_id]);
    set_flash_message('success', 'Lead record has been successfully deleted.');
    header("Location: dashboard.php");
    exit();
}

// Search and filter for enquiries
$search_lead = trim($_GET['lead_search'] ?? '');
$lead_query = "SELECT * FROM leads WHERE 1=1";
$lead_params = [];

if (!empty($search_lead)) {
    $lead_query .= " AND (name LIKE ? OR mobile LIKE ? OR location LIKE ? OR course LIKE ?)";
    $lead_params[] = "%$search_lead%";
    $lead_params[] = "%$search_lead%";
    $lead_params[] = "%$search_lead%";
    $lead_params[] = "%$search_lead%";
}

$lead_query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($lead_query);
$stmt->execute($lead_params);
$leads = $stmt->fetchAll();

// Statistics calculation
// 1. Total leads
$totalLeads = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();

// 2. Today's leads
$stmt_today = $pdo->query("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()");
$todayLeads = $stmt_today->fetchColumn();

// 3. Categories stats
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$activeCategories = $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'Active'")->fetchColumn();

// 4. Pricing plans stats
$totalPlans = $pdo->query("SELECT COUNT(*) FROM pricing_plans")->fetchColumn();
$activePlans = $pdo->query("SELECT COUNT(*) FROM pricing_plans WHERE status = 'Active'")->fetchColumn();

$pageTitle = 'Dashboard - Technodome Admin';
$pageHeading = 'Overview & Statistics';
$pageSubheading = 'Real-time overview of enquiries, pricing plans, and academic tracks';

require_once 'includes/header.php';
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 md:gap-5 mb-8">
    <!-- Total Leads -->
    <div class="bg-white p-5 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-lg hover:shadow-brand/5 transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 bg-brand/10 rounded-2xl flex items-center justify-center text-brand text-lg group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-users"></i>
            </div>
            <span class="text-[10px] font-extrabold text-brand bg-brand/5 uppercase tracking-wider px-2.5 py-0.5 rounded-full">All Time</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400">Total Leads</h4>
        <div class="text-2xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $totalLeads; ?></div>
    </div>

    <!-- Today's Leads -->
    <div class="bg-white p-5 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-lg hover:shadow-brand-blue/5 transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 bg-brand-blue/10 rounded-2xl flex items-center justify-center text-brand-blue text-lg group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <span class="text-[10px] font-extrabold text-brand-blue bg-blue-50 uppercase tracking-wider px-2.5 py-0.5 rounded-full">Today</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400">Today's Leads</h4>
        <div class="text-2xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $todayLeads; ?></div>
    </div>

    <!-- System Health -->
    <div class="bg-white p-5 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-lg hover:shadow-brand-pink/5 transition-all group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 bg-brand-pink/10 rounded-2xl flex items-center justify-center text-brand-pink text-lg group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <span class="text-[10px] font-extrabold text-brand-pink bg-pink-50 uppercase tracking-wider px-2.5 py-0.5 rounded-full">Status</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400">System Health</h4>
        <div class="text-2xl font-outfit font-extrabold text-emerald-600 mt-1 flex items-center gap-1.5 text-lg">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span> Normal
        </div>
    </div>

    <!-- Total Categories -->
    <a href="categories.php" class="bg-white p-5 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-lg hover:shadow-purple-500/10 transition-all group block">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 bg-purple-100 rounded-2xl flex items-center justify-center text-brand text-lg group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <span class="text-[10px] font-extrabold text-purple-700 bg-purple-50 uppercase tracking-wider px-2.5 py-0.5 rounded-full">Tracks</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400">Total Categories</h4>
        <div class="text-2xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $totalCategories; ?></div>
    </a>

    <!-- Active Categories -->
    <a href="categories.php?status=Active" class="bg-white p-5 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-lg hover:shadow-emerald-500/10 transition-all group block">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 text-lg group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 uppercase tracking-wider px-2.5 py-0.5 rounded-full">Active</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400">Active Categories</h4>
        <div class="text-2xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $activeCategories; ?></div>
    </a>

    <!-- Active Pricing Plans -->
    <a href="pricing.php" class="bg-white p-5 rounded-[2rem] border border-gray-100/80 shadow-xs hover:shadow-lg hover:shadow-brand/10 transition-all group block">
        <div class="flex items-center justify-between mb-3">
            <div class="w-11 h-11 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 text-lg group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-tags"></i>
            </div>
            <span class="text-[10px] font-extrabold text-amber-700 bg-amber-50 uppercase tracking-wider px-2.5 py-0.5 rounded-full"><?php echo $activePlans; ?> Live</span>
        </div>
        <h4 class="text-xs font-bold text-gray-400">Pricing Plans</h4>
        <div class="text-2xl font-outfit font-extrabold text-gray-900 mt-1"><?php echo $totalPlans; ?></div>
    </a>
</div>

<!-- Quick Action Banners -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="bg-gradient-to-br from-brand/90 to-brand-dark p-6 rounded-[2.5rem] text-white flex items-center justify-between shadow-lg shadow-brand/15">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-wider px-3 py-1 rounded-full bg-white/20">Course Modules</span>
            <h3 class="text-lg font-outfit font-extrabold mt-2">Manage Course Categories</h3>
            <p class="text-xs text-white/80 mt-1 max-w-sm">Organize programming curriculums, web design, and AI specializations.</p>
        </div>
        <a href="categories.php" class="px-4 py-2.5 bg-white text-brand rounded-2xl font-bold text-xs hover:bg-slate-50 transition-all shadow-md shrink-0 ml-4">
            Manage <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
    </div>

    <div class="bg-gradient-to-br from-brand-blue to-indigo-600 p-6 rounded-[2.5rem] text-white flex items-center justify-between shadow-lg shadow-brand-blue/15">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-wider px-3 py-1 rounded-full bg-white/20">Tuition & Plans</span>
            <h3 class="text-lg font-outfit font-extrabold mt-2">Update Course Pricing</h3>
            <p class="text-xs text-white/80 mt-1 max-w-sm">Configure standard fees, limited-time discounts, and duration packages.</p>
        </div>
        <a href="pricing.php" class="px-4 py-2.5 bg-white text-brand-blue rounded-2xl font-bold text-xs hover:bg-slate-50 transition-all shadow-md shrink-0 ml-4">
            View Plans <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
    </div>
</div>

<!-- Enquiry Records Section (Preserved & Enhanced) -->
<div id="enquiries" class="bg-white rounded-[2.5rem] border border-gray-100/80 shadow-sm overflow-hidden">
    <!-- Header with Search & Reload -->
    <div class="px-8 py-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-outfit font-extrabold text-gray-900">Enquiry Records</h2>
                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-brand/10 text-brand">
                    <?php echo count($leads); ?> leads
                </span>
            </div>
            <p class="text-gray-400 font-medium text-xs mt-1">Management and tracking of all incoming student leads</p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <!-- Search Enquiries -->
            <form method="GET" action="dashboard.php" class="relative flex-1 md:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="lead_search" value="<?php echo htmlspecialchars($search_lead); ?>" placeholder="Search name, phone, city..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs font-medium focus:ring-2 focus:ring-brand/20 focus:border-brand outline-none transition-all">
                <?php if (!empty($search_lead)): ?>
                    <a href="dashboard.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>

            <button onclick="window.location.reload()" title="Refresh Registry" class="p-2.5 rounded-2xl bg-gray-50 text-gray-500 hover:text-brand hover:bg-white border border-transparent hover:border-gray-200 transition-all shadow-xs">
                <i class="fa-solid fa-rotate text-sm"></i>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/70 border-b border-gray-100">
                    <th class="px-8 py-4 text-xs font-extrabold text-gray-400 uppercase tracking-wider">Student & Date</th>
                    <th class="px-8 py-4 text-xs font-extrabold text-gray-400 uppercase tracking-wider">Contact Info</th>
                    <th class="px-8 py-4 text-xs font-extrabold text-gray-400 uppercase tracking-wider">Preferences</th>
                    <th class="px-8 py-4 text-xs font-extrabold text-gray-400 uppercase tracking-wider">Background</th>
                    <th class="px-8 py-4 text-center text-xs font-extrabold text-gray-400 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (count($leads) > 0): ?>
                    <?php foreach ($leads as $lead): ?>
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-11 bg-brand/10 text-brand rounded-2xl flex items-center justify-center font-bold text-sm shrink-0">
                                        <?php echo strtoupper(substr($lead['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-gray-900 text-sm"><?php echo htmlspecialchars($lead['name']); ?></div>
                                        <div class="text-[11px] font-bold text-gray-400 mt-0.5">
                                            <i class="fa-regular fa-clock mr-1 opacity-70"></i> 
                                            <?php echo date('d M Y, h:i A', strtotime($lead['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="space-y-1">
                                    <a href="tel:<?php echo htmlspecialchars($lead['mobile']); ?>" class="flex items-center gap-2 text-brand-blue font-bold text-xs hover:underline">
                                        <i class="fa-solid fa-phone text-[10px]"></i>
                                        <?php echo htmlspecialchars($lead['mobile']); ?>
                                    </a>
                                    <div class="flex items-center gap-2 text-gray-500 font-semibold text-xs">
                                        <i class="fa-solid fa-location-dot text-[10px] text-brand-pink"></i>
                                        <?php echo htmlspecialchars($lead['location']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col gap-1.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-indigo-50 text-indigo-600 border border-indigo-100 w-fit">
                                        <i class="fa-solid fa-graduation-cap mr-1 text-[10px] opacity-70"></i>
                                        <?php echo htmlspecialchars($lead['course']); ?>
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-amber-50 text-amber-600 border border-amber-100 w-fit">
                                        <i class="fa-solid fa-display mr-1 text-[10px] opacity-70"></i>
                                        <?php echo htmlspecialchars($lead['learning_mode'] ?? 'Offline'); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-xs font-bold text-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-400 text-[11px]">Computer Skills:</span>
                                    <?php if ($lead['computer_knowledge'] == 'Yes'): ?>
                                        <span class="text-emerald-500 flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check"></i> Foundational
                                        </span>
                                    <?php else: ?>
                                        <span class="text-red-400 flex items-center gap-1">
                                            <i class="fa-solid fa-circle-xmark"></i> Beginner
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <button type="button" 
                                        onclick="confirmDeleteLead(<?php echo $lead['id']; ?>, '<?php echo addslashes(htmlspecialchars($lead['name'])); ?>')"
                                        class="w-8 h-8 flex items-center justify-center rounded-xl bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all shadow-xs mx-auto"
                                        title="Delete Entry">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-8 py-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3 opacity-30">
                                <i class="fa-solid fa-inbox text-6xl"></i>
                                <p class="font-outfit font-extrabold text-lg">No enquiries found</p>
                                <p class="font-medium text-xs">Incoming website enquiries will automatically appear here once submitted.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Delete Lead Confirmation -->
<div id="deleteLeadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 w-full max-w-md p-8 text-center transform scale-95 transition-transform duration-300" id="deleteLeadModalCard">
        <div class="w-16 h-16 rounded-3xl bg-red-100 text-red-600 flex items-center justify-center text-2xl mx-auto mb-4">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 class="text-xl font-outfit font-extrabold text-gray-900 mb-2">Delete Lead Record?</h3>
        <p id="deleteLeadModalMessage" class="text-xs font-medium text-gray-500 mb-6">
            Are you sure you want to permanently delete this student enquiry?
        </p>

        <form action="dashboard.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="delete_lead">
            <input type="hidden" name="lead_id" id="deleteLeadId" value="">

            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteLeadModal()" 
                        class="px-5 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition-colors">
                    No, Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2.5 rounded-2xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-500/25 transition-all">
                    Yes, Delete Lead
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDeleteLead(id, name) {
        document.getElementById('deleteLeadId').value = id;
        document.getElementById('deleteLeadModalMessage').innerHTML = `Are you sure you want to permanently delete enquiry for <strong>"${name}"</strong>?`;
        const modal = document.getElementById('deleteLeadModal');
        const card = document.getElementById('deleteLeadModalCard');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }

    function closeDeleteLeadModal() {
        const modal = document.getElementById('deleteLeadModal');
        const card = document.getElementById('deleteLeadModalCard');
        modal.classList.add('opacity-0');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
</script>

<?php require_once 'includes/footer.php'; ?>
