<?php
require_once 'config/database.php';

// Get Product ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedPlan = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $selectedPlan = $stmt->fetch();
} catch (PDOException $e) {
    // Fail silently or log
}

// Redirect if not found
if (!$selectedPlan) {
    header("Location: index.php");
    exit;
}

// Logic for Popular (consistency with index)
// Assuming logic is same as index (e.g. if we fetched all we would know ranking)
// For Detail, just hardcode false or fetch popularity logic if needed.
// Let's assume passed via URL or just check price > threshold?
// For demo, let's say ID 2 is popular.
$selectedPlan['isPopular'] = ($selectedPlan['id'] == 2);

// Get Settings for WhatsApp
$contactWa = '6281234567890'; // Default
try {
    $stmt = $pdo->query("SELECT contact_wa FROM settings LIMIT 1");
    $settings = $stmt->fetch();
    if ($settings && !empty($settings['contact_wa'])) {
        $contactWa = $settings['contact_wa'];
        // Ensure format is 628...
        if (substr($contactWa, 0, 1) === '0') {
            $contactWa = '62' . substr($contactWa, 1);
        }
    }
} catch (Exception $e) {}

// Construct Message
$message = "Halo RAZIZ PANEL, saya ingin membeli paket " . $selectedPlan['name'] . " (ID: " . $selectedPlan['id'] . ")" . PHP_EOL .
           "Harga: Rp " . number_format($selectedPlan['price'], 0, ',', '.') . PHP_EOL .
           "Spesifikasi:" . PHP_EOL .
           "- CPU: " . $selectedPlan['cpu'] . PHP_EOL .
           "- RAM: " . $selectedPlan['ram'] . PHP_EOL .
           "- Disk: " . $selectedPlan['disk'];
