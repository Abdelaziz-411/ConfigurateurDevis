<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si l'utilisateur a les droits d'accès (admin ou modérateur)
if (!in_array($_SESSION['role'], ['admin', 'modérateur'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si le compte est actif
if ($_SESSION['user_statut'] !== 'actif') {
    session_destroy();
    header('Location: login.php');
    exit();
} 