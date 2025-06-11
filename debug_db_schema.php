<?php
require_once 'config.php';

header('Content-Type: text/plain');

try {
    $test_type_carrosserie = 'L1H2'; // Type de carrosserie à tester

    echo "\n--- Vérification des Kits pour type_carrosserie = {$test_type_carrosserie} ---\n";
    $stmt_kits = $pdo->prepare("SELECT * FROM kit_vehicule_compatibilite WHERE type_carrosserie = ?");
    $stmt_kits->execute([$test_type_carrosserie]);
    $kits_data = $stmt_kits->fetchAll(PDO::FETCH_ASSOC);
    error_log("debug_db_schema.php: Kits pour {$test_type_carrosserie}: " . print_r($kits_data, true));
    print_r($kits_data);

    echo "\n--- Vérification des Options pour type_carrosserie = {$test_type_carrosserie} ---\n";
    $stmt_options = $pdo->prepare("SELECT * FROM option_vehicule_compatibilite WHERE type_carrosserie = ?");
    $stmt_options->execute([$test_type_carrosserie]);
    $options_data = $stmt_options->fetchAll(PDO::FETCH_ASSOC);
    error_log("debug_db_schema.php: Options pour {$test_type_carrosserie}: " . print_r($options_data, true));
    print_r($options_data);

} catch (PDOException $e) {
    error_log("debug_db_schema.php: Erreur PDO: " . $e->getMessage());
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    error_log("debug_db_schema.php: Erreur générale: " . $e->getMessage());
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 