$waUrl = "https://wa.me/" . $contactWa . "?text=" . urlencode($message);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($selectedPlan['name']); ?> - RAZIZ PANEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #111827 100%);
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .server-preview {
            background: linear-gradient(135deg, #1a2a1a 0%, #0f172a 100%);
        }
        .spec-card {
            transition: all 0.3s ease;
        }
        .spec-card:hover {
            transform: translateY(-5px);
            background: rgba(34, 197, 94, 0.05);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
    </style>
</head>
<body class="min-h-screen text-gray-100 overflow-x-hidden">

    <!-- Header -->
    <nav class="glass-effect border-b border-green-900/30 fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <a href="index.php" class="flex-shrink-0 flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="server" class="text-white w-5 h-5"></i>
                    </div>
                    <span class="font-bold text-lg text-white">
                        RAZIZ<span class="text-green-400">PANEL</span>
                    </span>
                </a>

                <div class="flex items-center gap-4">
                    <a href="index.php" class="hidden sm:inline-flex items-center gap-2 text-gray-300 hover:text-green-400 transition-colors">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        <span class="text-sm font-medium">Beranda</span>
                    </a>
                    <a href="index.php#pricing" class="hidden sm:inline-flex items-center gap-2 text-gray-300 hover:text-green-400 transition-colors">
                        <i data-lucide="package" class="w-4 h-4"></i>
                        <span class="text-sm font-medium">Paket Lainnya</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="pt-28 pb-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto animate-fade-in">
        <!-- Breadcrumb & Back -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
            <a href="index.php" class="inline-flex items-center gap-2 text-green-400 hover:text-green-300 transition-colors group">
                <i data-lucide="arrow-left" class="w-5 h-5 group-hover:-translate-x-1 transition-transform duration-300"></i>
                <span class="font-medium">Kembali ke Beranda</span>
            </a>

            <?php if($selectedPlan['isPopular']): ?>
            <div class="px-4 py-1.5 bg-gradient-to-r from-green-600 to-emerald-700 text-white text-sm font-bold rounded-full">
                <i data-lucide="star" class="w-3 h-3 inline-block mr-1"></i>
                PAKET TERPOPULER
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
            <!-- Visual Preview Section -->
            <div class="space-y-8">
                <div class="relative group">
                    <!-- Glow Effect -->
                    <div class="absolute -inset-1 bg-gradient-to-r from-green-600/20 to-emerald-600/20 rounded-3xl blur-xl opacity-50 group-hover:opacity-70 transition-opacity duration-500"></div>

                    <div class="relative server-preview border-2 border-green-900/30 rounded-2xl overflow-hidden shadow-2xl">
                        <!-- Server Header -->
                        <div class="p-4 border-b border-green-900/30 bg-gray-900/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-1.5">
                                        <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                                        <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                                        <div class="w-2.5 h-2.5 rounded-full bg-green-500 pulse"></div>
                                    </div>
                                    <div class="h-3 bg-gray-800 rounded-full w-40 ml-2"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded bg-green-900/30 flex items-center justify-center">
                                        <i data-lucide="signal" class="w-3 h-3 text-green-500"></i>
                                    </div>
                                    <div class="text-xs text-green-400 font-mono">ONLINE</div>
                                </div>
                            </div>
                        </div>

                        <!-- Server Dashboard Preview -->
                        <div class="p-6">
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div class="col-span-2 space-y-3">
                                    <div class="h-10 bg-gray-900/70 rounded-lg border border-gray-800 flex items-center px-3">
                                        <div class="text-xs text-green-400 font-mono">CPU: <?php echo htmlspecialchars($selectedPlan['cpu']); ?></div>
                                    </div>
                                    <div class="h-10 bg-gray-900/70 rounded-lg border border-gray-800 flex items-center px-3">
                                        <div class="text-xs text-green-400 font-mono">RAM: <?php echo htmlspecialchars($selectedPlan['ram']); ?></div>
                                    </div>
                                </div>
                                <div class="bg-gray-900/50 rounded-lg border border-gray-800 flex items-center justify-center">
                                    <div class="text-center p-3">
                                        <div class="text-2xl font-bold text-green-400">100%</div>
                                        <div class="text-[10px] text-gray-400">UPTIME</div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-900/40 rounded-lg border border-gray-800 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-sm text-gray-300 font-medium">Server Resources</div>
                                    <div class="text-xs text-green-400 bg-green-900/20 px-2 py-1 rounded">ACTIVE</div>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-400">CPU Load</span>
                                        <span class="text-green-400">12%</span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-1.5">
                                        <div class="bg-green-500 h-1.5 rounded-full" style="width: 12%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-400">Memory</span>
                                        <span class="text-green-400"><?php echo rand(30, 70); ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-1.5">
                                        <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo rand(30, 70); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-gray-950 to-transparent p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-4 h-4 text-green-500"></i>
                                    <span class="text-xs text-gray-300">Aktif 30 Hari</span>
                                </div>
                                <div class="text-xs text-green-400 font-mono flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                    24/7 Monitoring
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Highlights -->
                <div class="glass-effect rounded-2xl border border-gray-800 p-6">
                    <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                        <i data-lucide="zap" class="w-5 h-5 text-green-500"></i>
                        Keunggulan Paket
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center gap-2 text-sm text-gray-300">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Garansi 15 Hari</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-300">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Aktif 30 Hari</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-300">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Setup Instan</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-300">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Panel Pterodactyl</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-300">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>Unlimited Slots</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-300">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                            <span>High Performance</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Info Section -->
            <div class="space-y-8">
                <!-- Plan Header -->
                <div class="glass-effect rounded-3xl border border-gray-800 p-8">
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <h1 class="text-3xl sm:text-4xl font-extrabold text-white"><?php echo htmlspecialchars($selectedPlan['name']); ?></h1>
                            <div class="text-green-500 font-bold text-3xl">
                                Rp <?php echo number_format($selectedPlan['price'], 0, ',', '.'); ?>
                            </div>
                        </div>
                        <p class="text-gray-400">Paket server gaming optimal untuk kebutuhan <?php echo strtolower(htmlspecialchars($selectedPlan['name'])); ?> dengan performa maksimal.</p>
                    </div>

                    <div class="bg-green-900/10 border border-green-900/30 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <i data-lucide="shield-check" class="w-5 h-5 text-green-500"></i>
                            <div>
                                <p class="text-sm text-green-400 font-semibold">Garansi 15 Hari</p>
                                <p class="text-xs text-gray-400">Uang kembali jika tidak puas dengan layanan</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-900/10 border border-blue-900/30 rounded-xl p-4">
                        <div class="flex items-center gap-3">
                            <i data-lucide="calendar" class="w-5 h-5 text-blue-500"></i>
                            <div>
                                <p class="text-sm text-blue-400 font-semibold">Aktif 30 Hari</p>
                                <p class="text-xs text-gray-400">Server aktif selama 30 hari sejak aktivasi</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Specifications -->
                <div class="glass-effect rounded-3xl border border-gray-800 p-8">
                    <h3 class="text-white font-bold text-xl mb-6 flex items-center gap-2">
                        <i data-lucide="settings" class="w-5 h-5 text-green-500"></i>
                        Spesifikasi Utama
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <div class="spec-card bg-gray-900/30 hover:bg-gray-900/50 rounded-xl p-4 border border-gray-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-green-900/20 flex items-center justify-center">
                                    <i data-lucide="cpu" class="w-5 h-5 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">PROCESSOR</p>
                                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($selectedPlan['cpu']); ?></p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Kecepatan tinggi untuk gaming smooth</p>
                        </div>

                        <div class="spec-card bg-gray-900/30 hover:bg-gray-900/50 rounded-xl p-4 border border-gray-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-green-900/20 flex items-center justify-center">
                                    <i data-lucide="database" class="w-5 h-5 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">MEMORY</p>
                                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($selectedPlan['ram']); ?></p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Kapasitas memori optimal untuk server gaming</p>
                        </div>

                        <div class="spec-card bg-gray-900/30 hover:bg-gray-900/50 rounded-xl p-4 border border-gray-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-green-900/20 flex items-center justify-center">
                                    <i data-lucide="hard-drive" class="w-5 h-5 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">STORAGE</p>
                                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($selectedPlan['disk']); ?></p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">SSD NVMe high-speed storage</p>
                        </div>

                        <div class="spec-card bg-gray-900/30 hover:bg-gray-900/50 rounded-xl p-4 border border-gray-800">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-green-900/20 flex items-center justify-center">
                                    <i data-lucide="calendar" class="w-5 h-5 text-green-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">DURASI</p>
                                    <p class="text-lg font-bold text-white">30 Hari</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Server aktif selama 30 hari penuh</p>
                        </div>
                    </div>

                    <!-- Important Notes -->
                    <div class="space-y-4 pt-6 border-t border-gray-800">
                        <h4 class="text-white font-semibold text-sm uppercase tracking-wider">Informasi Penting</h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-2 text-sm text-gray-300">
                                <i data-lucide="info" class="w-4 h-4 text-green-500 mt-0.5"></i>
                                <span>Server akan aktif selama 30 hari sejak aktivasi</span>
                            </div>
                            <div class="flex items-start gap-2 text-sm text-gray-300">
                                <i data-lucide="shield" class="w-4 h-4 text-green-500 mt-0.5"></i>
                                <span>Garansi uang kembali berlaku dalam 15 hari pertama</span>
                            </div>
                            <div class="flex items-start gap-2 text-sm text-gray-300">
                                <i data-lucide="zap" class="w-4 h-4 text-green-500 mt-0.5"></i>
                                <span>Server menggunakan hardware performa tinggi untuk gaming</span>
                            </div>
                            <div class="flex items-start gap-2 text-sm text-gray-300">
                                <i data-lucide="clock" class="w-4 h-4 text-green-500 mt-0.5"></i>
                                <span>Setup server akan diproses maksimal 2 jam setelah pembayaran</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA Button -->
                <div class="space-y-4">
                    <a href="<?php echo $waUrl; ?>" target="_blank"
                       class="w-full bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-500 hover:to-emerald-600 text-white font-bold py-4 px-6 rounded-2xl shadow-xl hover:shadow-green-500/25 flex items-center justify-center gap-3 transition-all duration-300 transform hover:-translate-y-1 active:scale-95 text-lg">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                        Pesan Sekarang via WhatsApp
                    </a>

                    <p class="text-center text-gray-500 text-sm">
                        <i data-lucide="clock" class="w-4 h-4 inline-block mr-1"></i>
                        Setup server maksimal 2 jam setelah pembayaran
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900/50 border-t border-gray-800 mt-20 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <i data-lucide="server" class="text-white w-4 h-4"></i>
                    </div>
                    <span class="font-bold text-white">
                        RAZIZ<span class="text-green-400">PANEL</span>
                    </span>
                </div>

                <p class="text-gray-500 text-sm text-center">
                    &copy; 2024 RAZIZ PANEL. Hosting Server Gaming Terbaik.
                </p>

                <a href="index.php" class="text-green-400 hover:text-green-300 transition-colors text-sm font-medium">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline-block mr-1"></i>
                    Lihat Semua Paket
                </a>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // Smooth scroll to top on load
        window.addEventListener('load', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>
