<?php
/**
 * index.php - Technodome Dynamic Homepage
 * Fetches categories and pricing plans from DB so admin panel
 * changes reflect on the website in real-time.
 */
require_once 'db_config.php';

// Fetch active categories (for course dropdowns)
$cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 'Active' ORDER BY category_name ASC");
$cat_stmt->execute();
$activeCategories = $cat_stmt->fetchAll();

// Fetch active pricing plans (joined with category name)
$plan_stmt = $pdo->prepare("
    SELECT p.*, c.category_name
    FROM pricing_plans p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'Active'
    ORDER BY p.price ASC
");
$plan_stmt->execute();
$activePlans = $plan_stmt->fetchAll();

// Group plans by category_id for category card modals
$plansByCategory = [];
foreach ($activePlans as $ap) {
    $catId = (int)($ap['category_id'] ?? 0);
    if (!isset($plansByCategory[$catId])) {
        $plansByCategory[$catId] = [];
    }
    $plansByCategory[$catId][] = $ap;
}

// Build JS-ready data map for category packages modal
// Keys are category IDs (as strings for JSON), values have name + packages array
$categoryModalDataMap = [];
foreach ($activeCategories as $cat) {
    $cid = (int)($cat['id'] ?? 0);
    $plans = $plansByCategory[$cid] ?? [];
    $pkgsArr = [];
    foreach ($plans as $p) {
        $hasDisc = !empty($p['discount_price']) && (float)$p['discount_price'] > 0 && (float)$p['discount_price'] < (float)$p['price'];
        $dispPrice = $hasDisc ? (float)$p['discount_price'] : (float)$p['price'];
        $save = $hasDisc ? (int)round(((float)$p['price'] - (float)$p['discount_price']) / (float)$p['price'] * 100) : 0;
        $pkgsArr[] = [
            'plan_name'       => (string)($p['plan_name'] ?? ''),
            'course_duration' => (string)($p['course_duration'] ?? ''),
            'price'           => number_format((float)$p['price'], 0),
            'display_price'   => number_format($dispPrice, 0),
            'has_discount'    => $hasDisc,
            'save_pct'        => $save,
            'description'     => (string)($p['description'] ?? ''),
        ];
    }
    $categoryModalDataMap[$cid] = [
        'category_name' => (string)($cat['category_name'] ?? ''),
        'packages'      => $pkgsArr,
    ];
}
?>
<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden w-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Social Media Marketing Course | Technodome</title>

    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

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

    <style type="text/tailwindcss">
        @layer utilities {
            .text-gradient {
                @apply bg-clip-text text-transparent bg-gradient-to-r from-brand to-brand-blue;
            }
            .glass-card {
                @apply bg-white/40 border border-white/60 rounded-[2rem] p-8 lg:p-10 backdrop-blur-xl shadow-xl hover:bg-white/60 hover:-translate-y-2 hover:shadow-2xl transition-all duration-300 relative overflow-hidden;
            }
        }
        
        body {
            @apply font-inter text-gray-800 overflow-x-hidden;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
        }
        h1, h2, h3, h4, .brand-logo { @apply font-outfit; }
        html { scroll-behavior: smooth; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        /* Abstract Colorful Mesh Background */
        .color-mesh {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            overflow: hidden;
            background: #ffffff;
        }
        .mesh-blob {
            position: absolute;
            filter: blur(120px);
            border-radius: 50%;
            animation: float 10s infinite ease-in-out alternate;
        }
    </style>
</head>

<body class="antialiased relative overflow-x-hidden w-full">

    <!-- Colorful Attracting Background -->
    <div class="color-mesh">
        <div class="mesh-blob bg-pink-300/60 w-[600px] h-[600px] -top-[10%] -left-[10%] drop-shadow-2xl"></div>
        <div class="mesh-blob bg-blue-300/60 w-[800px] h-[800px] top-[20%] right-[5%] animation-delay-2000"></div>
        <div class="mesh-blob bg-purple-300/60 w-[700px] h-[700px] bottom-[-10%] left-[20%] animation-delay-4000"></div>
    </div>

    <!-- Header Navigation -->
    <header
        class="fixed top-0 w-full py-4 px-6 md:px-12 flex justify-between items-center z-50 bg-white/70 backdrop-blur-xl border-b border-white/50 shadow-sm"
        data-aos="fade-down" data-aos-duration="1000">
        <a href="index.php" class="flex items-center gap-3 decoration-transparent">
            <img src="logo.png" alt="Technodome Logo" class="h-10 sm:h-12 w-auto object-contain">
        </a>
        <nav class="hidden md:block">
            <ul class="flex space-x-8 items-center">
                <li><a href="#learn" class="font-medium text-gray-700 hover:text-brand transition-colors">What You
                        Learn</a></li>
                <li><a href="#earn" class="font-medium text-gray-700 hover:text-brand transition-colors">Earn</a></li>
                <li><a href="#fees" class="font-medium text-gray-700 hover:text-brand transition-colors">Fees</a></li>
                <li>
                    <a href="#contact"
                        class="bg-gradient-to-r from-brand to-brand-blue text-white font-semibold py-2 px-6 rounded-full shadow-lg hover:shadow-brand/40 transition-all hover:-translate-y-0.5 inline-block text-sm">
                        Enroll Now
                    </a>
                </li>
            </ul>
        </nav>
        <button id="mobileMenuBtn" class="md:hidden text-gray-800 text-2xl w-10 h-10 flex items-center justify-center hover:text-brand transition-colors">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <!-- Mobile Menu Container -->
    <div id="mobileMenu" class="fixed top-[74px] left-0 w-full bg-white/95 backdrop-blur-3xl border-b border-gray-200 shadow-2xl z-40 transform -translate-y-[150%] transition-transform duration-300 ease-in-out md:hidden flex flex-col pt-6 pb-8 px-6">
        <ul class="flex flex-col space-y-6 text-center">
            <li><a href="#learn" class="block font-semibold text-xl text-gray-800 hover:text-brand transition-colors mobile-link">What You Learn</a></li>
            <li><a href="#earn" class="block font-semibold text-xl text-gray-800 hover:text-brand transition-colors mobile-link">Earn</a></li>
            <li><a href="#fees" class="block font-semibold text-xl text-gray-800 hover:text-brand transition-colors mobile-link">Fees</a></li>
            <li class="pt-4">
                <a href="#contact" class="bg-gradient-to-r from-brand to-brand-blue text-white font-semibold py-3.5 px-8 rounded-full shadow-lg inline-block w-full mobile-link text-xl active:scale-95 transition-all">
                    Enroll Now
                </a>
            </li>
        </ul>
    </div>

    <!-- Hero Section -->
    <section id="home"
        class="min-h-[90vh] flex items-center justify-between flex-col md:flex-row pt-32 pb-16 px-6 md:px-12 lg:px-24">
        <div class="w-full md:w-1/2 md:pr-10 text-center md:text-left z-10" data-aos="fade-right"
            data-aos-duration="1200">
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold leading-tight mb-6 text-gray-900 drop-shadow-sm">
                Become a Professional in <br><span class="text-gradient drop-shadow-sm">Social Media Marketing</span><br> &amp; Start Earning.
            </h1>
            <p class="text-xl md:text-2xl text-gray-600 mb-8 font-medium">
                Build Skills. Grow Online. Earn Smart. <br>Technodome brings you a 3 Months &amp; 5 Months Professional Social Media Marketing Course.
            </p>

            <div class="flex flex-wrap justify-center md:justify-start gap-3 mb-10">
                <div
                    class="bg-white/60 border border-white/80 py-2 px-4 rounded-full text-sm text-gray-800 font-semibold shadow-sm backdrop-blur-md flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap text-brand"></i> Students
                </div>
                <div
                    class="bg-white/60 border border-white/80 py-2 px-4 rounded-full text-sm text-gray-800 font-semibold shadow-sm backdrop-blur-md flex items-center gap-2">
                    <i class="fa-solid fa-house-chimney-user text-brand-pink"></i> Housewives
                </div>
                <div
                    class="bg-white/60 border border-white/80 py-2 px-4 rounded-full text-sm text-gray-800 font-semibold shadow-sm backdrop-blur-md flex items-center gap-2">
                    <i class="fa-solid fa-briefcase text-brand-blue"></i> Business Owners
                </div>
                <div
                    class="bg-white/60 border border-white/80 py-2 px-4 rounded-full text-sm text-gray-800 font-semibold shadow-sm backdrop-blur-md flex items-center gap-2">
                    <i class="fa-solid fa-user-tie text-brand"></i> Job Seekers
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center md:justify-start gap-4">
                <a href="#contact"
                    class="bg-gradient-to-r from-brand to-brand-blue text-white font-semibold py-3.5 px-8 rounded-full shadow-xl shadow-brand/30 hover:shadow-brand/50 transition-all hover:-translate-y-1 inline-flex justify-center items-center gap-2 font-outfit text-lg w-full sm:w-auto">
                    Enroll Now
                </a>
                <a href="#learn"
                    class="bg-white border-2 border-brand text-brand font-semibold py-3 px-8 rounded-full shadow-lg hover:bg-brand hover:text-white transition-all hover:-translate-y-1 inline-flex justify-center items-center gap-2 font-outfit text-lg w-full sm:w-auto">
                    Explore Course
                </a>
            </div>
        </div>
        <div class="w-full md:w-1/2 mt-16 md:mt-0 flex justify-center relative z-10" data-aos="fade-left"
            data-aos-duration="1200" data-aos-delay="300">
            <img src="assets/hero-img.png" alt="Learn Social Media Marketing 3D Illustration"
                class="w-full max-w-lg lg:max-w-[120%] animate-float drop-shadow-2xl">
        </div>
    </section>

    <!-- What You Will Learn -->
    <section id="learn"
        class="py-24 px-6 md:px-12 lg:px-24 relative z-10 bg-[url('assets/tech-bg.png')] bg-cover bg-center bg-fixed rounded-[3rem] shadow-sm my-8 mx-4 border border-white/40">
        <div class="text-center max-w-3xl mx-auto mb-16 px-4 bg-white/40 p-10 rounded-3xl backdrop-blur-md border border-white/60 shadow-xl"
            data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4 text-gray-900">What You Will <span
                    class="text-gradient">Learn</span></h2>
            <p class="text-xl text-gray-700 font-medium tracking-wide">Master the skills that actually generate results
                and help businesses grow online.</p>
        </div>

        <!-- Category Cards Carousel -->
        <div class="relative" id="learnCarouselWrapper">

            <!-- Left Scroll Button -->
            <button id="learnScrollLeft" onclick="scrollLearnCarousel(-1)" aria-label="Scroll left"
                    class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-3 z-20
                           w-12 h-12 rounded-full bg-white shadow-xl border border-purple-100
                           flex items-center justify-center text-brand
                           hover:bg-brand hover:text-white hover:border-brand
                           transition-all duration-200 cursor-pointer
                           opacity-50 hover:opacity-100 disabled:opacity-20 disabled:cursor-not-allowed">
                <i class="fa-solid fa-chevron-left text-base"></i>
            </button>

            <!-- Right Scroll Button -->
            <button id="learnScrollRight" onclick="scrollLearnCarousel(1)" aria-label="Scroll right"
                    class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-3 z-20
                           w-12 h-12 rounded-full bg-white shadow-xl border border-purple-100
                           flex items-center justify-center text-brand
                           hover:bg-brand hover:text-white hover:border-brand
                           transition-all duration-200 cursor-pointer
                           opacity-50 hover:opacity-100 disabled:opacity-20 disabled:cursor-not-allowed">
                <i class="fa-solid fa-chevron-right text-base"></i>
            </button>

            <!-- Scrollable Row -->
            <div id="learnCarousel"
                 class="flex gap-6 overflow-x-auto scroll-smooth pb-4 px-2"
                 style="scrollbar-width: none; -ms-overflow-style: none;">

            <?php if (count($activeCategories) > 0): ?>
                <?php 
                // Default image map for slugs or fallback
                $imgMap = [
                    'social-media-marketing' => 'assets/smm-img.png',
                    'website-development' => 'assets/webdev-img.png',
                    'graphic-design' => 'assets/design-img.png',
                    'video-creation-ai-tools' => 'assets/video-img.png'
                ];
                $defaultImages = ['assets/smm-img.png', 'assets/webdev-img.png', 'assets/design-img.png', 'assets/video-img.png'];
                $checkColors = ['text-brand-blue', 'text-brand', 'text-brand-pink', 'text-emerald-500'];

                foreach ($activeCategories as $cIdx => $cat): 
                    $catSlug = $cat['slug'] ?? '';
                    // Prioritize dynamically uploaded image from Admin Panel
                    $catImg = '';
                    if (!empty($cat['image']) && file_exists(__DIR__ . '/' . ltrim($cat['image'], '/'))) {
                        $catImg = htmlspecialchars(ltrim($cat['image'], '/'));
                    } else {
                        $catImg = $imgMap[$catSlug] ?? $defaultImages[$cIdx % count($defaultImages)];
                    }
                    $checkColor = $checkColors[$cIdx % count($checkColors)];
                    $delay = 200 + ($cIdx * 100);

                    // Parse description into bullet points
                    $rawDesc = trim($cat['description'] ?? '');
                    $features = [];
                    if (!empty($rawDesc)) {
                        $lines = preg_split('/\r\n|\r|\n/', $rawDesc);
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (!empty($line)) {
                                $features[] = $line;
                            }
                        }
                    }
                    // Attached packages for this category
                    $catId = (int)($cat['id'] ?? 0);
                    $attachedPlans = $plansByCategory[$catId] ?? [];
                    $planCount = count($attachedPlans);
                ?>
                <div class="glass-card !p-6 flex flex-col group cursor-pointer transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 hover:border-brand/40 relative shrink-0"
                     style="min-width: 280px; width: 280px;"
                     data-aos="zoom-in" data-aos-delay="<?php echo $delay; ?>"
                     onclick="redirectToCourseFee('<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>', <?php echo $catId; ?>)"
                     title="Click to view Professional Course Fees for <?php echo htmlspecialchars($cat['category_name']); ?>">
                    
                    <!-- Image -->
                    <div class="w-full h-52 sm:h-56 rounded-2xl mb-6 overflow-hidden shadow-md border border-white/50 bg-purple-50/50 flex items-center justify-center relative">
                        <img src="<?php echo $catImg; ?>" alt="<?php echo htmlspecialchars($cat['category_name']); ?>"
                            class="w-full h-full object-cover aspect-square transition-transform duration-500 group-hover:scale-105" loading="lazy">
                        
                        <!-- Floating Packages Count Badge (opens quick package breakdown modal) -->
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-md text-brand font-extrabold text-xs px-3 py-1.5 rounded-full shadow-md flex items-center gap-1.5 border border-purple-100 hover:bg-brand hover:text-white transition-all cursor-pointer"
                             onclick="event.stopPropagation(); openCategoryPackagesModal(<?php echo $catId; ?>)"
                             title="Click to view package details modal">
                            <i class="fa-solid fa-layer-group text-[11px]"></i>
                            <span><?php echo $planCount; ?> <?php echo $planCount === 1 ? 'Package' : 'Packages'; ?></span>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold mb-3 text-gray-900 group-hover:text-brand transition-colors"><?php echo htmlspecialchars($cat['category_name']); ?></h3>
                    
                    <?php if (!empty($features)): ?>
                    <ul class="space-y-2.5 text-gray-700 font-medium text-sm flex-1 mb-5">
                        <?php foreach ($features as $feat): ?>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-check <?php echo $checkColor; ?> mt-1 font-bold text-xs shrink-0"></i>
                            <span class="line-clamp-2"><?php echo htmlspecialchars($feat); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <!-- Bottom Call-To-Action Button: Opens Package Details Modal -->
                    <div class="mt-auto pt-2">
                        <button type="button" 
                                onclick="event.stopPropagation(); openCategoryPackagesModal(<?php echo $catId; ?>)"
                                class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-brand/10 to-brand-blue/10 text-brand border border-brand/20 group-hover:from-brand group-hover:to-brand-blue group-hover:text-white font-extrabold text-xs flex items-center justify-center gap-2 transition-all shadow-xs cursor-pointer">
                            <i class="fa-solid fa-layer-group text-xs"></i>
                            <span>Show Packages</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback: Static Cards -->
                <!-- Card 1 -->
                <div class="glass-card !p-6 flex flex-col shrink-0" style="min-width:280px;width:280px;" data-aos="zoom-in" data-aos-delay="200">
                    <img src="assets/smm-img.png" alt="Social Media Marketing"
                        class="w-full h-48 object-cover rounded-2xl mb-6 shadow-md border border-white/50">
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Social Media Marketing</h3>
                    <ul class="space-y-3 text-gray-700 font-medium text-sm">
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand-blue mt-1 font-bold"></i>
                            Facebook, Instagram &amp; YouTube Strategy</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand-blue mt-1 font-bold"></i>
                            Increase Followers &amp; Subscribers</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand-blue mt-1 font-bold"></i>
                            Paid Ads &amp; Performance Marketing</li>
                    </ul>
                </div>
                <!-- Card 2 -->
                <div class="glass-card !p-6 flex flex-col shrink-0" style="min-width:280px;width:280px;" data-aos="zoom-in" data-aos-delay="300">
                    <img src="assets/webdev-img.png" alt="Website Development"
                        class="w-full h-48 object-cover rounded-2xl mb-6 shadow-md border border-white/50">
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Website Development</h3>
                    <ul class="space-y-3 text-gray-700 font-medium text-sm">
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand mt-1 font-bold"></i>
                            Create professional websites</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand mt-1 font-bold"></i>
                            Landing pages for business growth</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand mt-1 font-bold"></i>
                            Basics of domain, hosting &amp; setup</li>
                    </ul>
                </div>
                <!-- Card 3 -->
                <div class="glass-card !p-6 flex flex-col shrink-0" style="min-width:280px;width:280px;" data-aos="zoom-in" data-aos-delay="400">
                    <img src="assets/design-img.png" alt="Graphic Design"
                        class="w-full h-48 object-cover rounded-2xl mb-6 shadow-md border border-white/50">
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Graphic Design</h3>
                    <ul class="space-y-3 text-gray-700 font-medium text-sm">
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand mt-1 font-bold"></i> Social media creative</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand mt-1 font-bold"></i>
                            Branding &amp; ad design</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand mt-1 font-bold"></i> Tools like Canva &amp; advanced design concepts</li>
                    </ul>
                </div>
                <!-- Card 4 -->
                <div class="glass-card !p-6 flex flex-col shrink-0" style="min-width:280px;width:280px;" data-aos="zoom-in" data-aos-delay="500">
                    <img src="assets/video-img.png" alt="Video &amp; AI Tools"
                        class="w-full h-48 object-cover rounded-2xl mb-6 shadow-md border border-white/50">
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Video Creation &amp; AI Tools</h3>
                    <ul class="space-y-3 text-gray-700 font-medium text-sm">
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand font-bold mt-1"></i> Reel &amp; video editing techniques</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand font-bold mt-1"></i> Viral content strategy</li>
                        <li class="flex items-start gap-3"><i class="fa-solid fa-check text-brand font-bold mt-1"></i>
                            Implementation of 25+ AI Tools for faster growth</li>
                    </ul>
                </div>
            <?php endif; ?>

            </div><!-- /learnCarousel -->
        </div><!-- /learnCarouselWrapper -->

        <script>
            // Hide scrollbar track in webkit
            (function() {
                const style = document.createElement('style');
                style.textContent = '#learnCarousel::-webkit-scrollbar { display: none; }';
                document.head.appendChild(style);
            })();

            function scrollLearnCarousel(dir) {
                const carousel = document.getElementById('learnCarousel');
                if (!carousel) return;
                const cardWidth = 280 + 24; // card width + gap
                carousel.scrollBy({ left: dir * cardWidth, behavior: 'smooth' });
                setTimeout(updateLearnArrows, 350);
            }

            function updateLearnArrows() {
                const carousel = document.getElementById('learnCarousel');
                const btnLeft  = document.getElementById('learnScrollLeft');
                const btnRight = document.getElementById('learnScrollRight');
                if (!carousel || !btnLeft || !btnRight) return;
                btnLeft.disabled  = carousel.scrollLeft <= 4;
                btnRight.disabled = carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth - 4;
            }

            document.addEventListener('DOMContentLoaded', function() {
                const carousel = document.getElementById('learnCarousel');
                if (carousel) {
                    updateLearnArrows();
                    carousel.addEventListener('scroll', updateLearnArrows);
                }
            });
        </script>

    </section>

    <!-- Learn How to Earn -->
    <section id="earn"
        class="py-24 px-6 md:px-12 lg:px-24 flex flex-col md:flex-row items-center gap-12 lg:gap-20 relative z-10">
        <div class="w-full md:w-[45%]" data-aos="fade-right">
            <div class="bg-white/40 p-4 rounded-[2.5rem] backdrop-blur-md shadow-2xl border border-white/60">
                <img src="assets/earning-img.png" alt="Earn Making Money Online"
                    class="w-full h-auto rounded-[2rem] drop-shadow-lg">
            </div>
        </div>
        <div class="w-full md:w-[55%] bg-white/50 p-10 lg:p-14 rounded-[3rem] backdrop-blur-xl border border-white/60 shadow-xl"
            data-aos="fade-left">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-6 text-gray-900">Learn How to <span
                    class="text-gradient">Earn</span> from Social Media</h2>
            <p class="text-lg text-gray-700 mb-8 font-medium">Don't just scroll through your feeds. Learn the exact
                methods professionals use to generate income and build a sustainable career online.</p>

            <ul class="space-y-4 mb-10">
                <li class="flex items-center gap-4 text-lg font-semibold text-gray-800"><i
                        class="fa-solid fa-circle-check text-2xl text-gradient"></i> Monetize Instagram, Facebook &amp;
                    YouTube</li>
                <li class="flex items-center gap-4 text-lg font-semibold text-gray-800"><i
                        class="fa-solid fa-circle-check text-2xl text-gradient"></i> Get freelance projects &amp; clients</li>
                <li class="flex items-center gap-4 text-lg font-semibold text-gray-800"><i
                        class="fa-solid fa-circle-check text-2xl text-gradient"></i> Work with real clients &amp; businesses
                </li>
                <li class="flex items-center gap-4 text-lg font-semibold text-gray-800"><i
                        class="fa-solid fa-circle-check text-2xl text-gradient"></i> Build your own digital brand</li>
            </ul>

            <h3 class="text-2xl font-bold mb-6 text-gray-900">Exclusive <span class="text-gradient">Benefits</span></h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div
                    class="bg-white/80 border border-white shadow-sm p-4 rounded-2xl flex items-center gap-3 font-semibold text-gray-800">
                    <i class="fa-solid fa-gift text-brand text-xl"></i> Premium Software 1 Yr FREE
                </div>
                <div
                    class="bg-white/80 border border-white shadow-sm p-4 rounded-2xl flex items-center gap-3 font-semibold text-gray-800">
                    <i class="fa-solid fa-users text-brand text-xl"></i> Real Client Projects
                </div>
                <div
                    class="bg-white/80 border border-white shadow-sm p-4 rounded-2xl flex items-center gap-3 font-semibold text-gray-800">
                    <i class="fa-solid fa-wallet text-brand text-xl"></i> Monthly Income Potential
                </div>
                <div
                    class="bg-white/80 border border-white shadow-sm p-4 rounded-2xl flex items-center gap-3 font-semibold text-gray-800">
                    <i class="fa-solid fa-laptop-code text-brand text-xl"></i> Industry-Oriented Training
                </div>
            </div>
        </div>
    </section>

    <!-- Course Fees — Dynamic from DB -->
    <section id="fees"
        class="py-24 px-6 md:px-12 lg:px-24 text-center relative z-10 bg-[url('assets/wave-bg.png')] bg-cover bg-center bg-fixed rounded-[3rem] shadow-sm my-8 mx-4 border border-white/40">
        <div class="max-w-3xl mx-auto mb-16 bg-white/40 p-8 rounded-[2.5rem] backdrop-blur-md shadow-lg border border-white/60"
            data-aos="fade-down">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4 text-gray-900">Professional <span
                    class="text-gradient">Course Fees</span></h2>
            <p class="text-xl text-gray-700 font-medium tracking-wide">Affordable fees with flexible payment options. Choose a plan that suits you best.</p>
        </div>

        <?php if (count($activePlans) > 0): ?>

        <!-- Search Bar + Category Filter Row -->
        <div class="max-w-6xl mx-auto mb-8 flex flex-col sm:flex-row items-center gap-4" data-aos="fade-up">

            <!-- Search Bar with Search Symbol and Clear (X) button -->
            <div class="relative w-full sm:max-w-xs group">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-brand text-sm pointer-events-none transition-colors group-focus-within:text-brand-blue">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text"
                       id="feesSearchInput"
                       placeholder="Search courses, plans, categories…"
                       oninput="onFeesSearchInput()"
                       class="w-full pl-10 pr-9 py-3 rounded-2xl bg-white/80 backdrop-blur-md border border-white/80 shadow-md text-sm font-medium text-gray-700 placeholder-gray-400 outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand/40 transition-all">
                
                <!-- Clear Search Button -->
                <button type="button"
                        id="feesSearchClearBtn"
                        onclick="clearFeesSearch()"
                        title="Clear search"
                        class="hidden absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-gray-200/80 hover:bg-red-100 hover:text-red-500 text-gray-500 items-center justify-center text-xs transition-all cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Category Filter Pills & Clear All Option -->
            <div class="flex items-center gap-2 flex-wrap justify-center sm:justify-start flex-1" id="feesCategoryFilters">
                <!-- "All" pill -->
                <button type="button"
                        onclick="setFeesCategory('all', this)"
                        data-filter="all"
                        class="fees-filter-pill active-pill inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-extrabold border transition-all cursor-pointer
                               bg-gradient-to-r from-brand to-brand-blue text-white border-transparent shadow-md shadow-brand/20">
                    <i class="fa-solid fa-border-all text-[11px]"></i>
                    All Categories
                    <span class="ml-0.5 bg-white/30 text-white px-1.5 py-0.5 rounded-full text-[10px] font-bold"><?php echo count($activePlans); ?></span>
                </button>

                <?php
                // Build unique category list with counts from activePlans
                $catCounts = [];
                foreach ($activePlans as $p) {
                    $cn = trim($p['category_name'] ?? 'Other');
                    if (!isset($catCounts[$cn])) $catCounts[$cn] = 0;
                    $catCounts[$cn]++;
                }
                $pillColors = [
                    'bg-purple-100 text-brand border-purple-200 hover:bg-brand hover:text-white hover:border-brand',
                    'bg-blue-100 text-brand-blue border-blue-200 hover:bg-brand-blue hover:text-white hover:border-brand-blue',
                    'bg-pink-100 text-brand-pink border-pink-200 hover:bg-brand-pink hover:text-white hover:border-brand-pink',
                    'bg-emerald-100 text-emerald-600 border-emerald-200 hover:bg-emerald-600 hover:text-white hover:border-emerald-600',
                ];
                $pci = 0;
                foreach ($catCounts as $catName => $cnt):
                    $pill = $pillColors[$pci % count($pillColors)];
                    $pci++;
                ?>
                <button type="button"
                        onclick="setFeesCategory('<?php echo htmlspecialchars($catName, ENT_QUOTES); ?>', this)"
                        data-filter="<?php echo htmlspecialchars($catName, ENT_QUOTES); ?>"
                        class="fees-filter-pill inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-extrabold border transition-all cursor-pointer <?php echo $pill; ?>">
                    <i class="fa-solid fa-tag text-[10px]"></i>
                    <?php echo htmlspecialchars($catName); ?>
                    <span class="ml-0.5 bg-black/10 px-1.5 py-0.5 rounded-full text-[10px] font-bold"><?php echo $cnt; ?></span>
                </button>
                <?php endforeach; ?>

                <!-- Clear All Filters Button (appears or can be clicked to reset everything) -->
                <button type="button"
                        id="feesResetAllBtn"
                        onclick="clearAllFeesFilters()"
                        title="Clear all search & filters"
                        class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all cursor-pointer shadow-xs">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i>
                    <span>Clear All</span>
                </button>
            </div>
        </div>

        <!-- No results message -->
        <div id="feesNoResults" class="hidden max-w-6xl mx-auto mb-8 text-center py-12 bg-white/50 rounded-3xl border border-white/80 shadow-sm backdrop-blur-md">
            <div class="w-14 h-14 rounded-2xl bg-purple-100 text-brand flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-search"></i>
            </div>
            <h4 class="font-extrabold text-gray-900 text-lg mb-1">No plans found</h4>
            <p class="text-sm text-gray-500 font-medium">Try a different search term or category filter.</p>
        </div>

        <!-- Dynamic Pricing Cards Grid (Strict 3 per row) -->
        <div id="pricingCardsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 max-w-6xl mx-auto gap-10">
            <?php 
            $colorSets = [
                ['accent' => 'brand', 'bg' => 'brand/10', 'border' => 'brand/20', 'text' => 'brand', 'badge' => 'brand/5'],
                ['accent' => 'brand-blue', 'bg' => 'brand-blue/10', 'border' => 'brand-blue/20', 'text' => 'brand-blue', 'badge' => 'blue-50'],
                ['accent' => 'brand-pink', 'bg' => 'brand-pink/10', 'border' => 'brand-pink/20', 'text' => 'brand-pink', 'badge' => 'pink-50'],
                ['accent' => 'emerald-600', 'bg' => 'emerald-50', 'border' => 'emerald-200', 'text' => 'emerald-600', 'badge' => 'emerald-50'],
            ];

            // Check if any plan has discount to decide featured plan (lowest discount price or first)
            $featuredIdx = 0;
            foreach ($activePlans as $idx => $p) {
                if (!empty($p['discount_price']) && $p['discount_price'] > 0) {
                    $featuredIdx = $idx;
                    break;
                }
            }

            foreach ($activePlans as $idx => $plan):
                $isFeatured = ($idx === $featuredIdx && count($activePlans) > 1);
                $colors = $colorSets[$idx % count($colorSets)];
                $hasDiscount = !empty($plan['discount_price']) && $plan['discount_price'] > 0 && $plan['discount_price'] < $plan['price'];
                $displayPrice = $hasDiscount ? $plan['discount_price'] : $plan['price'];
                $savePct = $hasDiscount ? round((($plan['price'] - $plan['discount_price']) / $plan['price']) * 100) : 0;
                $pageNumber = floor($idx / 9) + 1;
                $planCategoryName = trim($plan['category_name'] ?? 'Other');

                // Parse description as bullet features
                $features = [];
                if (!empty($plan['description'])) {
                    $lines = preg_split('/\r\n|\r|\n/', trim($plan['description']));
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) $features[] = $line;
                    }
                }
            ?>
            <?php 
                // Store plan data in JSON for the modal popup
                $planModalData = [
                    'plan_name' => $plan['plan_name'],
                    'category_name' => $planCategoryName,
                    'course_duration' => $plan['course_duration'],
                    'price' => number_format($plan['price'], 0),
                    'display_price' => number_format($displayPrice, 0),
                    'has_discount' => $hasDiscount,
                    'save_pct' => $savePct,
                    'features' => $features,
                    'colors' => $colors
                ];
                $planJson = htmlspecialchars(json_encode($planModalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="pricing-card glass-card flex flex-col justify-between group cursor-pointer transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 <?php echo $isFeatured ? 'bg-white/95 border-brand/50 shadow-[0_12px_45px_rgba(157,78,221,0.22)] md:-translate-y-4' : 'bg-white/75 hover:bg-white/90 hover:border-brand/30'; ?> <?php echo $pageNumber > 1 ? 'hidden' : ''; ?>"
                 data-page="<?php echo $pageNumber; ?>"
                 data-category="<?php echo htmlspecialchars(mb_strtolower($planCategoryName)); ?>"
                 data-category-id="<?php echo (int)($plan['category_id'] ?? 0); ?>"
                 data-name="<?php echo htmlspecialchars(mb_strtolower($plan['plan_name'] . ' ' . $planCategoryName)); ?>"
                 data-aos="<?php echo $idx % 2 === 0 ? 'flip-left' : 'flip-right'; ?>"
                 onclick='openPlanDetailsModal(<?php echo $planJson; ?>)'>

                <?php if ($isFeatured): ?>
                <div class="absolute -top-1 right-1/2 translate-x-1/2 bg-gradient-to-r from-brand to-brand-blue text-white px-8 py-2 rounded-b-2xl text-sm font-extrabold shadow-lg z-10 w-max uppercase tracking-wider">
                    Best Value
                </div>
                <?php endif; ?>

                <div>
                    <div class="<?php echo $isFeatured ? 'mb-6 mt-6' : 'mb-6'; ?>">
                        <!-- Category badge -->
                        <div class="inline-block bg-<?php echo $colors['badge']; ?> text-<?php echo $colors['text']; ?> py-1.5 px-5 rounded-full text-xs font-bold tracking-wide mb-3 border border-<?php echo $colors['border']; ?> uppercase">
                            <?php echo htmlspecialchars($plan['category_name'] ?? 'Course'); ?>
                        </div>
                        <h3 class="text-2xl font-extrabold text-gray-900 mb-2 group-hover:text-brand transition-colors"><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                            <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($plan['course_duration']); ?>
                        </span>
                    </div>

                    <!-- Pricing -->
                    <div class="mb-6">
                        <?php if ($hasDiscount): ?>
                        <div class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue font-outfit drop-shadow-sm">
                            ₹<?php echo number_format($displayPrice, 0); ?>
                        </div>
                        <div class="flex items-center justify-center gap-3 mt-2">
                            <span class="text-lg font-semibold text-gray-400 line-through">₹<?php echo number_format($plan['price'], 0); ?></span>
                            <span class="text-xs font-extrabold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">Save <?php echo $savePct; ?>%</span>
                        </div>
                        <?php else: ?>
                        <div class="text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue font-outfit drop-shadow-sm">
                            ₹<?php echo number_format($displayPrice, 0); ?>
                        </div>
                        <p class="text-gray-500 font-medium mt-2 text-sm">Standard Fee</p>
                        <?php endif; ?>
                    </div>

                    <!-- Features list preview (Compact: shows first 3, then "Click to view all" badge) -->
                    <?php if (!empty($features)): ?>
                    <ul class="space-y-2.5 mb-4 text-left text-sm">
                        <?php 
                        $previewFeatures = array_slice($features, 0, 3);
                        $remainingCount = count($features) - count($previewFeatures);
                        foreach ($previewFeatures as $feat): 
                        ?>
                        <li class="flex items-start gap-2.5 text-gray-700 font-medium leading-relaxed">
                            <i class="fa-solid fa-check text-<?php echo $colors['text']; ?> mt-1 shrink-0 text-xs font-bold"></i>
                            <span class="line-clamp-2"><?php echo htmlspecialchars($feat); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Click to View All Features hint -->
                    <div class="mb-6 text-center">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-brand bg-purple-50 group-hover:bg-brand group-hover:text-white px-3.5 py-1.5 rounded-full transition-all">
                            <i class="fa-solid fa-sparkles text-[10px]"></i>
                            <span><?php echo $remainingCount > 0 ? "+$remainingCount more features (Click card)" : "View all features"; ?></span>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-auto pt-2" onclick="event.stopPropagation()">
                    <a href="#contact"
                        class="<?php echo $isFeatured ? 'bg-gradient-to-r from-brand to-brand-blue text-white hover:shadow-brand/50' : 'bg-white border-2 border-brand text-brand hover:bg-brand hover:text-white'; ?> font-bold py-3 px-8 rounded-full shadow-lg hover:-translate-y-1 transition-all w-full block text-lg text-center">
                        Book Now
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php 
        $pageSize = 9;
        $totalPlans = count($activePlans);
        $totalPages = max(1, (int)ceil($totalPlans / $pageSize));
        ?>
        <!-- Pagination Bar (3 rows x 3 cards = 9 cards per page) -->
        <div class="mt-14 flex flex-col sm:flex-row items-center justify-center gap-4 relative z-20" id="pricingPaginationContainer">
            <div class="bg-white/80 backdrop-blur-md border border-white/90 p-2 rounded-full shadow-xl shadow-purple-500/5 inline-flex items-center gap-2" id="pricingPagination">
                <!-- Prev Button -->
                <button type="button" id="prevPricingPage" onclick="changePricingPage(currentPricingPage - 1)" 
                        class="px-4 py-2 rounded-full text-xs sm:text-sm font-extrabold text-gray-500 hover:text-brand hover:bg-purple-50 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-arrow-left text-xs"></i> <span>Prev</span>
                </button>

                <!-- Page Number Buttons -->
                <div class="flex items-center gap-1.5" id="pricingPageNumbers">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <button type="button" onclick="changePricingPage(<?php echo $p; ?>)" 
                                data-page-btn="<?php echo $p; ?>"
                                class="w-10 h-10 rounded-full text-sm font-extrabold transition-all flex items-center justify-center cursor-pointer <?php echo $p === 1 ? 'bg-gradient-to-r from-brand to-brand-blue text-white shadow-md shadow-brand/30 scale-105' : 'text-gray-700 hover:bg-purple-50 hover:text-brand'; ?>">
                            <?php echo $p; ?>
                        </button>
                    <?php endfor; ?>
                </div>

                <!-- Next Button -->
                <button type="button" id="nextPricingPage" onclick="changePricingPage(currentPricingPage + 1)" 
                        class="px-4 py-2 rounded-full text-xs sm:text-sm font-extrabold text-gray-500 hover:text-brand hover:bg-purple-50 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <span>Next</span> <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>

            <!-- Page Count Info Badge -->
            <div class="text-xs font-bold text-gray-600 bg-white/70 backdrop-blur-md px-5 py-2.5 rounded-full border border-white/80 shadow-md">
                Page <span id="currentPricingPageText" class="text-brand font-extrabold">1</span> of <span class="font-extrabold" id="totalPricingPagesText"><?php echo $totalPages; ?></span> (<span class="font-bold" id="totalPricingPlansText"><?php echo $totalPlans; ?></span> Total Plans &bull; 9/Page)
            </div>
        </div>

        <script>
            let currentPricingPage = 1;
            let currentCategoryFilter = 'all';
            let currentSearchTerm = '';
            const pageSize = 9;

            function onFeesSearchInput() {
                const searchInput = document.getElementById('feesSearchInput');
                const clearBtn = document.getElementById('feesSearchClearBtn');
                if (searchInput && clearBtn) {
                    if (searchInput.value.trim().length > 0) {
                        clearBtn.classList.remove('hidden');
                        clearBtn.classList.add('flex');
                    } else {
                        clearBtn.classList.add('hidden');
                        clearBtn.classList.remove('flex');
                    }
                }
                applyFeesFilter();
            }

            function clearFeesSearch() {
                const searchInput = document.getElementById('feesSearchInput');
                const clearBtn = document.getElementById('feesSearchClearBtn');
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                if (clearBtn) {
                    clearBtn.classList.add('hidden');
                    clearBtn.classList.remove('flex');
                }
                currentPricingPage = 1;
                applyFeesFilter();
            }

            function clearAllFeesFilters() {
                // Clear search input
                const searchInput = document.getElementById('feesSearchInput');
                const clearBtn = document.getElementById('feesSearchClearBtn');
                if (searchInput) searchInput.value = '';
                if (clearBtn) {
                    clearBtn.classList.add('hidden');
                    clearBtn.classList.remove('flex');
                }

                // Reset category to "all"
                const allPillBtn = document.querySelector('.fees-filter-pill[data-filter="all"]');
                setFeesCategory('all', allPillBtn);
            }

            function setFeesCategory(catName, btn) {
                currentCategoryFilter = (catName || 'all').toLowerCase().trim();
                currentPricingPage = 1;

                // If btn is not directly passed, locate the matching filter pill
                if (!btn && catName) {
                    const normTarget = (catName || 'all').toLowerCase().trim();
                    const pills = document.querySelectorAll('.fees-filter-pill');
                    pills.forEach(p => {
                        const df = (p.getAttribute('data-filter') || '').toLowerCase().trim();
                        if (df === normTarget) {
                            btn = p;
                        }
                    });
                }

                // Update active pill styling
                const pills = document.querySelectorAll('.fees-filter-pill');
                pills.forEach(p => {
                    p.className = 'fees-filter-pill inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-extrabold border transition-all cursor-pointer bg-white/80 text-gray-700 border-gray-200 hover:border-brand hover:text-brand hover:bg-purple-50';
                    const countSpan = p.querySelector('span');
                    if (countSpan) {
                        countSpan.className = 'ml-0.5 bg-black/10 px-1.5 py-0.5 rounded-full text-[10px] font-bold text-gray-700';
                    }
                });

                if (btn) {
                    btn.className = 'fees-filter-pill active-pill inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-extrabold border transition-all cursor-pointer bg-gradient-to-r from-brand to-brand-blue text-white border-transparent shadow-md shadow-brand/20';
                    const activeCountSpan = btn.querySelector('span');
                    if (activeCountSpan) {
                        activeCountSpan.className = 'ml-0.5 bg-white/30 text-white px-1.5 py-0.5 rounded-full text-[10px] font-bold';
                    }
                }

                applyFeesFilter();
            }

            // Redirect from Category Card to its Professional Fee in #fees section with highlight
            function redirectToCourseFee(catName, catId) {
                if (!catName) return;

                // 1. Clear search input if present
                const searchInput = document.getElementById('feesSearchInput');
                const clearBtn = document.getElementById('feesSearchClearBtn');
                if (searchInput) searchInput.value = '';
                if (clearBtn) {
                    clearBtn.classList.add('hidden');
                    clearBtn.classList.remove('flex');
                }

                // 2. Set Category filter
                setFeesCategory(catName);

                // 3. Smooth scroll down to #fees section
                const feesSec = document.getElementById('fees');
                if (feesSec) {
                    const yOffset = -30;
                    const y = feesSec.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                }

                // 4. Highlight the matching fee card(s) with an animated glowing pulse
                setTimeout(() => {
                    const normCat = (catName || '').toLowerCase().trim();
                    const allVisibleCards = Array.from(document.querySelectorAll('#pricingCardsGrid .pricing-card:not(.hidden)'));

                    let matchedCards = allVisibleCards.filter(c => {
                        const cCat = (c.getAttribute('data-category') || '').toLowerCase().trim();
                        const cId  = c.getAttribute('data-category-id');
                        return (catId && cId == catId) || (cCat === normCat);
                    });

                    if (matchedCards.length === 0) {
                        matchedCards = allVisibleCards;
                    }

                    matchedCards.forEach(card => {
                        card.classList.add(
                            'ring-4',
                            'ring-brand',
                            'shadow-[0_0_50px_rgba(157,78,221,0.55)]',
                            '!bg-white',
                            'scale-[1.03]'
                        );
                        setTimeout(() => {
                            card.classList.remove(
                                'ring-4',
                                'ring-brand',
                                'shadow-[0_0_50px_rgba(157,78,221,0.55)]',
                                '!bg-white',
                                'scale-[1.03]'
                            );
                        }, 3200);
                    });
                }, 550);
            }

            function applyFeesFilter() {
                const searchInput = document.getElementById('feesSearchInput');
                currentSearchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

                // Show/hide "Clear All" button if any filter or search is active
                const resetAllBtn = document.getElementById('feesResetAllBtn');
                if (resetAllBtn) {
                    const hasActiveFilter = (currentCategoryFilter !== 'all') || (currentSearchTerm.length > 0);
                    if (hasActiveFilter) {
                        resetAllBtn.classList.remove('hidden');
                        resetAllBtn.classList.add('inline-flex');
                    } else {
                        resetAllBtn.classList.add('hidden');
                        resetAllBtn.classList.remove('inline-flex');
                    }
                }

                const cards = Array.from(document.querySelectorAll('.pricing-card'));
                const matchedCards = [];

                cards.forEach(card => {
                    const cardCat = (card.getAttribute('data-category') || '').toLowerCase();
                    const cardName = (card.getAttribute('data-name') || '').toLowerCase();

                    const matchesCategory = (currentCategoryFilter === 'all') || (cardCat === currentCategoryFilter);
                    const matchesSearch = !currentSearchTerm || cardName.includes(currentSearchTerm);

                    if (matchesCategory && matchesSearch) {
                        matchedCards.push(card);
                    } else {
                        card.classList.add('hidden');
                        card.classList.remove('animate-fadeIn');
                    }
                });

                // Handle no results
                const noResults = document.getElementById('feesNoResults');
                const pagContainer = document.getElementById('pricingPaginationContainer');
                
                if (matchedCards.length === 0) {
                    if (noResults) noResults.classList.remove('hidden');
                    if (pagContainer) pagContainer.classList.add('hidden');
                    return;
                } else {
                    if (noResults) noResults.classList.add('hidden');
                    if (pagContainer) pagContainer.classList.remove('hidden');
                }

                // Recalculate pages
                const totalFilteredPages = Math.max(1, Math.ceil(matchedCards.length / pageSize));
                if (currentPricingPage > totalFilteredPages) {
                    currentPricingPage = 1;
                }

                // Show only current page of matched cards
                matchedCards.forEach((card, index) => {
                    const cardPage = Math.floor(index / pageSize) + 1;
                    if (cardPage === currentPricingPage) {
                        card.classList.remove('hidden');
                        card.classList.add('animate-fadeIn');
                    } else {
                        card.classList.add('hidden');
                        card.classList.remove('animate-fadeIn');
                    }
                });

                // Render dynamic pagination buttons
                renderPagination(totalFilteredPages, matchedCards.length);
            }

            function renderPagination(totalPages, totalCount) {
                const pagContainer = document.getElementById('pricingPaginationContainer');
                if (!pagContainer) return;

                // Ensure pagination is always visible when plans exist
                pagContainer.classList.remove('hidden');

                const numbersContainer = document.getElementById('pricingPageNumbers');
                if (numbersContainer) {
                    numbersContainer.innerHTML = '';
                    for (let p = 1; p <= totalPages; p++) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.onclick = () => changePricingPage(p);
                        btn.setAttribute('data-page-btn', p);
                        btn.className = (p === currentPricingPage)
                            ? 'w-10 h-10 rounded-full text-sm font-extrabold transition-all flex items-center justify-center cursor-pointer bg-gradient-to-r from-brand to-brand-blue text-white shadow-md shadow-brand/30 scale-105'
                            : 'w-10 h-10 rounded-full text-sm font-extrabold transition-all flex items-center justify-center cursor-pointer text-gray-700 hover:bg-purple-50 hover:text-brand';
                        btn.innerText = p;
                        numbersContainer.appendChild(btn);
                    }
                }

                const prevBtn = document.getElementById('prevPricingPage');
                const nextBtn = document.getElementById('nextPricingPage');
                if (prevBtn) prevBtn.disabled = (currentPricingPage <= 1);
                if (nextBtn) nextBtn.disabled = (currentPricingPage >= totalPages);

                const currentText = document.getElementById('currentPricingPageText');
                const totalText = document.getElementById('totalPricingPagesText');
                const countText = document.getElementById('totalPricingPlansText');
                if (currentText) currentText.innerText = currentPricingPage;
                if (totalText) totalText.innerText = totalPages;
                if (countText) countText.innerText = totalCount;
            }

            function changePricingPage(targetPage) {
                currentPricingPage = targetPage;
                applyFeesFilter();

                // Smoothly scroll to fees top
                const feesSec = document.getElementById('fees');
                if (feesSec) {
                    const yOffset = -50;
                    const y = feesSec.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                }
            }

            // Initialize button states
            document.addEventListener('DOMContentLoaded', () => {
                applyFeesFilter();
            });

            // Interactive Plan Details Modal Handler (Shows full features without expanding card height)
            function openPlanDetailsModal(plan) {
                const modal = document.getElementById('planDetailsModal');
                const card = document.getElementById('planDetailsModalCard');
                if (!modal || !card) return;

                document.getElementById('planModalTitle').innerText = plan.plan_name;
                document.getElementById('planModalCategory').innerText = plan.category_name;
                document.getElementById('planModalDuration').innerText = plan.course_duration;

                const priceContainer = document.getElementById('planModalPriceContainer');
                if (plan.has_discount) {
                    priceContainer.innerHTML = `
                        <div class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue font-outfit">
                            ₹${plan.display_price}
                        </div>
                        <div class="flex items-center justify-center gap-2 mt-1">
                            <span class="text-sm font-semibold text-gray-400 line-through">₹${plan.price}</span>
                            <span class="text-[11px] font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Save ${plan.save_pct}%</span>
                        </div>
                    `;
                } else {
                    priceContainer.innerHTML = `
                        <div class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue font-outfit">
                            ₹${plan.display_price}
                        </div>
                        <p class="text-gray-400 font-medium text-[11px] mt-0.5">Standard Fee</p>
                    `;
                }

                // Populate all features
                const list = document.getElementById('planModalFeaturesList');
                list.innerHTML = '';
                if (plan.features && plan.features.length > 0) {
                    plan.features.forEach(feat => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center gap-2.5 text-gray-700 font-medium text-xs leading-snug bg-white/70 p-2.5 rounded-xl border border-gray-100 shadow-2xs';
                        li.innerHTML = `
                            <span class="w-4 h-4 rounded-full bg-brand/10 text-brand flex items-center justify-center text-[10px] shrink-0 font-bold">
                                <i class="fa-solid fa-check text-[9px]"></i>
                            </span>
                            <span>${escapeHtml(feat)}</span>
                        `;
                        list.appendChild(li);
                    });
                } else {
                    list.innerHTML = '<p class="text-gray-400 italic text-sm text-center py-4">Full syllabus and curriculum provided upon enrollment.</p>';
                }

                // Show modal
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    card.classList.remove('scale-95');
                    card.classList.add('scale-100');
                }, 10);
            }

            function closePlanDetailsModal() {
                const modal = document.getElementById('planDetailsModal');
                const card = document.getElementById('planDetailsModalCard');
                if (!modal || !card) return;

                modal.classList.add('opacity-0');
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
                document.body.style.overflow = '';
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }

            // Interactive Category Packages Modal Handler
            function openCategoryPackagesModal(catId) {
                const modal = document.getElementById('categoryPackagesModal');
                const card  = document.getElementById('categoryPackagesModalCard');
                if (!modal || !card) { console.warn('categoryPackagesModal not found in DOM'); return; }

                const data = (window.categoryPackagesData || {})[catId];
                if (!data) { console.warn('No data for catId', catId); return; }

                document.getElementById('catModalTitle').innerText    = data.category_name;
                document.getElementById('catModalPkgCount').innerText = data.packages.length + ' Package' + (data.packages.length === 1 ? '' : 's') + ' Available';

                const container = document.getElementById('catModalPackagesGrid');
                container.innerHTML = '';

                if (data.packages && data.packages.length > 0) {
                    data.packages.forEach((pkg) => {
                        const pkgCard = document.createElement('div');
                        pkgCard.className = 'p-5 rounded-3xl bg-white border border-gray-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between';
                        
                        let priceHtml = '';
                        if (pkg.has_discount) {
                            priceHtml = `
                                <div class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue font-outfit">
                                    ₹${pkg.display_price}
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-sm font-semibold text-gray-400 line-through">₹${pkg.price}</span>
                                    <span class="text-[11px] font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Save ${pkg.save_pct}%</span>
                                </div>
                            `;
                        } else {
                            priceHtml = `
                                <div class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue font-outfit">
                                    ₹${pkg.display_price}
                                </div>
                                <span class="text-xs text-gray-400 font-medium">Standard Fee</span>
                            `;
                        }

                        // Feature bullet points from description
                        let featsHtml = '';
                        if (pkg.description && pkg.description.trim() !== '') {
                            const lines = pkg.description.split(/\r\n|\r|\n/).filter(l => l.trim() !== '');
                            if (lines.length > 0) {
                                featsHtml = '<ul class="space-y-1.5 my-3 text-xs text-gray-600 font-medium">';
                                lines.slice(0, 4).forEach(l => {
                                    featsHtml += `
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-check text-brand text-[10px] mt-0.5 shrink-0"></i>
                                            <span>${escapeHtml(l)}</span>
                                        </li>
                                    `;
                                });
                                featsHtml += '</ul>';
                            }
                        }

                        const safePlanName = escapeHtml(pkg.plan_name).replace(/'/g, "\\'");
                        pkgCard.innerHTML = `
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full flex items-center gap-1">
                                        <i class="fa-regular fa-clock"></i> ${escapeHtml(pkg.course_duration)}
                                    </span>
                                </div>
                                <h4 class="text-lg font-extrabold text-gray-900 mb-2">${escapeHtml(pkg.plan_name)}</h4>
                                ${priceHtml}
                                ${featsHtml}
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-100 flex flex-col gap-2">
                                <a href="#contact" onclick="selectCourseAndEnroll('${safePlanName}'); closeCategoryPackagesModal();"
                                   class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-brand to-brand-blue text-white font-extrabold text-xs shadow-md shadow-brand/20 hover:shadow-brand/40 transition-all block text-center">
                                    Enroll in Package
                                </a>
                                <button type="button" onclick="closeCategoryPackagesModal(); redirectToCourseFee('${escapeHtml(data.category_name).replace(/'/g, "\\'")}', ${catId});"
                                   class="w-full py-2 px-3 rounded-xl bg-purple-50 hover:bg-purple-100 text-brand font-bold text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-arrow-down text-[10px]"></i> View in Professional Course Fees
                                </button>
                            </div>
                        `;
                        container.appendChild(pkgCard);
                    });
                } else {
                    container.innerHTML = `
                        <div class="col-span-full text-center py-12 bg-purple-50/40 rounded-3xl border border-purple-100/60 p-6">
                            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-brand flex items-center justify-center mx-auto mb-3 text-lg">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>
                            <h4 class="font-extrabold text-gray-900 text-base mb-1">Pricing Packages Coming Soon</h4>
                            <p class="text-xs text-gray-500 max-w-sm mx-auto mb-4">Packages for this category are being updated. Contact us to get customized pricing.</p>
                            <a href="#contact" onclick="closeCategoryPackagesModal()" class="inline-block py-2.5 px-6 rounded-full bg-brand text-white font-bold text-xs shadow-md shadow-brand/25">
                                Contact for Details
                            </a>
                        </div>
                    `;
                }

                // Show modal with animation
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    card.classList.remove('scale-95');
                    card.classList.add('scale-100');
                }, 10);
            }

            function closeCategoryPackagesModal() {
                const modal = document.getElementById('categoryPackagesModal');
                const card = document.getElementById('categoryPackagesModalCard');
                if (!modal || !card) return;

                modal.classList.add('opacity-0');
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
                document.body.style.overflow = '';
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }

            function selectCourseAndEnroll(courseName) {
                const selectEl = document.querySelector('select[name="course"]');
                if (selectEl) {
                    // Try to match option
                    let found = false;
                    for (let i = 0; i < selectEl.options.length; i++) {
                        if (selectEl.options[i].text.includes(courseName) || selectEl.options[i].value.includes(courseName)) {
                            selectEl.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        </script>

        <!-- Interactive Course Plan Features Modal Popup -->
        <div id="planDetailsModal" class="fixed inset-0 z-[200] flex items-center justify-center p-3 sm:p-4 pt-12 sm:pt-16 bg-slate-900/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300 overflow-y-auto" onclick="if(event.target === this) closePlanDetailsModal()">
            <div id="planDetailsModalCard" class="bg-white rounded-3xl shadow-2xl border border-gray-100 w-full max-w-sm sm:max-w-md overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[80vh] my-auto">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-50/50 via-white to-blue-50/30">
                    <div>
                        <div class="inline-block bg-purple-100/70 text-brand py-0.5 px-3 rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-1" id="planModalCategory">
                            Course
                        </div>
                        <h3 class="text-xl font-outfit font-extrabold text-gray-900 leading-tight" id="planModalTitle">
                            Plan Details
                        </h3>
                        <div class="flex items-center gap-1.5 mt-0.5 text-xs font-semibold text-gray-500">
                            <i class="fa-regular fa-clock text-brand text-[11px]"></i>
                            <span id="planModalDuration">Duration</span>
                        </div>
                    </div>
                    <button type="button" onclick="closePlanDetailsModal()" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex items-center justify-center transition-all cursor-pointer">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Modal Body (All Features with Smooth Scroll) -->
                <div class="p-5 overflow-y-auto flex-1 space-y-4">
                    <div id="planModalPriceContainer" class="p-3.5 rounded-xl bg-gradient-to-r from-purple-50/60 to-blue-50/40 border border-purple-100/70 text-center">
                    </div>

                    <div>
                        <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 text-center">
                            Everything Included In This Course
                        </h4>
                        <ul id="planModalFeaturesList" class="space-y-2">
                        </ul>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3.5 bg-gray-50/80 border-t border-gray-100 flex items-center gap-2.5">
                    <button type="button" onclick="closePlanDetailsModal()" class="flex-1 py-2.5 px-4 rounded-xl bg-white border border-gray-200 text-gray-700 font-bold text-xs hover:bg-gray-100 transition-all text-center">
                        Close
                    </button>
                    <a href="#contact" onclick="closePlanDetailsModal()" class="flex-1 py-2.5 px-4 rounded-xl bg-gradient-to-r from-brand to-brand-blue text-white font-extrabold text-xs shadow-md shadow-brand/30 hover:shadow-brand/50 hover:-translate-y-0.5 transition-all text-center">
                        Book Now
                    </a>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Fallback: no active plans — show static original cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-w-4xl mx-auto">
            <!-- 3 Months Card -->
            <div class="glass-card bg-white/70" data-aos="flip-left">
                <div class="mb-8">
                    <h3 class="text-3xl font-extrabold text-gray-900 mb-4">3 Months Course</h3>
                    <div
                        class="inline-block bg-brand/10 text-brand py-1.5 px-5 rounded-full text-sm font-bold tracking-wide mb-6 border border-brand/20 uppercase">
                        Flexible Payments</div>
                </div>
                <div class="space-y-3 mb-8 text-left max-w-xs mx-auto">
                    <div class="flex justify-between items-center bg-white/50 p-3 rounded-xl border border-white">
                        <span class="font-semibold text-gray-600">First Month:</span>
                        <span class="font-bold text-brand">₹5,000</span>
                    </div>
                    <div class="flex justify-between items-center bg-white/50 p-3 rounded-xl border border-white">
                        <span class="font-semibold text-gray-600">Second Month:</span>
                        <span class="font-bold text-brand">₹2,500</span>
                    </div>
                    <div class="flex justify-between items-center bg-white/50 p-3 rounded-xl border border-white">
                        <span class="font-semibold text-gray-600">Third Month:</span>
                        <span class="font-bold text-brand">₹2,500</span>
                    </div>
                </div>
                <a href="#contact"
                    class="bg-white border-2 border-brand text-brand font-bold py-3 px-8 rounded-full shadow-lg hover:bg-brand hover:text-white transition-all w-full block text-lg">Book Now</a>
            </div>

            <!-- 6 Months Card -->
            <div class="glass-card bg-white/90 border border-brand/40 shadow-[0_10px_40px_rgba(157,78,221,0.2)] transform md:-translate-y-4"
                data-aos="flip-right">
                <div
                    class="absolute -top-1 right-1/2 translate-x-1/2 bg-gradient-to-r from-brand to-brand-blue text-white px-8 py-2 rounded-b-2xl text-sm font-extrabold shadow-lg z-10 w-max uppercase tracking-wider">
                    Best Value
                </div>
                <div class="mb-8 mt-6">
                    <h3 class="text-3xl font-extrabold text-gray-900 mb-4">5 Months Course</h3>
                    <div
                        class="inline-block bg-brand-blue/10 text-brand-blue py-1.5 px-5 rounded-full text-sm font-bold tracking-wide mb-6 border border-brand-blue/20">
                        In-Depth Mastery</div>
                </div>
                <div
                    class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue mb-2 font-outfit drop-shadow-sm">
                    ₹2,000<span
                        class="text-xl font-bold text-gray-500 leading-none inline-block align-middle pb-2">/mo</span>
                </div>
                <p class="text-gray-600 font-medium mt-6 mb-8 h-12">Deep dive into advanced strategies and hands-on
                    industry projects.</p>
                <a href="#contact"
                    class="bg-gradient-to-r from-brand to-brand-blue text-white font-bold py-3 px-8 rounded-full shadow-lg hover:shadow-brand/50 hover:-translate-y-1 transition-all w-full block text-lg border border-transparent">Book Now</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-24 max-w-5xl mx-auto">
            <h3 class="text-3xl font-extrabold mb-10 text-gray-900">Why Choose <span class="text-gradient">Technodome?</span></h3>
            <div class="flex flex-wrap justify-center gap-6">
                <div class="bg-white/80 border border-white/60 shadow-md px-8 py-4 rounded-full text-lg font-semibold text-gray-800 backdrop-blur-sm"
                    data-aos="fade-up" data-aos-delay="100"><i class="fa-solid fa-building text-brand mr-2"></i>
                    Qualified &amp; Experienced Faculty</div>
                <div class="bg-white/80 border border-white/60 shadow-md px-8 py-4 rounded-full text-lg font-semibold text-gray-800 backdrop-blur-sm"
                    data-aos="fade-up" data-aos-delay="200"><i class="fa-solid fa-face-smile text-brand-blue mr-2"></i>
                    Industry-focused practical training</div>
                <div class="bg-white/80 border border-white/60 shadow-md px-8 py-4 rounded-full text-lg font-semibold text-gray-800 backdrop-blur-sm"
                    data-aos="fade-up" data-aos-delay="300"><i
                        class="fa-solid fa-money-bill-trend-up text-brand-pink mr-2"></i> Income-oriented learning system</div>
                <div class="bg-white/80 border border-white/60 shadow-md px-8 py-4 rounded-full text-lg font-semibold text-gray-800 backdrop-blur-sm"
                    data-aos="fade-up" data-aos-delay="400"><i class="fa-solid fa-headset text-brand mr-2"></i> Freelance &amp; Job Opportunities</div>
            </div>
        </div>
    </section>

    <!-- Lead Form — Course dropdown is dynamic -->
    <section id="contact" class="py-24 px-4 sm:px-6 md:px-12 lg:px-24 relative z-10">
        <div class="text-center max-w-3xl mx-auto mb-12 md:mb-16 px-4 bg-white/40 p-8 md:p-10 rounded-3xl backdrop-blur-md border border-white/60 shadow-xl"
            data-aos="fade-up">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4 text-gray-900">Start Your <span
                    class="text-gradient">Journey</span> Today</h2>
            <p class="text-xl text-gray-700 font-medium">Don't just scroll social media — learn how to earn from it! <br> Limited Seats Available. Enroll Now &amp; Transform Your Future.</p>
        </div>

        <div class="max-w-3xl mx-auto w-full bg-white/80 border border-white p-6 sm:p-8 md:p-12 rounded-3xl md:rounded-[3rem] backdrop-blur-xl shadow-2xl relative z-10"
            data-aos="zoom-in" data-aos-delay="200">
            <form id="leadForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fullName" class="block text-gray-800 font-bold mb-2">Full Name</label>
                        <input type="text" id="fullName" name="name"
                            class="w-full bg-white border border-gray-200 text-gray-900 p-4 rounded-2xl transition-all font-inter placeholder:text-gray-400 focus:border-brand focus:ring-4 focus:ring-brand/10 outline-none shadow-inner"
                            placeholder="John Doe" required>
                    </div>
                    <div>
                        <label for="mobile" class="block text-gray-800 font-bold mb-2">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile"
                            class="w-full bg-white border border-gray-200 text-gray-900 p-4 rounded-2xl transition-all font-inter placeholder:text-gray-400 focus:border-brand focus:ring-4 focus:ring-brand/10 outline-none shadow-inner"
                            placeholder="+91 0000 000000" required>
                    </div>
                </div>

                <div>
                    <label for="location" class="block text-gray-800 font-bold mb-2">City / Location</label>
                    <input type="text" id="location" name="location"
                        class="w-full bg-white border border-gray-200 text-gray-900 p-4 rounded-2xl transition-all font-inter placeholder:text-gray-400 focus:border-brand focus:ring-4 focus:ring-brand/10 outline-none shadow-inner"
                        placeholder="Enter your city" required>
                </div>

                <div>
                    <label for="course" class="block text-gray-800 font-bold mb-2">Interested Course</label>
                    <div class="relative">
                        <select id="course" name="course"
                            class="w-full bg-white border border-gray-200 text-gray-900 p-4 rounded-2xl transition-all font-inter appearance-none focus:border-brand focus:ring-4 focus:ring-brand/10 outline-none shadow-inner"
                            required>
                            <option value="" disabled selected class="text-gray-500">Select a course</option>
                            <?php if (!empty($activePlans)): ?>
                                <?php foreach ($activePlans as $plan): ?>
                                <option value="<?php echo htmlspecialchars($plan['plan_name']); ?>" class="text-gray-900">
                                    <?php echo htmlspecialchars($plan['plan_name']); ?>
                                    <?php if (!empty($plan['course_duration'])): ?> (<?php echo htmlspecialchars($plan['course_duration']); ?>)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            <?php elseif (!empty($activeCategories)): ?>
                                <?php foreach ($activeCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_name']); ?>" class="text-gray-900">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="3 Months Course" class="text-gray-900">3 Months Course</option>
                                <option value="5 Months Course" class="text-gray-900">5 Months Course</option>
                            <?php endif; ?>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-5 text-gray-500">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                    <div class="bg-gray-50 p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <label class="block text-gray-800 font-bold mb-3 sm:mb-4 text-sm sm:text-base">Have Basic
                            Computer Knowledge?</label>
                        <div class="flex flex-wrap gap-4 sm:gap-6 w-full">
                            <label
                                class="flex items-center gap-2 sm:gap-3 cursor-pointer text-gray-800 font-medium text-sm sm:text-base">
                                <input type="radio" name="computerKnowledge" value="Yes"
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-brand focus:ring-brand shrink-0" required>
                                <span class="select-none inline-block mt-0.5">Yes</span>
                            </label>
                            <label
                                class="flex items-center gap-2 sm:gap-3 cursor-pointer text-gray-800 font-medium text-sm sm:text-base">
                                <input type="radio" name="computerKnowledge" value="No"
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-brand focus:ring-brand shrink-0">
                                <span class="select-none inline-block mt-0.5">No</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <label class="block text-gray-800 font-bold mb-3 sm:mb-4 text-sm sm:text-base">Preferred
                            Learning Mode</label>
                        <div class="flex flex-wrap gap-4 sm:gap-6 w-full">
                            <label
                                class="flex items-center gap-2 sm:gap-3 cursor-pointer text-gray-800 font-medium text-sm sm:text-base">
                                <input type="radio" name="learningMode" value="Online"
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-brand focus:ring-brand shrink-0" required>
                                <span class="select-none inline-block mt-0.5">Online</span>
                            </label>
                            <label
                                class="flex items-center gap-2 sm:gap-3 cursor-pointer text-gray-800 font-medium text-sm sm:text-base">
                                <input type="radio" name="learningMode" value="Offline"
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-brand focus:ring-brand shrink-0">
                                <span class="select-none inline-block mt-0.5">Offline</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="pt-8">
                    <button type="submit"
                        class="bg-gradient-to-r from-brand to-brand-blue text-white font-extrabold w-full py-5 text-xl rounded-2xl shadow-xl shadow-brand/30 hover:shadow-brand/50 hover:-translate-y-1 transition-all flex justify-center items-center gap-3 border outline-none">
                        <span>Book Now</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
                <div id="leadFormMessage" class="hidden font-semibold text-center pt-4 transition-all"></div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer
        class="relative mt-20 pt-16 pb-8 overflow-hidden font-inter border-t border-brand/20 bg-white shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)] z-10 text-gray-800">
        <!-- Vibrant Sub-Background for Footer text -->
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-pink-50 opacity-90 z-0"></div>
        <div class="absolute w-full h-1.5 bg-gradient-to-r from-brand-blue via-brand to-brand-pink top-0 left-0"></div>

        <div class="max-w-7xl mx-auto px-6 md:px-12 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
                <!-- Column 1: Brand -->
                <div class="col-span-1 lg:col-span-1">
                    <a href="https://technodome.in" class="flex items-center gap-4 mb-6 decoration-transparent inline-flex">
                        <img src="logo.png" alt="Technodome Logo" class="h-12 md:h-14 w-auto object-contain">
                    </a>
                    <p class="text-gray-600 font-medium text-sm mb-6 leading-relaxed">
                        Become a Professional in Social Media Marketing &amp; Start Earning. Build Skills. Grow Online. Earn Smart. <br>
                        <a href="https://technodome.in" target="_blank" class="text-brand font-bold hover:underline">Visit Official Site: technodome.in</a>
                    </p>
                    <div class="flex space-x-4">
                        <a href="#"
                            class="w-12 h-12 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-white hover:bg-brand hover:border-transparent hover:scale-110 transition-all shadow-sm hover:shadow-lg">
                            <i class="fa-brands fa-facebook-f text-lg"></i>
                        </a>
                        <a href="#"
                            class="w-12 h-12 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-white hover:bg-brand-pink hover:border-transparent hover:scale-110 transition-all shadow-sm hover:shadow-lg">
                            <i class="fa-brands fa-instagram text-lg"></i>
                        </a>
                        <a href="#"
                            class="w-12 h-12 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-white hover:bg-red-500 hover:border-transparent hover:scale-110 transition-all shadow-sm hover:shadow-lg">
                            <i class="fa-brands fa-youtube text-lg"></i>
                        </a>
                        <a href="#"
                            class="w-12 h-12 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-white hover:bg-blue-600 hover:border-transparent hover:scale-110 transition-all shadow-sm hover:shadow-lg">
                            <i class="fa-brands fa-linkedin-in text-lg"></i>
                        </a>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div>
                    <h4
                        class="text-xl font-bold mb-6 font-outfit text-gray-900 border-b-2 border-brand/20 pb-2 inline-block">
                        Quick Links</h4>
                    <ul class="space-y-3 font-medium">
                        <li><a href="#home"
                                class="text-gray-600 hover:text-brand hover:translate-x-2 transition-transform block"><i
                                    class="fa-solid fa-angle-right text-brand mr-2 text-xs"></i> Home</a></li>
                        <li><a href="#learn"
                                class="text-gray-600 hover:text-brand hover:translate-x-2 transition-transform block"><i
                                    class="fa-solid fa-angle-right text-brand mr-2 text-xs"></i> What You Learn</a></li>
                        <li><a href="#earn"
                                class="text-gray-600 hover:text-brand hover:translate-x-2 transition-transform block"><i
                                    class="fa-solid fa-angle-right text-brand mr-2 text-xs"></i> Earning Potential</a>
                        </li>
                        <li><a href="#fees"
                                class="text-gray-600 hover:text-brand hover:translate-x-2 transition-transform block"><i
                                    class="fa-solid fa-angle-right text-brand mr-2 text-xs"></i> Course Options</a></li>
                        <li><a href="#contact"
                                class="text-gray-600 hover:text-brand hover:translate-x-2 transition-transform block"><i
                                    class="fa-solid fa-angle-right text-brand mr-2 text-xs"></i> Book Workshop</a></li>
                        <li><a href="https://technodome.in" target="_blank"
                                class="text-gray-600 hover:text-brand hover:translate-x-2 transition-transform block font-bold text-brand-blue"><i
                                    class="fa-solid fa-globe text-brand-blue mr-2 text-xs"></i> Official Site</a></li>
                    </ul>
                </div>

                <!-- Column 3: Contact Info -->
                <div class="lg:col-span-2">
                    <h4
                        class="text-xl font-bold mb-6 font-outfit text-gray-900 border-b-2 border-brand/20 pb-2 inline-block">
                        Connect With Us</h4>
                    <div class="bg-white/80 p-8 rounded-3xl border border-gray-200 shadow-md">
                        <div class="grid sm:grid-cols-2 gap-8">
                            <!-- Phone -->
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-14 h-14 rounded-2xl bg-brand/10 flex items-center justify-center shrink-0 border border-brand/20">
                                    <i class="fa-solid fa-phone text-brand text-2xl"></i>
                                </div>
                                <div class="pt-1">
                                    <p class="text-gray-500 font-semibold text-sm mb-1">Call / WhatsApp</p>
                                    <a href="tel:+916297418660"
                                        class="text-lg font-bold text-gray-900 hover:text-brand transition-colors block leading-tight">+91
                                        62974 18660</a>
                                    <a href="tel:+919875372267"
                                        class="text-lg font-bold text-gray-900 hover:text-brand transition-colors block leading-tight mt-1">+91
                                        98753 72267</a>
                                </div>
                            </div>
                            <!-- Email -->
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-14 h-14 rounded-2xl bg-brand-blue/10 flex items-center justify-center shrink-0 border border-brand-blue/20">
                                    <i class="fa-solid fa-envelope text-brand-blue text-2xl"></i>
                                </div>
                                <div class="pt-1 break-all">
                                    <p class="text-gray-500 font-semibold text-sm mb-1">Email Support</p>
                                    <a href="mailto:info@technodome.in"
                                        class="text-base font-bold text-gray-900 hover:text-brand-blue transition-colors block leading-tight mt-1">info@technodome.in</a>
                                </div>
                            </div>
                            <!-- Address -->
                            <div class="flex items-start gap-4 sm:col-span-2 pt-6 border-t border-gray-100">
                                <div
                                    class="w-14 h-14 rounded-2xl bg-brand-pink/10 flex items-center justify-center shrink-0 border border-brand-pink/20">
                                    <i class="fa-solid fa-location-dot text-brand-pink text-2xl"></i>
                                </div>
                                <div class="pt-1">
                                    <p class="text-gray-500 font-semibold text-sm mb-1">Office Address</p>
                                    <p
                                        class="text-[15px] sm:text-base font-bold text-gray-900 leading-relaxed max-w-lg">
                                        83, M.B. Road Belgharia, Kolkata - 700049 <br>Near Nimta Bazar, Landmark: Mio Amore Cake Shop
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div
                class="text-center pt-8 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-600 font-medium text-sm">&copy; 2026 Technodome. All Rights Reserved.</p>
                <div class="flex space-x-6 text-sm font-semibold text-gray-600">
                    <a href="#" class="hover:text-brand transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-brand transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            offset: 80,
            duration: 800
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>

    <!-- Category Packages Data (server-rendered, keyed by category ID) -->
    <script>
        window.categoryPackagesData = <?php echo json_encode($categoryModalDataMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
    </script>

    <!-- Enquiry Modal Popup -->
    <div id="enquiryModal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-500 p-2 sm:p-4">
        <div id="enquiryModalInner"
            class="bg-white rounded-3xl md:rounded-[2rem] shadow-2xl w-full max-w-2xl mx-auto relative transform scale-95 transition-transform duration-500 overflow-y-auto max-h-[95vh] border border-gray-100 p-6 sm:p-8 md:p-12">
            <button id="closeModalBtn"
                class="absolute top-4 right-4 md:top-6 md:right-6 w-10 h-10 bg-gray-50 border border-gray-100 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-brand hover:scale-110 transition-all z-10">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>

            <h3 class="text-3xl font-extrabold mb-2 text-gray-900 text-center">Enroll <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-brand to-brand-blue">Now</span></h3>
            <p class="text-gray-600 text-center mb-8 font-medium">Limited Seats Available! Secure your spot by filling
                out the form below.</p>

            <form id="popupForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-800 font-bold mb-1">Full Name</label>
                        <input type="text" name="name"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-900 p-3 rounded-xl transition-all font-inter focus:border-brand outline-none"
                            placeholder="John Doe" required>
                    </div>
                    <div>
                        <label class="block text-gray-800 font-bold mb-1">Mobile Number</label>
                        <input type="tel" name="mobile"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-900 p-3 rounded-xl transition-all font-inter focus:border-brand outline-none"
                            placeholder="+91 0000 000000" required>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-800 font-bold mb-1">City / Location</label>
                    <input type="text" name="location"
                        class="w-full bg-gray-50 border border-gray-200 text-gray-900 p-3 rounded-xl transition-all font-inter focus:border-brand outline-none"
                        placeholder="Enter your city" required>
                </div>

                <div>
                    <label class="block text-gray-800 font-bold mb-1">Interested Course</label>
                    <div class="relative">
                        <select name="course"
                            class="w-full bg-gray-50 border border-gray-200 text-gray-900 p-3 rounded-xl transition-all font-inter appearance-none focus:border-brand outline-none"
                            required>
                            <option value="" disabled selected class="text-gray-500">Select a course</option>
                            <?php if (!empty($activePlans)): ?>
                                <?php foreach ($activePlans as $plan): ?>
                                <option value="<?php echo htmlspecialchars($plan['plan_name']); ?>">
                                    <?php echo htmlspecialchars($plan['plan_name']); ?>
                                    <?php if (!empty($plan['course_duration'])): ?> (<?php echo htmlspecialchars($plan['course_duration']); ?>)<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            <?php elseif (!empty($activeCategories)): ?>
                                <?php foreach ($activeCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="3 Months Course">3 Months Course</option>
                                <option value="5 Months Course">5 Months Course</option>
                            <?php endif; ?>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <div class="bg-gray-50 p-3 sm:p-4 rounded-xl border border-gray-100 shadow-sm">
                        <label class="block text-gray-800 font-bold mb-2 text-sm">Basic Computer Knowledge?</label>
                        <div class="flex flex-wrap gap-x-4 gap-y-2 w-full">
                            <label class="flex items-center gap-2 cursor-pointer text-gray-800 text-sm">
                                <input type="radio" name="computerKnowledge" value="Yes"
                                    class="w-4 h-4 text-brand focus:ring-brand shrink-0" required>
                                <span class="select-none inline-block mt-0.5">Yes</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-gray-800 text-sm">
                                <input type="radio" name="computerKnowledge" value="No"
                                    class="w-4 h-4 text-brand focus:ring-brand shrink-0">
                                <span class="select-none inline-block mt-0.5">No</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 sm:p-4 rounded-xl border border-gray-100 shadow-sm">
                        <label class="block text-gray-800 font-bold mb-2 text-sm">Preferred Learning Mode</label>
                        <div class="flex flex-wrap gap-x-4 gap-y-2 w-full">
                            <label class="flex items-center gap-2 cursor-pointer text-gray-800 text-sm">
                                <input type="radio" name="learningMode" value="Online"
                                    class="w-4 h-4 text-brand focus:ring-brand shrink-0" required>
                                <span class="select-none inline-block mt-0.5">Online</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-gray-800 text-sm">
                                <input type="radio" name="learningMode" value="Offline"
                                    class="w-4 h-4 text-brand focus:ring-brand shrink-0">
                                <span class="select-none inline-block mt-0.5">Offline</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="bg-gradient-to-r from-brand to-brand-blue text-white font-extrabold w-full py-3.5 text-lg rounded-xl shadow-lg hover:shadow-brand/40 hover:-translate-y-1 transition-all flex justify-center items-center gap-3 border outline-none">
                        <span>Confirm Booking</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
                <div id="popupFormMessage" class="hidden font-semibold text-center pt-2 transition-all"></div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile Menu Logic
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenuIcon = mobileMenuBtn.querySelector('i');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileLinks = document.querySelectorAll('.mobile-link');
            
            function toggleMenu() {
                const isOpen = mobileMenu.classList.contains('translate-y-0');
                if (isOpen) {
                    mobileMenu.classList.remove('translate-y-0');
                    mobileMenu.classList.add('-translate-y-[150%]');
                    mobileMenuIcon.classList.remove('fa-xmark');
                    mobileMenuIcon.classList.add('fa-bars');
                } else {
                    mobileMenu.classList.remove('-translate-y-[150%]');
                    mobileMenu.classList.add('translate-y-0');
                    mobileMenuIcon.classList.remove('fa-bars');
                    mobileMenuIcon.classList.add('fa-xmark');
                }
            }

            mobileMenuBtn.addEventListener('click', toggleMenu);
            
            mobileLinks.forEach(link => {
                link.addEventListener('click', toggleMenu);
            });

            // Enquiry Modal Logic
            const modal = document.getElementById('enquiryModal');
            const modalInner = document.getElementById('enquiryModalInner');
            const closeBtn = document.getElementById('closeModalBtn');

            function openModal() {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modal.classList.add('opacity-100');
                modalInner.classList.remove('scale-95');
                modalInner.classList.add('scale-100');
            }

            function closeModal() {
                modal.classList.remove('opacity-100');
                modal.classList.add('opacity-0', 'pointer-events-none');
                modalInner.classList.remove('scale-100');
                modalInner.classList.add('scale-95');
            }

            // Auto-trigger on page load with 5.5s delay
            setTimeout(openModal, 5500);

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            // Form Submission Handler
            const handleFormSubmit = async (formId, msgId) => {
                const form = document.getElementById(formId);
                const messageBox = document.getElementById(msgId);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.querySelector('span').innerText;

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    
                    // State: Sending
                    submitBtn.disabled = true;
                    submitBtn.querySelector('span').innerText = "Sending...";
                    messageBox.classList.add('hidden');

                    const formData = new FormData(form);

                    try {
                        const response = await fetch('send_enquiry.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (!response.ok) {
                            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                        }

                        const result = await response.json();

                        if (result.status === 'success') {
                            messageBox.innerText = result.message;
                            messageBox.className = "font-semibold text-center pt-4 transition-all text-green-600 block";
                            form.reset();
                            if(formId === 'popupForm') {
                                setTimeout(closeModal, 2000);
                            }
                        } else {
                            messageBox.innerText = result.message;
                            messageBox.className = "font-semibold text-center pt-4 transition-all text-red-600 block";
                        }
                    } catch (error) {
                        console.error('Submission Error:', error);
                        if (window.location.protocol === 'file:') {
                            messageBox.innerText = "Please open this page via a web server (like XAMPP or your live hosting) instead of 'file://'.";
                        } else {
                            messageBox.innerText = "Communication Error: Unable to reach the server. Please check your internet or try again later.";
                        }
                        messageBox.className = "font-semibold text-center pt-4 transition-all text-red-600 block";
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.querySelector('span').innerText = originalBtnText;
                    }
                });
            };

            handleFormSubmit('leadForm', 'leadFormMessage');
            handleFormSubmit('popupForm', 'popupFormMessage');
        });
    </script>

    <!-- ===== Category Packages Modal ===== -->
    <div id="categoryPackagesModal"
         class="fixed inset-0 z-[200] flex items-start justify-center p-4 pt-10 sm:pt-16 bg-slate-900/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300 overflow-y-auto"
         onclick="if(event.target === this) closeCategoryPackagesModal()">

        <div id="categoryPackagesModalCard"
             class="bg-white rounded-[2rem] shadow-2xl border border-gray-100 w-full max-w-3xl transform scale-95 transition-transform duration-300 overflow-hidden mb-10">

            <!-- Modal Header -->
            <div class="px-7 py-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-50/60 via-white to-blue-50/40 sticky top-0 z-10">
                <div>
                    <div class="inline-flex items-center gap-1.5 bg-brand/10 text-brand py-1 px-3 rounded-full text-[11px] font-extrabold uppercase tracking-wider mb-1">
                        <i class="fa-solid fa-layer-group text-[10px]"></i>
                        <span id="catModalPkgCount">Packages Available</span>
                    </div>
                    <h3 class="text-2xl font-outfit font-extrabold text-gray-900 leading-tight" id="catModalTitle">
                        Course Packages
                    </h3>
                </div>
                <button type="button" onclick="closeCategoryPackagesModal()"
                        class="w-10 h-10 rounded-2xl bg-gray-100 hover:bg-red-50 hover:text-red-500 text-gray-500 flex items-center justify-center transition-all cursor-pointer shrink-0">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Body: Package Grid -->
            <div class="p-6 sm:p-8">
                <p class="text-sm text-gray-500 font-medium mb-5">
                    Choose the package that fits your schedule and budget. Click <strong class="text-brand">Enroll</strong> to register for any package.
                </p>
                <div id="catModalPackagesGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Populated dynamically by JS -->
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-7 py-5 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between gap-4">
                <p class="text-xs text-gray-400 font-medium">All packages include lifetime access to course material.</p>
                <button type="button" onclick="closeCategoryPackagesModal()"
                        class="shrink-0 py-2.5 px-6 rounded-xl bg-white border border-gray-200 text-gray-700 font-bold text-sm hover:bg-gray-100 transition-all cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
    <!-- ===== /Category Packages Modal ===== -->

</body>

</html>
