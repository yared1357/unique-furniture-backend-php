<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// === CHANGE PASSWORD LOGIC ===
$success = $error = '';

if (isset($_POST['change_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $admin_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($old_pass, $admin['password'])) {
        if ($new_pass === $confirm_pass && strlen($new_pass) >= 6) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update->bind_param("si", $hashed, $admin_id);
            if ($update->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Database error. Try again.";
            }
        } else {
            $error = $new_pass === $confirm_pass 
                ? "Password must be at least 6 characters." 
                : "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Unique Furniture Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .password-toggle { cursor: pointer; }

        /* Mobile Menu - Only visible on small screens */
        @media (min-width: 768px) {
            #mobileMenu, #mobileOverlay { display: none !important; }
        }
        @media (max-width: 767px) {
            .mobile-menu { transition: transform 0.3s ease-in-out; }
            .mobile-menu.closed { transform: translateX(-100%); }
            .mobile-menu.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white min-h-screen">

    <!-- Header (Same as Dashboard) -->
    <nav class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <h1 class="text-xl font-bold text-white">Unique Furniture</h1>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-3">
                    <span class="text-sm text-slate-300">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>

                    <!-- Darker, readable buttons -->
                    <a href="dashboard.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-amber-300 hover:text-amber-200 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <span>Messages</span>
                    </a>

                    <a href="change_password.php" class="bg-amber-600/60 hover:bg-amber-600/80 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-amber-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a6 6 0 01-6 6 6 6 0 01-6-6 6 6 0 016-6m0 0V5a2 2 0 112 2h-2zm0 0v2a2 2 0 01-2 2H9a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2h-2z"></path>
                        </svg>
                        <span>Change Password</span>
                    </a>

                    <a href="posts.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-slate-200 hover:text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V9m-6 0h6"></path>
                        </svg>
                        <span>Posts</span>
                    </a>

                    <a href="logout.php" class="bg-red-600/60 hover:bg-red-600/80 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all border border-red-500">
                        Logout
                    </a>
                </div>

                <!-- Mobile Burger Button -->
                <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-white/10 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Slide-In Menu -->
        <div id="mobileMenu" class="fixed inset-y-0 left-0 w-64 bg-slate-800/95 backdrop-blur-xl border-r border-white/20 z-40 mobile-menu closed md:hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-lg font-bold text-white">Admin Menu</h2>
                    <button id="closeMenuBtn" class="p-2 rounded-lg hover:bg-white/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-1">
                    <p class="text-sm text-slate-400 mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>

                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-amber-300 hover:bg-slate-600/80 transition-all border border-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <span>Messages</span>
                    </a>

                    <a href="change_password.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-amber-600/60 text-white hover:bg-amber-600/80 transition-all border border-amber-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a6 6 0 01-6 6 6 6 0 01-6-6 6 6 0 016-6m0 0V5a2 2 0 112 2h-2zm0 0v2a2 2 0 01-2 2H9a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2h-2z"></path>
                        </svg>
                        <span>Change Password</span>
                    </a>

                    <a href="posts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-slate-200 hover:bg-slate-600/80 transition-all border border-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V9m-6 0h6"></path>
                        </svg>
                        <span>Posts</span>
                    </a>

                    <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-red-600/60 text-white hover:bg-red-600/80 transition-all border border-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile Overlay -->
        <div id="mobileOverlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <!-- Page Title -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-r from-amber-500 to-amber-600 rounded-full flex items-center justify-center shadow-lg">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a6 6 0 01-6 6 6 6 0 01-6-6 6 6 0 016-6m0 0V5a2 2 0 112 2h-2zm0 0v2a2 2 0 01-2 2H9a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2h-2z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white">Change Password</h1>
                <p class="text-slate-300 mt-2">Keep your account secure</p>
            </div>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-xl mb-6 backdrop-blur-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Change Password Form -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20">
                <form method="POST" class="space-y-6">
                    <!-- Current Password -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-slate-200 mb-2">Current Password</label>
                        <input 
                            type="password" 
                            name="old_password" 
                            id="old_password"
                            required
                            class="w-full px-4 py-3 pr-12 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent backdrop-blur-sm transition-all"
                            placeholder="Enter current password"
                        >
                        <button type="button" onclick="togglePass('old_password', 'eye-old')" class="absolute inset-y-0 right-0 flex items-center pr-3 mt-8 text-slate-400 hover:text-white password-toggle">
                            <svg id="eye-old" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- New Password -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-slate-200 mb-2">New Password</label>
                        <input 
                            type="password" 
                            name="new_password" 
                            id="new_password"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 pr-12 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent backdrop-blur-sm transition-all"
                            placeholder="Enter new password"
                        >
                        <button type="button" onclick="togglePass('new_password', 'eye-new')" class="absolute inset-y-0 right-0 flex items-center pr-3 mt-8 text-slate-400 hover:text-white password-toggle">
                            <svg id="eye-new" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Confirm Password -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-slate-200 mb-2">Confirm New Password</label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 pr-12 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent backdrop-blur-sm transition-all"
                            placeholder="Confirm new password"
                        >
                        <button type="button" onclick="togglePass('confirm_password', 'eye-confirm')" class="absolute inset-y-0 right-0 flex items-center pr-3 mt-8 text-slate-400 hover:text-white password-toggle">
                            <svg id="eye-confirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>

                    <button 
                        type="submit" 
                        name="change_password"
                        class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg"
                    >
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-20 py-8 border-t border-white/10 text-center text-slate-400 text-sm">
        <p>Copyrights © <?php echo date('Y'); ?> Unique Furniture. All rights reserved. | Admin Panel v1.0</p>
        <p class="mt-2">Developed By <span class="text-amber-400">Y-Global System Solution</span></p>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMenuBtn = document.getElementById('closeMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');

        function openMenu() {
            if (window.innerWidth >= 768) return;
            mobileMenu.classList.remove('closed');
            mobileMenu.classList.add('open');
            mobileOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            mobileMenu.classList.remove('open');
            mobileMenu.classList.add('closed');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMenu);
        if (closeMenuBtn) closeMenuBtn.addEventListener('click', closeMenu);
        if (mobileOverlay) mobileOverlay.addEventListener('click', closeMenu);

        // Auto-close on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) closeMenu();
        });

        // Password Toggle
        function togglePass(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(eyeId);
            if (input.type === 'password') {
                input.type = 'text';
                eye.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L15.758 15"></path>`;
            } else {
                input.type = 'password';
                eye.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }
    </script>
</body>
</html>