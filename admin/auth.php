<?php
session_start();
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $mot_de_passe = $_POST['password'];

    try {
        // Récupérer les informations de l'utilisateur
        $sql = "
            SELECT u.id, u.nom, u.email, u.mot_de_passe, s.libelle AS statut, r.libelle AS role
            FROM utilisateurs u
            JOIN users_statuts s ON u.statut_id = s.id
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérifier le mot de passe
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            if ($utilisateur['statut'] === 'actif') {
                // Connexion réussie
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                $_SESSION['email'] = $utilisateur['email'];
                $_SESSION['user_statut'] = $utilisateur['statut'];
                $_SESSION['role'] = $utilisateur['role'];

                // Rediriger selon le rôle
                if ($utilisateur['role'] === 'admin') {
                    header('Location: index.php');
                } else {
                    $_SESSION['error'] = "Vous n'avez pas les droits d'accès à l'administration.";
                    header('Location: ../index.php');
                }
                exit();
            } else {
                $_SESSION['error'] = "Votre compte n'est pas actif. Contactez l'administrateur.";
            }
        } else {
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer plus tard.";
    }

    header('Location: login.php');
    exit();
} 