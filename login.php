<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header("Location: admin.php");
    exit;
}
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
    header("Location: member.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RAZIZ PANEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #030712; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-gray-900 border border-gray-800 rounded-3xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 mb-4 shadow-lg shadow-green-500/20">
                <i data-lucide="server" class="text-white w-6 h-6"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1">RAZIZ<span class="text-green-500">PANEL</span></h1>
            <p class="text-gray-400 text-sm">Masuk untuk mengelola server Anda</p>
        </div>

        <!-- Tabs -->
        <div class="flex bg-gray-950 p-1 rounded-xl mb-6 border border-gray-800">
            <button onclick="switchMode('login')" id="tab-login" class="flex-1 py-2 text-sm font-bold rounded-lg text-white bg-gray-800 shadow transition-all">Masuk</button>
            <button onclick="switchMode('register')" id="tab-register" class="flex-1 py-2 text-sm font-bold rounded-lg text-gray-400 hover:text-white transition-all">Daftar</button>
        </div>

        <!-- Login Form -->
        <form id="form-login" onsubmit="handleAuth(event, 'login')" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Username</label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                    <input type="text" name="username" required class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-10 pr-4 text-white placeholder-gray-600 outline-none focus:border-green-500 transition-colors" placeholder="Username">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                    <input type="password" name="password" required class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-10 pr-4 text-white placeholder-gray-600 outline-none focus:border-green-500 transition-colors" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-600/20 flex items-center justify-center gap-2 transition-all active:scale-95">
                Masuk Sekarang
            </button>
        </form>

        <!-- Register Form -->
        <form id="form-register" onsubmit="handleAuth(event, 'register')" class="space-y-4 hidden">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Username</label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                    <input type="text" name="username" required class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-10 pr-4 text-white placeholder-gray-600 outline-none focus:border-green-500 transition-colors" placeholder="Username">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Nomor WhatsApp</label>
                <div class="relative">
                    <i data-lucide="phone" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                    <input type="text" name="wa" required class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-10 pr-4 text-white placeholder-gray-600 outline-none focus:border-green-500 transition-colors" placeholder="08123456789">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1.5">Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5"></i>
                    <input type="password" name="password" required class="w-full bg-gray-950 border border-gray-800 rounded-xl py-3 pl-10 pr-4 text-white placeholder-gray-600 outline-none focus:border-green-500 transition-colors" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-600/20 flex items-center justify-center gap-2 transition-all active:scale-95">
                Daftar Akun
            </button>
        </form>
    </div>

    <script>
        lucide.createIcons();

        function switchMode(mode) {
            const loginForm = document.getElementById('form-login');
            const registerForm = document.getElementById('form-register');
            const tabLogin = document.getElementById('tab-login');
            const tabRegister = document.getElementById('tab-register');

            if (mode === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                tabLogin.className = 'flex-1 py-2 text-sm font-bold rounded-lg text-white bg-gray-800 shadow transition-all';
                tabRegister.className = 'flex-1 py-2 text-sm font-bold rounded-lg text-gray-400 hover:text-white transition-all';
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                tabRegister.className = 'flex-1 py-2 text-sm font-bold rounded-lg text-white bg-gray-800 shadow transition-all';
                tabLogin.className = 'flex-1 py-2 text-sm font-bold rounded-lg text-gray-400 hover:text-white transition-all';
            }
        }

        function handleAuth(e, type) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="animate-spin" data-lucide="loader-2"></i> Loading...';
            lucide.createIcons();

            const formData = new FormData(form);
            formData.append('action', type);

            fetch('actions/auth_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalContent;

                if (data.success) {
                    if (type === 'login') {
                        // Check for redirect param
                        const urlParams = new URLSearchParams(window.location.search);
                        const redirect = urlParams.get('redirect');

                        if (data.role === 'admin') window.location.href = 'admin.php';
                        else window.location.href = redirect ? redirect : 'member.php';
                    } else {
                        alert(data.message);
                        switchMode('login');
                    }
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = originalContent;
                alert('Terjadi kesalahan koneksi');
            });
        }
    </script>
</body>
</html>
