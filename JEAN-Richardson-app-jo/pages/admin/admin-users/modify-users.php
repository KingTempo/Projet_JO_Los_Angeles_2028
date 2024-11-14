<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'utilisateur est fourni dans l'URL
if (!isset($_GET['id_utilisateur'])) {
    $_SESSION['error'] = "ID de l'utilisateur manquant.";
    header("Location: manage-users.php");
    exit();
}

$id_utilisateur = filter_input(INPUT_GET, 'id_utilisateur', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'utilisateur est un entier valide
if (!$id_utilisateur) {
    $_SESSION['error'] = "ID de l'utilisateur invalide.";
    header("Location: manage-users.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'utilisateur pour affichage dans le formulaire
try {
    $queryUtilisateur = "SELECT id_utilisateur, nom_utilisateur, prenom_utilisateur, login 
                         FROM UTILISATEUR
                         WHERE id_utilisateur = :idUtilisateur";
    $statementUtilisateur = $connexion->prepare($queryUtilisateur);
    $statementUtilisateur->bindParam(":idUtilisateur", $id_utilisateur, PDO::PARAM_INT);
    $statementUtilisateur->execute();

    if ($statementUtilisateur->rowCount() > 0) {
        $utilisateur = $statementUtilisateur->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header("Location: manage-users.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-users.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomUtilisateur = filter_input(INPUT_POST, 'nomUtilisateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomUtilisateur = filter_input(INPUT_POST, 'prenomUtilisateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $loginUtilisateur = filter_input(INPUT_POST, 'loginUtilisateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $passwordUtilisateur = filter_input(INPUT_POST, 'passwordUtilisateur', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si tous les champs sont remplis
    if (empty($nomUtilisateur) || empty($prenomUtilisateur) || empty($loginUtilisateur) || empty($passwordUtilisateur)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: modify-users.php?id_utilisateur=$id_utilisateur");
        exit();
    }

    // Hashage du mot de passe
    $hashedPassword = password_hash($passwordUtilisateur, PASSWORD_BCRYPT);

    try {
        // Requête pour mettre à jour les informations de l'utilisateur
        $query = "UPDATE UTILISATEUR SET nom_utilisateur = :nomUtilisateur, prenom_utilisateur = :prenomUtilisateur, 
                  login = :loginUtilisateur, password = :hashedPassword WHERE id_utilisateur = :idUtilisateur";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomUtilisateur", $nomUtilisateur, PDO::PARAM_STR);
        $statement->bindParam(":prenomUtilisateur", $prenomUtilisateur, PDO::PARAM_STR);
        $statement->bindParam(":loginUtilisateur", $loginUtilisateur, PDO::PARAM_STR);
        $statement->bindParam(":hashedPassword", $hashedPassword, PDO::PARAM_STR);
        $statement->bindParam(":idUtilisateur", $id_utilisateur, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'utilisateur a été modifié avec succès.";
            header("Location: manage-users.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur.";
            header("Location: modify-users.php?id_utilisateur=$id_utilisateur");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: modify-users.php?id_utilisateur=$id_utilisateur");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Modifier un Utilisateur - Jeux Olympiques - Los Angeles 2028</title>
</head>
<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Utilisateur</h1>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-users.php?id_utilisateur=<?php echo $id_utilisateur; ?>" method="post"
              onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet utilisateur ?')">
            <label for="nomUtilisateur">Nom de l'utilisateur :</label>
            <input type="text" name="nomUtilisateur" id="nomUtilisateur"
                   value="<?php echo htmlspecialchars($utilisateur['nom_utilisateur'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="prenomUtilisateur">Prénom de l'utilisateur :</label>
            <input type="text" name="prenomUtilisateur" id="prenomUtilisateur"
                   value="<?php echo htmlspecialchars($utilisateur['prenom_utilisateur'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="loginUtilisateur">Identifiant :</label>
            <input type="text" name="loginUtilisateur" id="loginUtilisateur"
                   value="<?php echo htmlspecialchars($utilisateur['login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="passwordUtilisateur">Mot de passe :</label>
            <input type="password" name="passwordUtilisateur" id="passwordUtilisateur" required>

            <input type="submit" value="Modifier l'utilisateur">
        </form>
    </main>
</body>
</html>
