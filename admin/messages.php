<?php 
require_once 'config.php'; 

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// === DELETE MESSAGE ===
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: messages.php');
    exit;
}

// === MARK ALL AS READ ===
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE contacts SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    $stmt->close();
    header('Location: messages.php');
    exit;
}

// === FETCH UNREAD COUNT ===
$unread_result = $conn->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0");
$unread_contacts = $unread_result ? $unread_result->fetch_row()[0] : 0;

// === FETCH ALL MESSAGES ===
$query = "SELECT * FROM contacts ORDER BY submitted_at DESC";
$result = $conn->query($query);
$contacts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Messages | Unique Furniture</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Text Truncation */
        .no-wrap { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .short-text { 
            display: block; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
            max-width: 200px;
        }
        .full-text { 
            display: none; 
            word-wrap: break-word; 
            white-space: pre-wrap; 
            max-width: 100%;
        }
        .full-text.show { display: block; }
        .read-toggle { 
            font-size: 0.75rem; 
            margin-left: 0.25rem; 
            vertical-align: middle; 
            color: #f59e0b;
            cursor: pointer;
        }
        .read-toggle:hover { text-decoration: underline; }

        /* Mobile Menu */
        @media (min-width: 768px) {
            #mobileMenu, #mobileOverlay { display: none !important; }
        }
        @media (max-width: 767px) {
            .mobile-menu { 
                transition: transform 0.3s ease-in-out; 
                transform: translateX(-100%);
            }
            .mobile-menu.open { transform: translateX(0); }
        }

        /* Notification Badge */
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
                    <span class="text-sm text-slate-300">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>!</span>

                    <!-- Bell with Badge -->
                    <div class="relative">
                        <a href="messages.php?mark_read=1" class="relative p-2 rounded-lg hover:bg-white/10 transition-all block">
                            <svg class="w-6 h-6 text-amber-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <?php if ($unread_contacts > 0): ?>
                                <span class="notification-badge">
                                    <?= $unread_contacts > 99 ? '99+' : $unread_contacts ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Nav Links -->
                    <a href="dashboard.php" class="bg-amber-600/60 hover:bg-amber-600/80 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-amber-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="messages.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-amber-300 hover:text-amber-200 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <span>Messages</span>
                    </a>

                    <a href="change_password.php" class="bg-amber-600/60 hover:bg-amber-600/80 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-amber-500">
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
        <div id="mobileMenu" class="fixed inset-y-0 left-0 w-64 bg-slate-800/95 backdrop-blur-xl border-r border-white/20 z-40 mobile-menu md:hidden">
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
                    <p class="text-sm text-slate-400 mb-4">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>!</p>

                    <!-- Mobile Bell -->
                    <div class="relative mb-4">
                        <a href="messages.php?mark_read=1" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-amber-300 hover:bg-slate-600/80 transition-all border border-slate-600">
                            <div class="relative">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                <?php if ($unread_contacts > 0): ?>
                                    <span class="notification-badge">
                                        <?= $unread_contacts > 99 ? '99+' : $unread_contacts ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span>Notifications</span>
                        </a>
                    </div>

                    <!-- Mobile Links -->
                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-amber-600/60 text-white hover:bg-amber-600/80 transition-all border border-amber-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="messages.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-amber-300 hover:bg-slate-600/80 transition-all border border-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <span>Messages</span>
                    </a>

                    <a href="change_password.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-amber-600/60 text-white hover:bg-amber-600/80 transition-all border border-amber-500">
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
            <h2 class="text-3xl font-bold text-white">Contact Messages</h2>
            <p class="text-slate-400 mt-1" id="countDisplay"><?= count($contacts) ?> total messages</p>
        </div>

        <!-- Search -->
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
                    <table class="w-full" id="contactsTable">
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
                            <?php foreach ($contacts as $index => $c): 
                                $msg = $c['message'];
                                $isLong = strlen($msg) > 40;
                                $short = $isLong ? substr($msg, 0, 40) . '...' : $msg;
                                $searchText = strtolower($c['name'] . ' ' . $c['email'] . ' ' . ($c['phone'] ?? '') . ' ' . ($c['service'] ?? '') . ' ' . $msg);
                            ?>
                                <tr class="hover:bg-white/5 transition-all searchable-row" data-search="<?= htmlspecialchars($searchText) ?>">
                                    <td class="px-6 py-4 text-sm text-amber-400 font-medium no-wrap"><?= $index + 1 ?></td>
                                    <td class="px-6 py-4 text-sm no-wrap"><?= htmlspecialchars($c['name']) ?></td>
                                    <td class="px-6 py-4 text-sm text-blue-300 no-wrap"><?= htmlspecialchars($c['email']) ?></td>
                                    <td class="px-6 py-4 text-sm no-wrap"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-3 py-1 text-xs rounded-full bg-amber-500/20 text-amber-300 whitespace-nowrap inline-block">
                                            <?= htmlspecialchars($c['service'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="message-container">
                                            <span class="short-text"><?= htmlspecialchars($short) ?></span>
                                            <?php if ($isLong): ?>
                                                <span class="full-text"><?= nl2br(htmlspecialchars($msg)) ?></span>
                                                <button onclick="toggleMessage(this)" class="read-toggle">Read more</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-400 no-wrap">
                                        <?= date('M j, Y<br>g:i A', strtotime($c['submitted_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button 
                                            onclick="return confirm('Delete this message?') ? window.location.href='messages.php?delete=<?= $c['id'] ?>' : false;"
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
        <p>Copyrights © <?= date('Y') ?> Unique Furniture. All rights reserved. | Admin Panel v1.0</p>
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
            mobileMenu.classList.add('open');
            mobileOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            mobileMenu.classList.remove('open');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        mobileMenuBtn?.addEventListener('click', openMenu);
        closeMenuBtn?.addEventListener('click', closeMenu);
        mobileOverlay?.addEventListener('click', closeMenu);

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) closeMenu();
        });

        // Toggle Message
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
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody .searchable-row');
            const countDisplay = document.getElementById('countDisplay');
            let visible = 0;

            rows.forEach(row => {
                const text = row.dataset.search;
                if (text.includes(input)) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            const total = <?= count($contacts) ?>;
            countDisplay.textContent = visible === total 
                ? `${total} total messages`
                : `${visible} visible messages (filtered)`;
        }
    </script>
</body>
</html>