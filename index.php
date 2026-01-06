<?php
session_start();
require_once 'config/database.php';

// Fetch Products
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY CAST(price AS INTEGER) ASC");
    $plans = $stmt->fetchAll();
} catch (PDOException $e) {
    $plans = [];
}

// Logic for Popular (e.g., middle tier or specific ID, here we pick the 2nd one or last one)
// Let's mark the one with index 1 (2nd item) as popular if it exists
foreach ($plans as $k => $v) {
    $plans[$k]['isPopular'] = ($k === 1);
    // Format price
    $plans[$k]['price_fmt'] = number_format($plans[$k]['price'], 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAZIZ PANEL - Hosting Server Tanpa Batas</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #111827 100%);
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        .glass-effect {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(34, 197, 94, 0.1);
        }
        .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(6, 78, 59, 0.3);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="min-h-screen text-gray-100 overflow-x-hidden">

    <!-- NAVBAR -->
    <nav class="fixed w-full z-50 glass-effect border-b border-green-900/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <a href="index.php" class="flex-shrink-0 flex items-center gap-3 cursor-pointer z-50 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-[0_0_25px_rgba(34,197,94,0.5)] group-hover:shadow-[0_0_35px_rgba(34,197,94,0.7)] transition-all duration-300">
                        <i data-lucide="server" class="text-white w-5 h-5"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-white">
                        RAZIZ<span class="text-green-400">PANEL</span>
                    </span>
                </a>

                <div class="hidden lg:block">
                    <div class="flex items-center gap-8">
                        <a href="#home" class="text-gray-300 hover:text-green-400 font-medium transition-colors duration-200 relative group">
                            Home
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-300"></span>
                        </a>
                        <a href="#features" class="text-gray-300 hover:text-green-400 font-medium transition-colors duration-200 relative group">
                            Keunggulan
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-300"></span>
                        </a>
                        <a href="#pricing" class="text-gray-300 hover:text-green-400 font-medium transition-colors duration-200 relative group">
                            Harga
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-300"></span>
                        </a>
                        <?php if(isset($_SESSION['user_logged_in'])): ?>
                        <a href="member.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-green-500/25">
                            Member Area
                        </a>
                        <?php else: ?>
                        <a href="login.php" class="bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-green-500/25">
                            Masuk
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="lg:hidden">
                    <button id="mobile-menu-btn" class="p-2 rounded-lg text-gray-400 hover:text-green-400 hover:bg-gray-800/50 transition-colors">
                        <i data-lucide="menu" class="h-6 w-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden glass-effect border-t border-gray-800">
            <div class="px-4 pt-3 pb-4 space-y-2">
                <a href="#home" class="block px-4 py-3 rounded-lg text-base font-medium text-gray-300 hover:text-green-400 hover:bg-gray-800/50 transition-colors">
                    Home
                </a>
                <a href="#features" class="block px-4 py-3 rounded-lg text-base font-medium text-gray-300 hover:text-green-400 hover:bg-gray-800/50 transition-colors">
                    Keunggulan
                </a>
                <a href="#pricing" class="block px-4 py-3 rounded-lg text-base font-medium text-gray-300 hover:text-green-400 hover:bg-gray-800/50 transition-colors">
                    Harga
                </a>
                <a href="login.php" class="block px-4 py-3 rounded-lg text-base font-medium bg-gradient-to-r from-green-600 to-emerald-700 text-white text-center mt-2">
                    Masuk
                </a>
            </div>
        </div>
    </nav>

    <!-- CONTENT -->
    <!-- HERO -->
    <section id="home" class="relative pt-32 pb-24 px-4 overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-green-600/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-emerald-600/5 rounded-full blur-3xl"></div>

        <div class="max-w-6xl mx-auto relative">
            <div class="text-center max-w-4xl mx-auto">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-900/30 border border-green-700/30 text-green-400 text-sm font-medium mb-6">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    Hosting Server Terbaik untuk Gaming
                </span>

                <h1 class="text-5xl sm:text-7xl lg:text-8xl font-black mb-6 leading-tight">
                    <span class="text-white">Hosting Server</span>
                    <br>
                    <span class="gradient-text">Tanpa Batas</span>
                </h1>

                <p class="text-gray-400 text-xl mb-10 max-w-3xl mx-auto leading-relaxed">
                    Dapatkan pengalaman gaming terbaik dengan server hosting berperforma tinggi.
                    Mulai dari <span class="text-green-400 font-bold">Rp 3.000</span> saja per bulan.
                </p>

                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-16">
                    <a href="#pricing" class="px-10 py-4 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 rounded-xl font-bold shadow-xl hover:shadow-green-500/30 transition-all duration-300 transform hover:-translate-y-1 text-white text-lg">
                        Mulai Sekarang
                        <i data-lucide="arrow-right" class="inline-block ml-2 w-5 h-5"></i>
                    </a>
                    <a href="#features" class="px-10 py-4 glass-effect border border-gray-800 hover:border-green-500/50 rounded-xl font-bold hover:text-green-400 transition-all duration-300 text-lg">
                        Jelajahi Fitur
                    </a>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
                    <div class="p-6 glass-effect rounded-2xl border border-gray-800">
                        <div class="text-3xl font-bold text-green-400 mb-2">99.9%</div>
                        <div class="text-gray-400 text-sm">Uptime</div>
                    </div>
                    <div class="p-6 glass-effect rounded-2xl border border-gray-800">
                        <div class="text-3xl font-bold text-green-400 mb-2">24/7</div>
                        <div class="text-gray-400 text-sm">Support</div>
                    </div>
                    <div class="p-6 glass-effect rounded-2xl border border-gray-800">
                        <div class="text-3xl font-bold text-green-400 mb-2">15 Hari</div>
                        <div class="text-gray-400 text-sm">Garansi</div>
                    </div>
                    <div class="p-6 glass-effect rounded-2xl border border-gray-800">
                        <div class="text-3xl font-bold text-green-400 mb-2">500+</div>
                        <div class="text-gray-400 text-sm">Pengguna</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section id="pricing" class="py-20 px-4 relative">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                    Paket <span class="gradient-text">Harga</span> Terjangkau
                </h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    Pilih paket yang sesuai dengan kebutuhan server gaming Anda
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($plans as $plan): ?>
                <div class="<?php echo $plan['isPopular'] ? 'relative transform scale-105' : ''; ?>">
                    <?php if ($plan['isPopular']): ?>
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="px-4 py-1.5 bg-gradient-to-r from-green-600 to-emerald-700 text-white text-xs font-bold rounded-full">
                            POPULAR
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="card-hover p-8 rounded-3xl glass-effect border <?php echo $plan['isPopular'] ? 'border-green-500/50 shadow-lg shadow-green-500/10' : 'border-gray-800'; ?> h-full flex flex-col">
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-green-400 font-bold text-4xl">Rp <?php echo $plan['price_fmt']; ?></span>
                                <span class="text-gray-500">/bulan</span>
                            </div>
                        </div>

                        <ul class="space-y-4 mb-8 flex-1">
                            <li class="flex items-center gap-3 text-gray-300">
                                <div class="w-8 h-8 rounded-lg bg-green-900/30 flex items-center justify-center">
                                    <i data-lucide="cpu" class="w-4 h-4 text-green-500"></i>
                                </div>
                                <span>CPU <?php echo htmlspecialchars($plan['cpu']); ?></span>
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <div class="w-8 h-8 rounded-lg bg-green-900/30 flex items-center justify-center">
                                    <i data-lucide="database" class="w-4 h-4 text-green-500"></i>
                                </div>
                                <span>RAM <?php echo htmlspecialchars($plan['ram']); ?></span>
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <div class="w-8 h-8 rounded-lg bg-green-900/30 flex items-center justify-center">
                                    <i data-lucide="disc" class="w-4 h-4 text-green-500"></i>
                                </div>
                                <span><?php echo htmlspecialchars($plan['disk']); ?></span>
                            </li>
                        </ul>

                        <a href="detail.php?id=<?php echo $plan['id']; ?>"
                           class="w-full py-3.5 rounded-xl font-bold text-sm transition-all duration-300 text-center block
                           <?php echo $plan['isPopular']
                               ? 'bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 text-white'
                               : 'bg-gray-800 hover:bg-gradient-to-r hover:from-green-600 hover:to-emerald-700 text-gray-300 hover:text-white'; ?>">
                            Pilih Paket
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="py-20 px-4 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-gray-900/50 to-transparent"></div>

        <div class="max-w-6xl mx-auto relative">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4 text-white">
                    <span class="gradient-text">Keunggulan</span> Kami
                </h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    Mengapa memilih RAZIZ PANEL untuk server gaming Anda?
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="p-8 glass-effect rounded-3xl border border-gray-800 hover:border-green-500/30 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-600 to-emerald-700 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="shield-check" class="w-7 h-7 text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-3 text-white">Anti DDoS</h4>
                    <p class="text-gray-400 leading-relaxed">
                        Perlindungan maksimal dari serangan luar dengan sistem keamanan berlapis.
                    </p>
                </div>

                <div class="p-8 glass-effect rounded-3xl border border-gray-800 hover:border-green-500/30 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-600 to-emerald-700 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="zap" class="w-7 h-7 text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-3 text-white">High Performance</h4>
                    <p class="text-gray-400 leading-relaxed">
                        Menggunakan hardware terbaru dan jaringan cepat untuk pengalaman gaming optimal.
                    </p>
                </div>

                <div class="p-8 glass-effect rounded-3xl border border-gray-800 hover:border-green-500/30 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-600 to-emerald-700 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="clock" class="w-7 h-7 text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-3 text-white">24/7 Support</h4>
                    <p class="text-gray-400 leading-relaxed">
                        Tim support siap membantu Anda kapan saja melalui berbagai channel komunikasi.
                    </p>
                </div>

                <div class="p-8 glass-effect rounded-3xl border border-gray-800 hover:border-green-500/30 transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-600 to-emerald-700 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="refresh-cw" class="w-7 h-7 text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-3 text-white">Auto Backup</h4>
                    <p class="text-gray-400 leading-relaxed">
                        Sistem backup otomatis untuk menjaga keamanan data server Anda.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-gray-900/50 border-t border-gray-800 pt-16 pb-8 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <!-- Brand Info -->
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="server" class="text-white w-5 h-5"></i>
                        </div>
                        <span class="font-bold text-xl text-white">
                            RAZIZ<span class="text-green-400">PANEL</span>
                        </span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed mb-6">
                        Penyedia hosting server gaming terbaik dengan performa tinggi dan keamanan maksimal.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-lg bg-gray-800 hover:bg-green-600 flex items-center justify-center transition-colors">
                            <i data-lucide="twitter" class="w-5 h-5 text-gray-300"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-lg bg-gray-800 hover:bg-green-600 flex items-center justify-center transition-colors">
                            <i data-lucide="instagram" class="w-5 h-5 text-gray-300"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-lg bg-gray-800 hover:bg-green-600 flex items-center justify-center transition-colors">
                            <i data-lucide="discord" class="w-5 h-5 text-gray-300"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-6">Tautan Cepat</h4>
                    <ul class="space-y-4">
                        <li><a href="#home" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i> Home
                        </a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i> Keunggulan
                        </a></li>
                        <li><a href="#pricing" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i> Harga
                        </a></li>
                        <li><a href="login.php" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i> Masuk
                        </a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-6">Dukungan</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-4 h-4"></i> FAQ
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="book-open" class="w-4 h-4"></i> Dokumentasi
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="message-square" class="w-4 h-4"></i> Live Chat
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-green-400 transition-colors flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4"></i> Kontak
                        </a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-6">Hubungi Kami</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3 text-gray-400">
                            <i data-lucide="mail" class="w-5 h-5 text-green-500 mt-1"></i>
                            <span>support@razizpanel.com</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-400">
                            <i data-lucide="phone" class="w-5 h-5 text-green-500 mt-1"></i>
                            <span>+62 812 3456 7890</span>
                        </li>
                        <li class="flex items-start gap-3 text-gray-400">
                            <i data-lucide="map-pin" class="w-5 h-5 text-green-500 mt-1"></i>
                            <span>Jakarta, Indonesia</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-gray-800">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-500 text-sm">
                        &copy; 2024 RAZIZ PANEL. All rights reserved.
                    </p>
                    <div class="flex gap-6 text-sm text-gray-500">
                        <a href="#" class="hover:text-green-400 transition-colors">Terms of Service</a>
                        <a href="#" class="hover:text-green-400 transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-green-400 transition-colors">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <button id="back-to-top" class="fixed bottom-8 right-8 w-12 h-12 rounded-full bg-gradient-to-r from-green-600 to-emerald-700 text-white flex items-center justify-center shadow-lg hover:shadow-green-500/30 transition-all duration-300 opacity-0 invisible">
        <i data-lucide="chevron-up" class="w-5 h-5"></i>
    </button>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('animate-fade-in');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Back to Top Button
        const backToTopBtn = document.getElementById('back-to-top');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.remove('opacity-0', 'invisible');
                backToTopBtn.classList.add('opacity-100', 'visible');
            } else {
                backToTopBtn.classList.remove('opacity-100', 'visible');
                backToTopBtn.classList.add('opacity-0', 'invisible');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                if (this.getAttribute('href') !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        // Close mobile menu if open
                        mobileMenu.classList.add('hidden');
                    }
                }
            });
        });
    </script>
</body>
</html>
