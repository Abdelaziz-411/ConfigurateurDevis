<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si l'utilisateur a les droits d'accès (admin uniquement)
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
} 