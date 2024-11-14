<?php
session_start();
require_once("../../../database/database.php");

// Protection CSRF
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header('Location: ../../../index.php');
        exit();
    }
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Génération du token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Vérifiez si les paramètres nécessaires sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "Les identifiants de l'athlète et de l'épreuve sont manquants.";
    header("Location: manage-results.php");
    exit();
} else {
    // Récupérer les identifiants des athlètes et des épreuves à partir des paramètres GET
    $id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

    // Vérification de la validité des identifiants
    if ($id_athlete === false || $id_epreuve === false) {
        $_SESSION['error'] = "Les identifiants fournis sont invalides.";
        header("Location: manage-results.php");
        exit();
    } else {
        try {
            // 1. Supprimer l'entrée spécifique dans la table RESULTAT
            $sqlDeleteResult = "DELETE FROM PARTICIPER WHERE id_athlete = :id_athlete AND id_epreuve = :id_epreuve";
            $statementDeleteResult = $connexion->prepare($sqlDeleteResult);
            $statementDeleteResult->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
            $statementDeleteResult->bindParam(':id_epreuve', $id_epreuve, PDO::PARAM_INT);
            $statementDeleteResult->execute();

            // Vérification si la suppression a été effectuée avec succès
            if ($statementDeleteResult->rowCount() > 0) {
                $_SESSION['success'] = "Le résultat a été supprimé avec succès.";
            } else {
                $_SESSION['error'] = "Aucun résultat trouvé pour cet athlète et cette épreuve.";
            }

            // Redirigez vers la page de gestion des résultats après la suppression
            header('Location: manage-results.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du résultat : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: manage-results.php');
            exit();
        }
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d’avoir activé l’option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>