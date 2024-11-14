<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer la liste des pays, épreuves, genres, et athlètes pour les menus déroulants
try {
    $queryPays = "SELECT id_pays, nom_pays FROM PAYS";
    $statementPays = $connexion->prepare($queryPays);
    $statementPays->execute();
    $pays = $statementPays->fetchAll(PDO::FETCH_ASSOC);

    $queryEpreuve = "SELECT id_epreuve, nom_epreuve FROM EPREUVE";
    $statementEpreuve = $connexion->prepare($queryEpreuve);
    $statementEpreuve->execute();
    $epreuves = $statementEpreuve->fetchAll(PDO::FETCH_ASSOC);

    $queryGenre = "SELECT id_genre, nom_genre FROM GENRE";
    $statementGenre = $connexion->prepare($queryGenre);
    $statementGenre->execute();
    $genres = $statementGenre->fetchAll(PDO::FETCH_ASSOC);

    $queryAthlete = "SELECT id_athlete, prenom_athlete, nom_athlete FROM ATHLETE";
    $statementAthlete = $connexion->prepare($queryAthlete);
    $statementAthlete->execute();
    $athletes = $statementAthlete->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors du chargement des données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: add-results.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idAthlete = filter_input(INPUT_POST, 'id_athlete', FILTER_VALIDATE_INT);
    $idPays = filter_input(INPUT_POST, 'idPays', FILTER_VALIDATE_INT);
    $idEpreuve = filter_input(INPUT_POST, 'idEpreuve', FILTER_VALIDATE_INT);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);
    $idGenre = filter_input(INPUT_POST, 'idGenre', FILTER_VALIDATE_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-results.php");
        exit();
    }

    if (empty($idAthlete)) {
        $_SESSION['error'] = "Veuillez sélectionner un athlète.";
        header("Location: add-results.php");
        exit();
    }

    try {
        // Insérer les résultats de l'athlète pour l'épreuve sélectionnée
        $queryResult = "INSERT INTO PARTICIPER (id_athlete, id_epreuve, resultat) VALUES (:idAthlete, :idEpreuve, :resultat)";
        $statementResult = $connexion->prepare($queryResult);
        $statementResult->bindParam(":idAthlete", $idAthlete, PDO::PARAM_INT);
        $statementResult->bindParam(":idEpreuve", $idEpreuve, PDO::PARAM_INT);
        $statementResult->bindParam(":resultat", $resultat, PDO::PARAM_STR);

        if ($statementResult->execute()) {
            $_SESSION['success'] = "Le résultat a été ajouté avec succès.";
            header("Location: manage-results.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout du résultat.";
            header("Location: add-results.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-results.php");
        exit();
    }
}

error_reporting(E_ALL);
ini_set("display_errors", 1);
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
    <title>Ajouter un Résultat - Jeux Olympiques - Los Angeles 2028</title>
</head>

<style>
form select {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  box-sizing: border-box;
  border: 1px solid #ccc;
  border-radius: 5px;
}
</style>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-places.php">Gestion Lieux</a></li>
                <li><a href="manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Résultat</h1>
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
        <form action="add-results.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <label for="id_athlete">Athlète :</label>
            <select name="id_athlete" id="id_athlete" required>
                <option value="">Sélectionnez un athlète</option>
                <?php foreach ($athletes as $athlete): ?>
                    <option value="<?= htmlspecialchars($athlete['id_athlete'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($athlete['nom_athlete'] . ' ' . $athlete['prenom_athlete'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="idGenre">Genre de l'athlète :</label>
            <select name="idGenre" id="idGenre" required>
                <option value="">Sélectionnez le genre</option>
                <?php foreach ($genres as $genreOption): ?>
                    <option value="<?= htmlspecialchars($genreOption['id_genre'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($genreOption['nom_genre'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="idPays">Pays de l'athlète :</label>
            <select name="idPays" id="idPays" required>
                <option value="">Sélectionnez le pays</option>
                <?php foreach ($pays as $paysOption): ?>
                    <option value="<?= htmlspecialchars($paysOption['id_pays'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($paysOption['nom_pays'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="idEpreuve">Épreuve :</label>
            <select name="idEpreuve" id="idEpreuve" required>
                <option value="">Sélectionnez l'épreuve</option>
                <?php foreach ($epreuves as $epreuveOption): ?>
                    <option value="<?= htmlspecialchars($epreuveOption['id_epreuve'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($epreuveOption['nom_epreuve'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" required>

            <input type="submit" value="Ajouter le résultat">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>
</html>
