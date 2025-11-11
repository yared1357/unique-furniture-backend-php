<?php
// admin/posts.php
session_start();

$host = 'localhost';
$db   = 'ceilcraft_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('DB error');

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
}
if (!isAdmin()) { header('Location: login.php'); exit; }

/* ---------- FORM SUBMIT ---------- */
if ($_POST) {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status  = $_POST['status'] ?? 'draft';
    $slug    = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $keep    = $_POST['keep_images'] ?? [];
    $new     = [];

    // Upload new images
    if (!empty($_FILES['images']['name'][0])) {
        $dir = '../uploads/posts/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
            $ext = strtolower(pathinfo($_FILES['images']['name'][$k], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $name = uniqid('post_') . '.' . $ext;
                if (move_uploaded_file($tmp, $dir . $name)) {
                    $new[] = 'uploads/posts/' . $name;
                }
            }
        }
    }
    $all = array_merge($keep, $new);
    $json = json_encode($all);

    if (!empty($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $stmt = $conn->prepare(
            "UPDATE posts SET title=?, slug=?, content=?, excerpt=?, images=?, status=? WHERE id=?"
        );
        $stmt->bind_param('ssssssi', $title, $slug, $content, $excerpt, $json, $status, $id);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO posts (title, slug, content, excerpt, images, status) VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param('ssssss', $title, $slug, $content, $excerpt, $json, $status);
    }
    $stmt->execute();
    header('Location: posts.php'); exit;
}

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT images FROM posts WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && $row['images']) {
        $imgs = json_decode($row['images'], true);
        foreach ($imgs as $img) {
            $path = '../' . $img;
            if (file_exists($path)) unlink($path);
        }
    }
    $del = $conn->prepare("DELETE FROM posts WHERE id=?");
    $del->bind_param('i', $id);
    $del->execute();
    header('Location: posts.php'); exit;
}

/* ---------- MARK ALL AS READ ---------- */
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE contacts SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    $stmt->close();
    header('Location: posts.php');
    exit;
}

/* ---------- UNREAD COUNT ---------- */
$unread_result = $conn->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0");
$unread_contacts = $unread_result ? $unread_result->fetch_row()[0] : 0;

/* ---------- EDIT MODE ---------- */
$editPost = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editPost = $stmt->get_result()->fetch_assoc();
}

/* ---------- SEARCH & LIST ---------- */
$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE title LIKE ? OR content LIKE ?" : '';
$sql    = "SELECT * FROM posts $where ORDER BY created_at DESC";
$stmt   = $conn->prepare($sql);
if ($search) {
    $like = "%$search%";
    $stmt->bind_param('ss', $like, $like);
}
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Posts | Unique Furniture</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .img-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: .5rem; }
        .remove-btn { top: -6px; right: -6px; }

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

    <!-- Header (Same as messages.php) -->
    <nav class="bg-white/10 backdrop-blur-md border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <h1 class="text-xl font-bold text-white">Unique Furniture</h1>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-3">
                    <span class="text-sm text-slate-300">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>!</span>

                    <!-- Bell + Badge -->
                    <div class="relative">
                        <a href="posts.php?mark_read=1" class="relative p-2 rounded-lg hover:bg-white/10 transition-all block">
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="messages.php" class="bg-slate-700/70 hover:bg-slate-600/80 text-amber-300 hover:text-amber-200 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2 border border-slate-600">
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
                        <a href="posts.php?mark_read=1" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-amber-300 hover:bg-slate-600/80 transition-all border border-slate-600">
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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="messages.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-slate-700/70 text-amber-300 hover:bg-slate-600/80 transition-all border border-slate-600">
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

        <!-- SEARCH -->
        <form method="GET" class="mb-6">
            <input type="text" name="search" placeholder="Search posts…" value="<?= htmlspecialchars($search) ?>"
                   class="w-full max-w-md px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 backdrop-blur-sm transition-all">
        </form>

        <!-- CREATE / EDIT FORM -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-8 border border-white/20">
            <h2 class="text-xl font-bold mb-4"><?= $editPost ? 'Edit Post' : 'Create Post' ?></h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php if ($editPost): ?><input type="hidden" name="edit_id" value="<?= $editPost['id'] ?>"><?php endif; ?>
                <input type="text" name="title" placeholder="Title *" required value="<?= $editPost ? htmlspecialchars($editPost['title']) : '' ?>" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <textarea name="content" placeholder="Content *" rows="8" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500"><?= $editPost ? htmlspecialchars($editPost['content']) : '' ?></textarea>
                <input type="text" name="excerpt" placeholder="Excerpt" value="<?= $editPost ? htmlspecialchars($editPost['excerpt']) : '' ?>" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500">

                <div id="imagePreview" class="flex flex-wrap gap-2 mb-2">
                    <?php if ($editPost && $editPost['images']):
                        $imgs = json_decode($editPost['images'], true);
                        foreach ($imgs as $img): ?>
                            <div class="relative inline-block">
                                <img src="../<?= $img ?>" class="img-thumb">
                                <button type="button" onclick="removeExisting(this,'<?= $img ?>')" class="absolute remove-btn bg-red-600 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center">X</button>
                                <input type="hidden" name="keep_images[]" value="<?= $img ?>">
                            </div>
                    <?php endforeach; endif; ?>
                </div>

                <input type="file" name="images[]" multiple accept="image/*"
                       class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-white hover:file:bg-amber-600"
                       onchange="previewNew(this)">

                <div class="flex items-center space-x-4 mt-4">
                    <select name="status" class="px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white">
                        <option value="draft" <?= ($editPost && $editPost['status']==='draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($editPost && $editPost['status']==='published') ? 'selected' : '' ?>>Published</option>
                    </select>
                    <button type="submit" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold py-2 px-6 rounded-lg transition-all transform hover:scale-105 shadow-lg">
                        <?= $editPost ? 'Update' : 'Create' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- TABLE -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl overflow-hidden border border-white/20">
            <div class="overflow-x-auto">
                <table class="w-full min-w-full">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Images</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        <?php foreach ($posts as $p): ?>
                            <tr class="hover:bg-white/5 transition-all">
                                <td class="px-6 py-4 text-sm"><?= htmlspecialchars($p['title']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full <?= $p['status']==='published' ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300' ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm"><?= count(json_decode($p['images'] ?? '[]', true)) ?></td>
                                <td class="px-6 py-4 text-xs text-slate-400"><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                                <td class="px-6 py-4 text-sm space-x-3">
                                    <a href="?edit=<?= $p['id'] ?>" class="text-amber-400 hover:underline">Edit</a>
                                    <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this post?')" class="text-red-400 hover:underline">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
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
        const mobileMenu = document.getElementById('mobileMenu');  // Fixed ID
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

        // Image Preview & Remove
        function previewNew(input) {
            const preview = document.getElementById('imagePreview');
            Array.from(input.files).forEach(file => {
                const r = new FileReader();
                r.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'relative inline-block';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="img-thumb">
                        <button type="button" onclick="this.parentElement.remove()" 
                                class="absolute remove-btn bg-red-600 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center">
                            X
                        </button>`;
                    preview.appendChild(div);
                };
                r.readAsDataURL(file);
            });
        }

        function removeExisting(btn, path) {
            btn.parentElement.remove();
            document.querySelector(`input[value="${path}"]`)?.remove();
        }
    </script>
</body>
</html>