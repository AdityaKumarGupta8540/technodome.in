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

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
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
                    $catImg = $imgMap[$catSlug] ?? $defaultImages[$cIdx % count($defaultImages)];
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
                ?>
                <div class="glass-card !p-6 flex flex-col" data-aos="zoom-in" data-aos-delay="<?php echo $delay; ?>">
                    <img src="<?php echo htmlspecialchars($catImg); ?>" alt="<?php echo htmlspecialchars($cat['category_name']); ?>"
                        class="w-full h-48 object-cover rounded-2xl mb-6 shadow-md border border-white/50">
                    <h3 class="text-xl font-bold mb-4 text-gray-900"><?php echo htmlspecialchars($cat['category_name']); ?></h3>
                    <?php if (!empty($features)): ?>
                    <ul class="space-y-3 text-gray-700 font-medium text-sm flex-1">
                        <?php foreach ($features as $feat): ?>
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-check <?php echo $checkColor; ?> mt-1 font-bold"></i>
                            <span><?php echo htmlspecialchars($feat); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback: Static Cards -->
                <!-- Card 1 -->
                <div class="glass-card !p-6 flex flex-col" data-aos="zoom-in" data-aos-delay="200">
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
                <div class="glass-card !p-6 flex flex-col" data-aos="zoom-in" data-aos-delay="300">
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
                <div class="glass-card !p-6 flex flex-col" data-aos="zoom-in" data-aos-delay="400">
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
                <div class="glass-card !p-6 flex flex-col" data-aos="zoom-in" data-aos-delay="500">
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
        </div>
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
        <!-- Dynamic Pricing Cards -->
        <div class="grid grid-cols-1 <?php echo count($activePlans) === 1 ? 'max-w-sm mx-auto' : (count($activePlans) === 2 ? 'md:grid-cols-2 max-w-4xl mx-auto' : (count($activePlans) <= 4 ? 'md:grid-cols-2 lg:grid-cols-' . min(count($activePlans), 3) . ' max-w-6xl mx-auto' : 'md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 max-w-7xl mx-auto')); ?> gap-10">
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
            <div class="glass-card <?php echo $isFeatured ? 'bg-white/90 border-brand/40 shadow-[0_10px_40px_rgba(157,78,221,0.2)] md:-translate-y-4' : 'bg-white/70'; ?>"
                 data-aos="<?php echo $idx % 2 === 0 ? 'flip-left' : 'flip-right'; ?>">

                <?php if ($isFeatured): ?>
                <div class="absolute -top-1 right-1/2 translate-x-1/2 bg-gradient-to-r from-brand to-brand-blue text-white px-8 py-2 rounded-b-2xl text-sm font-extrabold shadow-lg z-10 w-max uppercase tracking-wider">
                    Best Value
                </div>
                <?php endif; ?>

                <div class="<?php echo $isFeatured ? 'mb-8 mt-6' : 'mb-8'; ?>">
                    <!-- Category badge -->
                    <div class="inline-block bg-<?php echo $colors['badge']; ?> text-<?php echo $colors['text']; ?> py-1.5 px-5 rounded-full text-xs font-bold tracking-wide mb-3 border border-<?php echo $colors['border']; ?> uppercase">
                        <?php echo htmlspecialchars($plan['category_name'] ?? 'Course'); ?>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-2"><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
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

                <!-- Features list -->
                <?php if (!empty($features)): ?>
                <ul class="space-y-2 mb-8 text-left text-sm">
                    <?php foreach ($features as $feat): ?>
                    <li class="flex items-start gap-2 text-gray-700 font-medium">
                        <i class="fa-solid fa-check text-<?php echo $colors['text']; ?> mt-0.5 shrink-0"></i>
                        <?php echo htmlspecialchars($feat); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <a href="#contact"
                    class="<?php echo $isFeatured ? 'bg-gradient-to-r from-brand to-brand-blue text-white hover:shadow-brand/50' : 'bg-white border-2 border-brand text-brand hover:bg-brand hover:text-white'; ?> font-bold py-3 px-8 rounded-full shadow-lg hover:-translate-y-1 transition-all w-full block text-lg text-center">
                    Book Now
                </a>
            </div>
            <?php endforeach; ?>
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
</body>

</html>
