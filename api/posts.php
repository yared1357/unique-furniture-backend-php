<?php
// api/posts.php
require_once 'config.php';

ob_start();
header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // === SINGLE POST ===
    if (isset($_GET['action']) && $_GET['action'] === 'single') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['error' => 'Invalid ID']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT id, title, content, excerpt, images, created_at 
            FROM posts 
            WHERE id = ? AND status = 'published'
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();

        if (!$post) {
            http_response_code(404);
            echo json_encode(['error' => 'Post not found']);
            exit;
        }

        if ($post['images']) {
            $imgs = json_decode($post['images'], true);
            $post['images'] = array_map('absUrl', $imgs);
        } else {
            $post['images'] = [];
        }

        echo json_encode($post);
        exit;
    }

    // === LIST POSTS (NOW INCLUDES `content`) ===
    $search = trim($_GET['search'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 6;
    $offset = ($page - 1) * $limit;

    $where = $search ? "WHERE title LIKE ? OR content LIKE ?" : '';
    $countSql = "SELECT COUNT(*) AS total FROM posts $where";
    $stmt = $conn->prepare($countSql);
    if ($search) {
        $like = "%$search%";
        $stmt->bind_param('ss', $like, $like);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    // ADD `content` HERE
    $sql = "SELECT id, title, excerpt, content, images, created_at FROM posts $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if ($search) {
        $like = "%$search%";
        $stmt->bind_param('ssii', $like, $like, $limit, $offset);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($posts as &$post) {
        if ($post['images']) {
            $imgs = json_decode($post['images'], true);
            $post['images'] = array_map('absUrl', $imgs);
        } else {
            $post['images'] = [];
        }
    }

    echo json_encode([
        'posts' => $posts,
        'total' => (int)$total,
        'pages' => ceil($total / $limit)
    ]);
    exit;
}

// === ADMIN ONLY (unchanged) ===
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $title   = $data['title'] ?? '';
    $content = $data['content'] ?? '';
    $excerpt = $data['excerpt'] ?? '';
    $status  = $data['status'] ?? 'draft';
    $images  = $data['images'] ?? '[]';
    $slug    = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

    $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, excerpt, images, status) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ssssss', $title, $slug, $content, $excerpt, $images, $status);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok, 'id' => $conn->insert_id]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $data);
    $id      = (int)($data['id'] ?? 0);
    $title   = $data['title'] ?? '';
    $content = $data['content'] ?? '';
    $excerpt = $data['excerpt'] ?? '';
    $status  = $data['status'] ?? 'draft';
    $images  = $data['images'] ?? '[]';
    $slug    = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

    $stmt = $conn->prepare("UPDATE posts SET title=?, slug=?, content=?, excerpt=?, images=?, status=? WHERE id=?");
    $stmt->bind_param('ssssssi', $title, $slug, $content, $excerpt, $images, $status, $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT images FROM posts WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && $row['images']) {
        $imgs = json_decode($row['images'], true);
        foreach ($imgs as $img) {
            $path = '../' . str_replace(BASE_URL . '/', '', $img);
            if (file_exists($path)) unlink($path);
        }
    }
    $del = $conn->prepare("DELETE FROM posts WHERE id=?");
    $del->bind_param('i', $id);
    echo json_encode(['success' => $del->execute()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>