<?php 
require_once 'config.php'; 

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Delete message
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: messages.php');
    exit;
}

// Fetch contacts
$query = "SELECT * FROM contacts ORDER BY submitted_at DESC";
$result = $conn->query($query);
$contacts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Unique Furniture</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .no-wrap { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .message-container { display: inline-block; width: 100%; }
        .short-text { 
            display: block; 
            width: 100%; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
            max-width: 200px;
        }
        .full-text { display: none; word-wrap: break-word; white-space: pre-wrap; }
        .full-text.show { display: block; }
        .read-toggle { font-size: 0.75rem; margin-left: 0.25rem; vertical-align: middle; }

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

    <!-- Header -->
    <nav class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <h1 class="text-xl font-bold text-white">Unique Furniture</h1>

                <!-- Desktop Menu (Always Visible on md+) -->
                <div class="hidden md:flex items-center space-x-3">
                    <span class="text-sm text-slate-300">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>

                    <!-- Improved: Darker, more readable backgrounds -->
                    <a href="dashboard.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-amber-300 hover:text-amber-200 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <span>Messages</span>
                    </a>

                 <a href="statistics.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-amber-600/30 text-amber-300 hover:bg-amber-600/50 transition-all border border-amber-500/50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <span>Statistics</span>
                    </a>


                    <a href="change_password.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-slate-200 hover:text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
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

                <!-- Mobile Burger Button (Only on small screens) -->
                <button id="mobileMenuBtn" class="md:hidden p-2 rounded-lg hover:bg-white/10 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Slide-In Menu (Only visible on <md) -->
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

                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-amber-600/30 text-amber-300 hover:bg-amber-600/50 transition-all border border-amber-500/50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <span>Messages</span>
                    </a>

                     <a href="statistics.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-amber-600/30 text-amber-300 hover:bg-amber-600/50 transition-all border border-amber-500/50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <span>Statistics</span>
                    </a>

                    <a href="change_password.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-slate-200 hover:bg-slate-600/80 transition-all border border-slate-600">
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

        <!-- Mobile Overlay (Only on small screens) -->
        <div id="mobileOverlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-white">Contact Messages</h2>
            <p class="text-slate-400 mt-1" id="countDisplay"><?php echo count($contacts); ?> total messages</p>
        </div>

        <!-- Search Input -->
        <div class="mb-6">
            <input 
                type="text" 
                id="searchInput"
                placeholder="Search by name, email, phone, service, or message..."
                class="w-full max-w-md px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent backdrop-blur-sm transition-all"
                onkeyup="filterTable()"
            >
        </div>

        <?php if (empty($contacts)): ?>
            <div class="text-center py-20">
                <p class="text-slate-500 text-lg">No messages yet.</p>
            </div>
        <?php else: ?>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl overflow-hidden border border-white/20">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full" id="contactsTable">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider no-wrap">S.N</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider no-wrap">Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider no-wrap">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider no-wrap">Phone</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider no-wrap">Service</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider no-wrap">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider no-wrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10" id="tableBody">
                            <?php foreach ($contacts as $index => $c): ?>
                                <?php 
                                $msg = $c['message'];
                                $isLong = strlen($msg) > 40;
                                $short = $isLong ? substr($msg, 0, 40) . '...' : $msg;
                                $searchText = strtolower($c['name'] . ' ' . $c['email'] . ' ' . ($c['phone'] ?? '') . ' ' . ($c['service'] ?? '') . ' ' . $msg);
                                ?>
                                <tr class="hover:bg-white/5 transition-all searchable-row" data-search="<?php echo $searchText; ?>">
                                    <td class="px-6 py-4 text-sm text-amber-400 font-medium no-wrap"><?php echo $index + 1; ?></td>
                                    <td class="px-6 py-4 text-sm no-wrap"><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td class="px-6 py-4 text-sm text-blue-300 no-wrap"><?php echo htmlspecialchars($c['email']); ?></td>
                                    <td class="px-6 py-4 text-sm no-wrap"><?php echo htmlspecialchars($c['phone'] ?? '—'); ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 text-xs rounded-full bg-amber-500/20 text-amber-300 whitespace-nowrap inline-block">
                                            <?php echo htmlspecialchars($c['service'] ?? '—'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="message-container">
                                            <span class="short-text"><?php echo htmlspecialchars($short); ?></span>
                                            <?php if ($isLong): ?>
                                                <span class="full-text"><?php echo nl2br(htmlspecialchars($msg)); ?></span>
                                                <button onclick="toggleMessage(this)" class="text-amber-400 read-toggle hover:underline">Read more</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-400 no-wrap">
                                        <?php echo date('M j, Y<br>g:i A', strtotime($c['submitted_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button 
                                            onclick="return confirm('Delete this message?') ? window.location.href='dashboard.php?delete=<?php echo $c['id']; ?>' : false;"
                                            class="bg-red-500/20 hover:bg-red-500/40 text-red-300 hover:text-red-200 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 group"
                                        >
                                            <svg class="w-4 h-4 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M14 3h-4"></path>
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="mt-20 py-8 border-t border-white/10 text-center text-slate-400 text-sm">
        <p>Copyrights © <?php echo date('Y'); ?> Unique Furniture. All rights reserved. | Admin Panel v1.0</p>
        <p class="mt-2">Developed By <span class="text-amber-400">Y-Global System Solution</span></p>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile Menu (Only active on small screens)
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMenuBtn = document.getElementById('closeMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');

        function openMenu() {
            if (window.innerWidth >= 768) return; // Prevent on large screens
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

        // Auto-close mobile menu on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeMenu();
            }
        });

        // Message Toggle
        function toggleMessage(btn) {
            const container = btn.parentElement;
            const short = container.querySelector('.short-text');
            const full = container.querySelector('.full-text');
            if (full.classList.contains('show')) {
                full.classList.remove('show');
                short.style.display = 'block';
                btn.textContent = 'Read more';
            } else {
                full.classList.add('show');
                short.style.display = 'none';
                btn.textContent = 'Read less';
            }
        }

        // Search Filter
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody .searchable-row');
            const countDisplay = document.getElementById('countDisplay');
            let visibleCount = 0;
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (searchData.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            countDisplay.textContent = visibleCount === <?php echo count($contacts); ?> 
                ? '<?php echo count($contacts); ?> total messages'
                : visibleCount + ' visible messages (filtered)';
        }
    </script>
</body>
</html>