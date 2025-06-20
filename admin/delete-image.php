<?php
require_once '../config.php';
header('Content-Type: application/json');

try {
    $type = $_POST['type'] ?? 'modele';
    $id = $_POST['id'] ?? null;
    $image = $_POST['image'] ?? null;

    $tables = [
        'modele' => [
            'table' => 'modele_images',
            'id_col' => 'id',
            'img_col' => 'image_path',
            'folder' => '../images/modeles/'
        ],
        'option' => [
            'table' => 'option_images',
            'id_col' => 'id_option',
            'img_col' => 'image_path',
            'folder' => '../images/options/'
        ],
        'kit' => [
            'table' => 'kit_images',
            'id_col' => 'id_kit',
            'img_col' => 'filename',
            'folder' => '../images/kits/'
        ]
    ];

    if (!isset($tables[$type])) {
        echo json_encode(['success' => false, 'message' => 'Type invalide']);
        exit;
    }

    $table = $tables[$type]['table'];
    $id_col = $tables[$type]['id_col'];
    $img_col = $tables[$type]['img_col'];
    $folder = $tables[$type]['folder'];

    if ($type === 'modele' && $id) {
        $stmt = $pdo->prepare("SELECT $img_col FROM $table WHERE $id_col = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $path = $folder . $row[$img_col];
            if (file_exists($path)) {
                unlink($path);
            }
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_col = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Image non trouvée']);
            exit;
        }
    } elseif ($image && $id) {
        $stmt = $pdo->prepare("SELECT $img_col FROM $table WHERE $id_col = ? AND $img_col = ?");
        $stmt->execute([$id, $image]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $path = $folder . $row[$img_col];
            if (file_exists($path)) {
                unlink($path);
            }
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_col = ? AND $img_col = ?");
            $stmt->execute([$id, $image]);
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Image non trouvée']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
    exit;
}