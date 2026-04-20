<?php
require_once __DIR__ . '/../database/db.php';
sendCommonHeaders();

startSecureSession();
$user   = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$pdo    = getDB();

switch ($method) {

    // ── GET ───────────────────────────────────────────────
    case 'GET':
        $id = $input['id'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       u1.name AS owner_name, u1.email AS owner_email,
                       u2.name AS operator_name
                FROM containers c
                LEFT JOIN users u1 ON c.owner_id    = u1.id
                LEFT JOIN users u2 ON c.operator_id = u2.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $container = $stmt->fetch();
            if (!$container) jsonResponse(['error' => 'Kontainer tidak ditemukan'], 404);

            if ($user['role'] === 'stakeholder' && $container['owner_id'] != $user['id']) {
                jsonResponse(['error' => 'Akses ditolak'], 403);
            }

            $stmt = $pdo->prepare("SELECT * FROM events WHERE container_id = ? ORDER BY timestamp ASC");
            $stmt->execute([$id]);
            $container['events'] = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT d.*, u.name AS uploader_name FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.container_id = ? ORDER BY d.created_at DESC");
            $stmt->execute([$id]);
            $container['documents'] = $stmt->fetchAll();

            jsonResponse($container);

        } else {
            $where  = [];
            $params = [];

            if ($user['role'] === 'stakeholder') {
                $where[]  = 'c.owner_id = ?';
                $params[] = $user['id'];
            } elseif ($user['role'] === 'operator') {
                $where[]  = 'c.operator_id = ?';
                $params[] = $user['id'];
            }

            if (!empty($input['status'])) {
                $where[]  = 'c.status = ?';
                $params[] = $input['status'];
            }

            if (!empty($input['search'])) {
                $where[]  = '(c.id LIKE ? OR c.booking_no LIKE ? OR c.vessel LIKE ? OR c.commodity LIKE ?)';
                $s = '%' . $input['search'] . '%';
                array_push($params, $s, $s, $s, $s);
            }

            $sql = "SELECT c.*, u1.name AS owner_name, u2.name AS operator_name
                    FROM containers c
                    LEFT JOIN users u1 ON c.owner_id    = u1.id
                    LEFT JOIN users u2 ON c.operator_id = u2.id";
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY c.created_at DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    // ── POST: Create ──────────────────────────────────────
    case 'POST':
        if (!in_array($user['role'], ['admin', 'operator'])) {
            jsonResponse(['error' => 'Akses ditolak'], 403);
        }

        $id = strtoupper(trim($input['id'] ?? ''));
        if (!$id) jsonResponse(['error' => 'ID Kontainer wajib diisi'], 400);

        $check = $pdo->prepare("SELECT id FROM containers WHERE id = ?");
        $check->execute([$id]);
        if ($check->fetch()) jsonResponse(['error' => 'ID kontainer sudah digunakan'], 409);

        $stmt = $pdo->prepare("
            INSERT INTO containers 
            (id, booking_no, vessel, voyage, type, weight, commodity, origin, destination, eta,
             status, owner_id, operator_id, position_lat, position_lng, position_desc)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $id,
            $input['booking_no']    ?? '',
            $input['vessel']        ?? '',
            $input['voyage']        ?? '',
            $input['type']          ?? '20ft Dry',
            intval($input['weight'] ?? 0),
            $input['commodity']     ?? '',
            $input['origin']        ?? '',
            $input['destination']   ?? '',
            $input['eta']           ?: null,
            $input['status']        ?? 'booking',
            intval($input['owner_id']    ?? 0) ?: null,
            intval($input['operator_id'] ?? $user['id']),
            floatval($input['position_lat'] ?? -7.2575),
            floatval($input['position_lng'] ?? 112.7521),
            $input['position_desc'] ?? '',
        ]);

        // Log event
        $pdo->prepare("INSERT INTO events (id,container_id,event,actor,note) VALUES (?,?,?,?,?)")
            ->execute([genId('EVT'), $id, 'Kontainer Terdaftar', 'Admin: '.$user['name'], 'Kontainer baru ditambahkan ke sistem']);

        // Notif admin
        $pdo->prepare("INSERT INTO notifications (id,user_id,container_id,message,type) VALUES (?,?,?,?,?)")
            ->execute([genId('NTF'), 1, $id, "Kontainer baru terdaftar: $id", 'info']);

        jsonResponse(['success' => true, 'id' => $id]);
        break;

    // ── PUT: Update ───────────────────────────────────────
    case 'PUT':
        $id = $input['id'] ?? '';
        if (!$id) jsonResponse(['error' => 'ID wajib diisi'], 400);

        $stmt = $pdo->prepare("SELECT * FROM containers WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) jsonResponse(['error' => 'Kontainer tidak ditemukan'], 404);

        if ($user['role'] === 'operator' && $existing['operator_id'] != $user['id']) {
            jsonResponse(['error' => 'Akses ditolak'], 403);
        }

        $oldStatus = $existing['status'];
        $newStatus = $input['status'] ?? $oldStatus;

        $stmt = $pdo->prepare("
            UPDATE containers SET
                booking_no    = ?, vessel        = ?, voyage        = ?,
                type          = ?, weight        = ?, commodity     = ?,
                origin        = ?, destination   = ?, eta           = ?,
                status        = ?, owner_id      = ?, operator_id   = ?,
                position_lat  = ?, position_lng  = ?, position_desc = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $input['booking_no']    ?? $existing['booking_no'],
            $input['vessel']        ?? $existing['vessel'],
            $input['voyage']        ?? $existing['voyage'],
            $input['type']          ?? $existing['type'],
            intval($input['weight'] ?? $existing['weight']),
            $input['commodity']     ?? $existing['commodity'],
            $input['origin']        ?? $existing['origin'],
            $input['destination']   ?? $existing['destination'],
            ($input['eta'] ?? $existing['eta']) ?: null,
            $newStatus,
            intval($input['owner_id']    ?? $existing['owner_id']) ?: null,
            intval($input['operator_id'] ?? $existing['operator_id']),
            floatval($input['position_lat'] ?? $existing['position_lat']),
            floatval($input['position_lng'] ?? $existing['position_lng']),
            $input['position_desc'] ?? $existing['position_desc'],
            $id
        ]);

        // Log jika status berubah
        if ($oldStatus !== $newStatus) {
            $labels = [
                'booking'=>'Booking Diterima','gate_in'=>'Gate-In Terminal',
                'on_vessel'=>'Di Atas Kapal','discharged'=>'Dibongkar',
                'clearance'=>'Clearance Bea Cukai','on_delivery'=>'Dalam Pengiriman',
                'gate_in_depo'=>'Gate-In Depo','completed'=>'Selesai','delay'=>'Delay'
            ];
            $label   = $labels[$newStatus] ?? $newStatus;
            $ownerId = intval($input['owner_id'] ?? $existing['owner_id']);

            $pdo->prepare("INSERT INTO events (id,container_id,event,actor,note) VALUES (?,?,?,?,?)")
                ->execute([genId('EVT'), $id, $label, ucfirst($user['role']).': '.$user['name'], $input['note'] ?? '']);

            if ($ownerId) {
                $pdo->prepare("INSERT INTO notifications (id,user_id,container_id,message,type) VALUES (?,?,?,?,?)")
                    ->execute([genId('NTF'), $ownerId, $id, "Status $id diupdate: $label", 'info']);
            }
        }

        jsonResponse(['success' => true]);
        break;

    // ── DELETE ────────────────────────────────────────────
    case 'DELETE':
        if ($user['role'] !== 'admin') jsonResponse(['error' => 'Akses ditolak'], 403);
        $id = $input['id'] ?? '';
        if (!$id) jsonResponse(['error' => 'ID wajib diisi'], 400);

        // Hapus file upload terkait
        $stmt = $pdo->prepare("SELECT filepath FROM documents WHERE container_id = ?");
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $doc) {
            if ($doc['filepath']) {
                $full = __DIR__ . '/../' . $doc['filepath'];
                if (file_exists($full)) unlink($full);
            }
        }

        $pdo->prepare("DELETE FROM containers WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Method tidak diizinkan'], 405);
}
