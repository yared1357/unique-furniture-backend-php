<?php 
require_once 'config.php'; 

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Mark all contacts as read
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE contacts SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    header('Location: dashboard.php');
    exit;
}

// Fetch stats
$total_contacts = $conn->query("SELECT COUNT(*) FROM contacts")->fetch_row()[0];
$unread_contacts = $conn->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetch_row()[0];
$total_posts = $conn->query("SELECT COUNT(*) FROM posts")->fetch_row()[0];
$published_posts = $conn->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetch_row()[0];
$draft_posts = $conn->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetch_row()[0];
$total_admins = $conn->query("SELECT COUNT(*) FROM admins")->fetch_row()[0];

// Contact services distribution
$services_result = $conn->query("
    SELECT service, COUNT(*) as count 
    FROM contacts 
    GROUP BY service 
    ORDER BY count DESC
");
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Unique Furniture Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Mobile Menu */
        @media (min-width: 768px) {
            #mobileMenu, #mobileOverlay { display: none !important; }
        }
        @media (max-width: 767px) {
            .mobile-menu { transition: transform 0.3s ease-in-out; }
            .mobile-menu.closed { transform: translateX(-100%); }
            .mobile-menu.open { transform: translateX(0); }
        }

        /* CHART WIDTH */
        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        /* RED CIRCLE BADGE – ON TOP OF BELL */
        .notification-badge {
            @apply absolute bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center shadow-lg animate-pulse;
            width: 20px;
            height: 20px;
            min-width: 20px;
            top: -6px;
            left: 50%;
            transform: translateX(-50%);
            border: 2px solid #1e293b;
            z-index: 10;
        }
        @media (max-width: 640px) {
            .notification-badge {
                width: 18px;
                height: 18px;
                font-size: 10px;
                top: -5px;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white min-h-screen">

    <!-- Header -->
    <nav class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <h1 class="text-xl font-bold text-white">Unique Furniture</h1>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-3">
                    <span class="text-sm text-slate-300">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>!</span>

                    <!-- BELL + RED CIRCLE ON TOP -->
                    <div class="relative">
                        <a href="dashboard.php?mark_read=1" class="relative p-2 rounded-lg hover:bg-white/10 transition-all block">
                            <svg class="w-6 h-6 text-amber-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <?php if ($unread_contacts > 0): ?>
                                <span class="notification-badge">
                                    <?php echo $unread_contacts > 99 ? '99+' : $unread_contacts; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Other Links -->

                     <a href="dashboard.php" class="bg-amber-600/60 hover:bg-amber-600/80 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-amber-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span>Dashboard</span>
                    </a>


                    <a href="messages.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-amber-300 hover:text-amber-200 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <span>Messages</span>
                    </a>

                    <a href="change_password.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-slate-200 hover:text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a6 6 0 01-6 6 6 6 0 01-6-6 6 6 0 016-6m0 0V5a2 2 0 112 2h-2zm0 0v2a2 2 0 01-2 2H9a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2h-2z"></path></svg>
                        <span>Change Password</span>
                    </a>

                    <a href="posts.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-slate-200 hover:text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V9m-6 0h6"></path></svg>
                        <span>Posts</span>
                    </a>

                    <a href="logout.php" class="bg-red-600/60 hover:bg-red-600/80 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all border border-red-500">
                        Logout
                    </a>
                </div>

                <!-- Mobile Burger -->
                <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-white/10 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
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
                    <p class="text-sm text-slate-400 mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>!</p>

                    <!-- Mobile Bell + RED CIRCLE ON TOP -->
                    <div class="relative mb-4">
                        <a href="statistics.php?mark_read=1" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-amber-300 hover:bg-slate-600/80 transition-all border border-slate-600">
                            <div class="relative">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <?php if ($unread_contacts > 0): ?>
                                    <span class="notification-badge">
                                        <?php echo $unread_contacts > 99 ? '99+' : $unread_contacts; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span>Notifications</span>
                        </a>
                    </div>

                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-amber-600/60 text-white hover:bg-amber-600/80 transition-all border border-amber-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span>Dashboard</span>
                    </a>


                    <a href="messages.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-amber-300 hover:bg-slate-600/80 transition-all border border-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <span>Messages</span>
                    </a>

                    <a href="change_password.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-slate-200 hover:bg-slate-600/80 transition-all border border-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a6 6 0 01-6 6 6 6 0 01-6-6 6 6 0 016-6m0 0V5a2 2 0 112 2h-2zm0 0v2a2 2 0 01-2 2H9a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2h-2z"></path></svg>
                        <span>Change Password</span>
                    </a>

                    <a href="posts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-slate-200 hover:bg-slate-600/80 transition-all border border-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V9m-6 0h6"></path></svg>
                        <span>Posts</span>
                    </a>

                    <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-red-600/60 text-white hover:bg-red-600/80 transition-all border border-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
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
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-white">Dashboard Statistics</h2>
            <p class="text-slate-400 mt-1">Overview of your admin panel activity</p>
        </div>

        <!-- ALL 4 STAT CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Contacts -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Total Contact Messages</p>
                        <p class="text-3xl font-bold text-white"><?php echo $total_contacts; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
            </div>

            <!-- Unread Contacts -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Unread Messages</p>
                        <p class="text-3xl font-bold text-red-400"><?php echo $unread_contacts; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <?php if ($unread_contacts > 0): ?>
                    <a href="dashboard.php?mark_read=1" class="text-xs text-amber-400 hover:underline mt-2 block">Mark All Read</a>
                <?php endif; ?>
            </div>

            <!-- Total Posts -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Total Posts</p>
                        <p class="text-3xl font-bold text-white"><?php echo $total_posts; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V9m-6 0h6"></path>
                    </svg>
                </div>
            </div>

            <!-- Total Admins -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-400 text-sm">Total Admins</p>
                        <p class="text-3xl font-bold text-white"><?php echo $total_admins; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- POST STATUS CHART – WIDER, GREEN & ORANGE -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h3 class="text-lg font-semibold mb-4 text-white">Post Status Distribution</h3>
                <div class="chart-container">
                    <canvas id="postStatusChart"></canvas>
                </div>
            </div>

            <!-- SERVICES CHART -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h3 class="text-lg font-semibold mb-4 text-white">Contact Services</h3>
                <div class="chart-container">
                    <canvas id="servicesChart"></canvas>
                </div>
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
        // Mobile Menu
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMenuBtn = document.getElementById('closeMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');

        function openMenu() {
            if (window.innerWidth >= 768) return;
            mobileMenu.classList.remove('closed'); mobileMenu.classList.add('open');
            mobileOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeMenu() {
            mobileMenu.classList.remove('open'); mobileMenu.classList.add('closed');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
        mobileMenuBtn?.addEventListener('click', openMenu);
        closeMenuBtn?.addEventListener('click', closeMenu);
        mobileOverlay?.addEventListener('click', closeMenu);
        window.addEventListener('resize', () => { if (window.innerWidth >= 768) closeMenu(); });

        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            // POST STATUS – GREEN & ORANGE
            const postCtx = document.getElementById('postStatusChart').getContext('2d');
            new Chart(postCtx, {
                type: 'bar',
                data: {
                    labels: ['Published', 'Draft'],
                    datasets: [{
                        label: 'Posts',
                        data: [<?php echo $published_posts; ?>, <?php echo $draft_posts; ?>],
                        backgroundColor: ['#10b981', '#f97316'],
                        borderColor: ['#10b981', '#f97316'],
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { 
                        y: { beginAtZero: true, ticks: { color: '#cbd5e1' }, grid: { color: '#334155' } },
                        x: { ticks: { color: '#cbd5e1' }, grid: { color: '#334155' } }
                    },
                    plugins: { legend: { display: false } }
                }
            });

            // SERVICES CHART
            const servicesCtx = document.getElementById('servicesChart').getContext('2d');
            new Chart(servicesCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo '"' . implode('", "', array_column($services, 'service')) . '"'; ?>],
                    datasets: [{
                        data: [<?php echo implode(', ', array_column($services, 'count')); ?>],
                        backgroundColor: ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899'],
                        borderWidth: 2,
                        borderColor: '#1e293b',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#cbd5e1', padding: 15 } } }
                }
            });
        });
    </script>
</body>
</html>