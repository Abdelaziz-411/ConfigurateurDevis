<?php
session_start();
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $mot_de_passe = $_POST['password'];

    try {
        // Récupérer les informations de l'utilisateur
        $sql = "SELECT id, nom, email, mot_de_passe, role FROM utilisateurs WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug
        error_log("Tentative de connexion pour : " . $email);
        error_log("Utilisateur trouvé : " . ($utilisateur ? "Oui" : "Non"));

        // Vérifier le mot de passe
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            error_log("Mot de passe correct");
            
            // Connexion réussie
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
            $_SESSION['email'] = $utilisateur['email'];
            $_SESSION['role'] = $utilisateur['role'];

            error_log("Session initialisée : " . print_r($_SESSION, true));

            // Rediriger selon le rôle
            if ($utilisateur['role'] === 'admin') {
                error_log("Redirection vers index.php");
                header('Location: index.php');
                exit();
            } else {
                error_log("Rôle non admin");
                $_SESSION['error'] = "Vous n'avez pas les droits d'accès à l'administration.";
                header('Location: login.php');
                exit();
            }
        } else {
            error_log("Mot de passe incorrect");
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO : " . $e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer plus tard.";
    }

    header('Location: login.php');
    exit();
} 