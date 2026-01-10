<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

$user = $_SESSION['user'];

// Fetch Settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();
$adminWA = $settings['contact_wa'] ?? '628123456789';
$panelUrl = $settings['panel_domain'] ? 'https://' . $settings['panel_domain'] : '#';

// Fetch Servers
$stmt = $pdo->prepare("
    SELECT s.*, p.name as plan_name
    FROM servers s
    JOIN products p ON s.product_id = p.id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$user['id']]);
$servers = $stmt->fetchAll();

// Prepare Servers for JS
$jsServers = [];
foreach ($servers as $srv) {
    $jsServers[] = [
        'id' => "SVR-" . $srv['id'],
        'name' => $srv['plan_name'],
        'url' => $panelUrl,
        'created_at' => $srv['created_at'],
        // Logic for warranty/expiry dates (simplified)
        'active_until' => date('Y-m-d', strtotime($srv['created_at'] . ' +30 days')),
        'warranty_until' => date('Y-m-d', strtotime($srv['created_at'] . ' +15 days')),
        'status' => $srv['status']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAZIZ MEMBER AREA</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        gray: {
                            900: '#111827',
                            950: '#030712',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.4s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #374151; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 selection:bg-green-500 selection:text-black min-h-screen flex flex-col">

    <!-- NAVBAR -->
    <nav class="sticky top-0 z-50 bg-gray-900/80 backdrop-blur-md border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-green-600 rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(22,163,74,0.6)]">
                        <i data-lucide="server" class="text-white w-5 h-5"></i>
                    </div>
                    <span class="font-bold text-xl tracking-wider text-white uppercase">RAZIZ<span class="text-green-500"> MEMBER</span></span>
                </a>

                <!-- User Profile & Logout -->
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex flex-col items-end mr-2">
                        <span class="text-sm font-bold text-white"><?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="text-xs text-gray-400"><?php echo htmlspecialchars($user['wa']); ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-emerald-700 flex items-center justify-center text-white font-bold shadow-lg">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <button onclick="logout()" class="p-2 text-gray-400 hover:text-red-500 transition-colors bg-gray-800 rounded-lg hover:bg-gray-700 ml-2" title="Keluar">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-green-900/20 to-gray-900 border border-green-500/20 rounded-3xl p-6 sm:p-10 mb-8 animate-fade-in flex flex-col sm:flex-row items-center justify-between gap-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2 text-center sm:text-left">Halo, <?php echo htmlspecialchars($user['username']); ?>! 👋</h1>
                <p class="text-gray-400 text-center sm:text-left">Selamat datang kembali di panel member area. Kelola servermu dengan mudah di sini.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="switchTab('servers')" id="btn-servers" class="px-6 py-3 rounded-xl font-bold text-sm transition-all bg-green-600 text-white shadow-lg shadow-green-600/20 flex items-center gap-2">
                    <i data-lucide="hard-drive" class="w-4 h-4"></i> Server Saya
                </button>
                <button onclick="switchTab('settings')" id="btn-settings" class="px-6 py-3 rounded-xl font-bold text-sm transition-all bg-gray-800 text-gray-400 hover:text-white border border-gray-700 flex items-center gap-2">
                    <i data-lucide="settings" class="w-4 h-4"></i> Akun
                </button>
            </div>
        </div>

        <!-- SERVER LIST VIEW -->
        <div id="view-servers" class="space-y-6 animate-fade-in">
            <div class="px-2">
                <h2 class="text-xl font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="activity" class="w-5 h-5 text-green-500"></i> Layanan Aktif
                </h2>

                <!-- WARNING BOX -->
                <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-start gap-4 mb-8 shadow-sm">
                    <div class="bg-amber-500/20 p-2 rounded-lg shrink-0">
                        <i data-lucide="alert-triangle" class="text-amber-500 w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-amber-200 text-sm font-semibold mb-0.5">Peringatan Keamanan Data</p>
                        <p class="text-amber-200/70 text-xs leading-relaxed">
                            Harap backup data panel secara rutin. Ketika terjadi crash, maka kemungkinan terburuknya kami tidak menyimpan data panel anda. Keamanan data adalah tanggung jawab pengguna.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="server-list">
                <!-- Server Card Injected by JS -->
                <?php if(empty($jsServers)): ?>
                    <div class="col-span-2 text-center text-gray-500 py-10">
                        Belum ada layanan aktif. <a href="index.php" class="text-green-500 hover:underline">Beli Sekarang</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SOURCE CODE SECTION (Only visible if servers > 0) -->
            <?php if(!empty($jsServers)): ?>
            <div class="pt-8 border-t border-gray-800">
                <h2 class="text-xl font-bold text-white flex items-center gap-2 mb-6 px-2">
                    <i data-lucide="code" class="w-5 h-5 text-green-500"></i> Bonus Source Code
                </h2>
                <div class="bg-gray-900 border border-gray-800 rounded-3xl overflow-hidden shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-950/50 text-gray-500 uppercase text-[10px] font-bold tracking-widest">
                                <tr>
                                    <th class="px-6 py-4">Nama File</th>
                                    <th class="px-6 py-4">Tanggal Upload</th>
                                    <th class="px-6 py-4 text-right">Download</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800" id="sc-list-body">
                                <tr><td colspan="3" class="px-6 py-5 text-center"><i class="animate-spin" data-lucide="loader"></i> Memuat SC...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- SETTINGS VIEW -->
        <div id="view-settings" class="hidden animate-fade-in max-w-2xl mx-auto px-2">
            <h2 class="text-xl font-bold text-white flex items-center gap-2 mb-6">
                <i data-lucide="lock" class="w-5 h-5 text-green-500"></i> Keamanan Akun
            </h2>

            <div class="bg-gray-900 border border-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl">
                <form onsubmit="handlePasswordChange(event)" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Username Akun</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-12 pr-4 text-gray-500 cursor-not-allowed outline-none font-mono">
                        </div>
                        <p class="text-[10px] text-gray-600 mt-2 italic">*Username ini juga merupakan username untuk login ke Panel Pterodactyl.</p>
                    </div>

                    <div class="border-t border-gray-800 pt-4"></div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Password Baru</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                            <input type="password" name="new_password" id="new-pass" required class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-12 pr-4 text-white outline-none focus:border-green-500 transition-colors" placeholder="••••••••">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <i data-lucide="check-circle" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                            <input type="password" id="confirm-pass" required class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-12 pr-4 text-white outline-none focus:border-green-500 transition-colors" placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-green-600/20 flex items-center justify-center gap-2 transition-all active:scale-95">
                        <i data-lucide="save" class="w-5 h-5"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="border-t border-gray-900 bg-gray-950 py-8 mt-auto px-4">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-gray-500 text-sm uppercase tracking-widest font-bold">RAZIZ PANEL</p>
            <p class="text-gray-600 text-xs mt-1">&copy; 2024. Semua Hak Dilindungi.</p>
        </div>
    </footer>

    <!-- JAVASCRIPT -->
    <script>
        // NOMOR WHATSAPP ADMIN (From PHP)
        const adminWA = "<?php echo $adminWA; ?>";

        // Data Servers (From PHP)
        const myServers = <?php echo json_encode($jsServers); ?>;

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', () => {
            renderServers();
            <?php if(!empty($jsServers)): ?>
            loadSC();
            <?php endif; ?>
            lucide.createIcons();
        });

        // --- FUNCTIONS ---

        function calculateDaysLeft(targetDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const target = new Date(targetDate);
            target.setHours(0, 0, 0, 0);
            const diffTime = target - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            // return diffDays; // Allow negatives for expired
            return diffDays;
        }

        function formatDate(dateString) {
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            return new Date(dateString).toLocaleDateString('id-ID', options);
        }

        function renderServers() {
            const container = document.getElementById('server-list');
            if(myServers.length === 0) return; // Handled by PHP empty check usually

            let html = '';

            myServers.forEach(server => {
                const activeDays = calculateDaysLeft(server.active_until);
                const warrantyDays = calculateDaysLeft(server.warranty_until);

                const activeColor = activeDays > 5 ? 'text-green-400' : 'text-red-400';

                // Logika Garansi
                const warrantyBadge = warrantyDays > 0
                    ? `<span class="bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] px-2 py-0.5 rounded-full font-bold flex items-center gap-1 shadow-sm"><i data-lucide="shield-check" class="w-3 h-3"></i> Garansi ${warrantyDays} Hari</span>`
                    : `<span class="bg-red-500/10 text-red-500 border border-red-500/20 text-[10px] px-2 py-0.5 rounded-full font-bold flex items-center gap-1 shadow-sm"><i data-lucide="shield-off" class="w-3 h-3"></i> Tidak Ada Garansi</span>`;

                // Format Pesan WhatsApp
                const waMessage = `Halo Admin RAZIZ PANEL, saya ingin melakukan Klaim Replace untuk server berikut:\n\nNama Paket: ${server.name}\nID Server: ${server.id}\nURL Panel: ${server.url}\n\nMohon bantuannya untuk proses pengecekan.`;
                const waLink = `https://wa.me/${adminWA}?text=${encodeURIComponent(waMessage)}`;

                // Tombol Klaim Replace hanya muncul jika garansi masih ada
                const claimButton = warrantyDays > 0
                    ? `<a href="${waLink}" target="_blank" class="px-5 bg-gray-800 hover:bg-gray-700 text-white rounded-xl border border-gray-700 transition-all flex items-center justify-center" title="Klaim Garansi Replace">
                            <span class="font-bold text-sm">Klaim Replace</span>
                       </a>`
                    : '';

                html += `
                <div class="bg-gray-900 border border-gray-800 rounded-3xl p-6 hover:border-green-500/30 transition-all group relative overflow-hidden shadow-lg animate-fade-in">
                    <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <i data-lucide="server" class="w-32 h-32 text-white"></i>
                    </div>

                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-white mb-1 group-hover:text-green-400 transition-colors">${server.name}</h3>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="bg-green-500/10 text-green-500 text-[10px] px-2 py-0.5 rounded border border-green-500/20 font-bold uppercase tracking-wider">
                                        ${server.status}
                                    </span>
                                    ${warrantyBadge}
                                </div>
                            </div>
                            <div class="bg-gray-950 border border-gray-800 rounded-2xl p-2 text-center min-w-[90px] shadow-inner border border-green-500/10">
                                <span class="block text-[10px] text-gray-500 uppercase tracking-tighter font-semibold">Sisa Aktif</span>
                                <span class="block text-xl font-black ${activeColor}">${activeDays > 0 ? activeDays : 0} Hari</span>
                            </div>
                        </div>

                        <div class="space-y-3 mb-6 bg-gray-950/50 p-4 rounded-2xl border border-gray-800/50">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center text-sm gap-1">
                                <span class="text-gray-500 flex items-center gap-2 font-medium"><i data-lucide="globe" class="w-4 h-4 text-green-600"></i> Panel URL</span>
                                <a href="${server.url}" target="_blank" class="text-green-400 hover:text-green-300 hover:underline font-mono truncate max-w-[200px]">${server.url}</a>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center text-sm gap-1">
                                <span class="text-gray-500 flex items-center gap-2 font-medium"><i data-lucide="calendar" class="w-4 h-4 text-green-600"></i> Dibuat</span>
                                <span class="text-white text-xs">${formatDate(server.created_at)}</span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center text-sm gap-1">
                                <span class="text-gray-500 flex items-center gap-2 font-medium"><i data-lucide="hash" class="w-4 h-4 text-green-600"></i> Server ID</span>
                                <span class="text-white text-xs font-mono">${server.id}</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="${server.url}" target="_blank" class="flex-1 bg-green-600 hover:bg-green-500 text-white font-bold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 transition-all shadow-lg shadow-green-600/10">
                                <i data-lucide="external-link" class="w-4 h-4"></i> Login Panel
                            </a>
                            ${claimButton}
                        </div>
                    </div>
                </div>
                `;
            });

            container.innerHTML = html;
        }

        function switchTab(tab) {
            const btnServers = document.getElementById('btn-servers');
            const btnSettings = document.getElementById('btn-settings');
            const viewServers = document.getElementById('view-servers');
            const viewSettings = document.getElementById('view-settings');

            if (tab === 'servers') {
                viewServers.classList.remove('hidden');
                viewSettings.classList.add('hidden');

                btnServers.className = "px-6 py-3 rounded-xl font-bold text-sm transition-all bg-green-600 text-white shadow-lg shadow-green-600/20 flex items-center gap-2";
                btnSettings.className = "px-6 py-3 rounded-xl font-bold text-sm transition-all bg-gray-800 text-gray-400 hover:text-white border border-gray-700 flex items-center gap-2";
            } else {
                viewServers.classList.add('hidden');
                viewSettings.classList.remove('hidden');

                btnSettings.className = "px-6 py-3 rounded-xl font-bold text-sm transition-all bg-green-600 text-white shadow-lg shadow-green-600/20 flex items-center gap-2";
                btnServers.className = "px-6 py-3 rounded-xl font-bold text-sm transition-all bg-gray-800 text-gray-400 hover:text-white border border-gray-700 flex items-center gap-2";
            }
        }

        function handlePasswordChange(e) {
            e.preventDefault();
            const newPass = document.getElementById('new-pass').value;
            const confirmPass = document.getElementById('confirm-pass').value;

            if (newPass !== confirmPass) {
                alert('Konfirmasi password tidak cocok!');
                return;
            }

            const btn = e.target.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;

            btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Memproses...';
            btn.disabled = true;

            const formData = new FormData(e.target);
            formData.append('action', 'change_password');

            fetch('actions/auth_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5"></i> Berhasil Disimpan';
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-emerald-500');
                    lucide.createIcons();

                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.classList.add('bg-green-600');
                        btn.classList.remove('bg-emerald-500');
                        btn.disabled = false;
                        e.target.reset();
                        lucide.createIcons();
                    }, 2000);
                } else {
                    alert('Gagal: ' + data.message);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        }

        function logout() {
            fetch('actions/auth_handler.php?action=logout')
                .then(() => window.location.href = 'login.php');
        }

        function loadSC() {
            fetch('actions/sc_handler.php?action=list')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('sc-list-body');
                if(!tbody) return;

                if (data.sc && data.sc.length > 0) {
                    tbody.innerHTML = data.sc.map(item => `
                        <tr class="hover:bg-gray-800/20 transition-colors">
                            <td class="px-6 py-5 font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-blue-500/10 rounded-lg"><i data-lucide="file-archive" class="w-4 h-4 text-blue-500"></i></div>
                                ${item.name}
                            </td>
                            <td class="px-6 py-5 text-xs text-gray-400">${item.date}</td>
                            <td class="px-6 py-5 text-right">
                                <a href="actions/sc_handler.php?action=download&id=${item.id}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg text-xs font-bold transition-all shadow-lg shadow-green-600/20">
                                    <i data-lucide="download" class="w-3 h-3"></i> Unduh
                                </a>
                            </td>
                        </tr>
                    `).join('');
                    lucide.createIcons();
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-5 text-center text-gray-500">Belum ada file source code.</td></tr>';
                }
            })
            .catch(err => {
                console.error(err);
                const tbody = document.getElementById('sc-list-body');
                if(tbody) tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-5 text-center text-red-500">Gagal memuat data.</td></tr>';
            });
        }

    </script>
</body>
</html>
