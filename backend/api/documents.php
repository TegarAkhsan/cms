<?php
// api/documents.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../database/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$user   = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$pdo    = getDB();

switch ($method) {

    case 'GET':
        $id           = $input['id']           ?? null;
        $container_id = $input['container_id'] ?? null;
        $where        = [];
        $params       = [];

        if ($id) {
            $stmt = $pdo->prepare("SELECT d.*, u.name AS uploader_name FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();
            if (!$doc) jsonResponse(['error' => 'Dokumen tidak ditemukan'], 404);
            jsonResponse($doc);
        }

        if ($container_id) { $where[] = 'd.container_id = ?'; $params[] = $container_id; }

        if ($user['role'] === 'stakeholder') {
            $where[] = 'c.owner_id = ?'; $params[] = $user['id'];
        }

        if (!empty($input['status'])) { $where[] = 'd.status = ?'; $params[] = $input['status']; }

        if (!empty($input['search'])) {
            $where[] = '(d.id LIKE ? OR d.container_id LIKE ? OR d.type LIKE ?)';
            $s = '%'.$input['search'].'%';
            array_push($params, $s, $s, $s);
        }

        $sql = "SELECT d.*, u.name AS uploader_name, c.vessel
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id
                LEFT JOIN containers c ON d.container_id = c.id";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY d.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll());
        break;

    case 'POST':
        $container_id = trim($input['container_id'] ?? '');
        $type         = trim($input['type']         ?? '');
        if (!$container_id || !$type) jsonResponse(['error' => 'container_id dan type wajib diisi'], 400);

        $stmt = $pdo->prepare("SELECT * FROM containers WHERE id = ?");
        $stmt->execute([$container_id]);
        $container = $stmt->fetch();
        if (!$container) jsonResponse(['error' => 'Kontainer tidak ditemukan'], 404);

        $filepath = null;
        $filename = $input['filename'] ?? ($type . '_' . $container_id . '.pdf');

        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $origExt  = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed  = ['pdf','jpg','jpeg','png'];
            $safeExt  = in_array($origExt, $allowed) ? $origExt : 'pdf';
            $filename = genId('FILE') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $type) . '.' . $safeExt;
            $dest     = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                jsonResponse(['error' => 'Gagal menyimpan file'], 500);
            }
            // Build a web-accessible URL for the file
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'];
            // Determine the web root path for /cms/backend/uploads/
            $scriptPath = str_replace('\\', '/', __DIR__);
            $docRoot    = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
            $relDir     = ltrim(str_replace($docRoot, '', $scriptPath), '/');
            // Go up from api/ to backend/, then into uploads/
            $uploadsWebPath = '/' . dirname($relDir) . '/uploads/';
            $filepath = $baseUrl . $uploadsWebPath . $filename;
        }

        $status = ($user['role'] === 'stakeholder') ? 'pending' : 'approved';
        $docId  = genId('DOC');

        $pdo->prepare("INSERT INTO documents (id,container_id,type,filename,filepath,status,uploaded_by,notes) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$docId, $container_id, $type, $filename, $filepath, $status, $user['id'], $input['notes'] ?? '']);

        // Notif ke operator jika stakeholder upload
        if ($user['role'] === 'stakeholder' && $container['operator_id']) {
            $pdo->prepare("INSERT INTO notifications (id,user_id,container_id,message,type) VALUES (?,?,?,?,?)")
                ->execute([genId('NTF'), $container['operator_id'], $container_id, "Dokumen baru ($type) diupload untuk $container_id", 'info']);
        }

        // Notif ke owner jika operator upload
        if (in_array($user['role'], ['admin','operator']) && $container['owner_id']) {
            $pdo->prepare("INSERT INTO notifications (id,user_id,container_id,message,type) VALUES (?,?,?,?,?)")
                ->execute([genId('NTF'), $container['owner_id'], $container_id, "Dokumen ($type) telah diupload untuk $container_id", 'info']);
        }

        $pdo->prepare("INSERT INTO events (id,container_id,event,actor,note) VALUES (?,?,?,?,?)")
            ->execute([genId('EVT'), $container_id, 'Dokumen Diupload', ucfirst($user['role']).': '.$user['name'], "Tipe: $type"]);

        jsonResponse(['success' => true, 'id' => $docId, 'filename' => $filename, 'filepath' => $filepath]);
        break;

    case 'PUT':
        if (!in_array($user['role'], ['admin','operator'])) jsonResponse(['error' => 'Akses ditolak'], 403);

        $id     = $input['id']     ?? '';
        $status = $input['status'] ?? '';
        $notes  = $input['notes']  ?? '';
        if (!$id || !$status) jsonResponse(['error' => 'id dan status wajib diisi'], 400);

        $stmt = $pdo->prepare("SELECT d.*, c.owner_id FROM documents d LEFT JOIN containers c ON d.container_id = c.id WHERE d.id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if (!$doc) jsonResponse(['error' => 'Dokumen tidak ditemukan'], 404);

        $pdo->prepare("UPDATE documents SET status = ?, notes = ? WHERE id = ?")
            ->execute([$status, $notes, $id]);

        // Notif ke pemilik
        if ($doc['owner_id']) {
            $statusLabel = ['approved'=>'Disetujui ✅','revision'=>'Perlu Revisi ❌','pending'=>'Pending ⏳'];
            $label = $statusLabel[$status] ?? $status;
            $type  = $status === 'approved' ? 'success' : ($status === 'revision' ? 'danger' : 'info');
            $msg   = "Dokumen {$doc['type']} ({$doc['container_id']}): $label" . ($notes ? " — $notes" : '');

            $pdo->prepare("INSERT INTO notifications (id,user_id,container_id,message,type) VALUES (?,?,?,?,?)")
                ->execute([genId('NTF'), $doc['owner_id'], $doc['container_id'], $msg, $type]);
        }

        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        if ($user['role'] !== 'admin') jsonResponse(['error' => 'Akses ditolak'], 403);
        $id = $input['id'] ?? '';

        $stmt = $pdo->prepare("SELECT filepath FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if ($doc && $doc['filepath']) {
            $full = __DIR__ . '/../' . $doc['filepath'];
            if (file_exists($full)) unlink($full);
        }
        $pdo->prepare("DELETE FROM documents WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;
}
