<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'athlète et l'ID de l'épreuve sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve manquant.";
    header("Location: manage-results.php");
    exit();
}

$id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si les IDs sont valides
if (!$id_athlete || !$id_epreuve) {
    $_SESSION['error'] = "ID de l'athlète ou de l'épreuve invalide.";
    header("Location: manage-results.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez le résultat actuel pour affichage dans le formulaire
try {
    $queryResult = "SELECT p.resultat, a.nom_athlete, a.prenom_athlete, e.nom_epreuve, g.nom_genre 
                    FROM PARTICIPER p 
                    JOIN ATHLETE a ON p.id_athlete = a.id_athlete 
                    JOIN EPREUVE e ON p.id_epreuve = e.id_epreuve 
                    JOIN GENRE g ON a.id_genre = g.id_genre 
                    WHERE p.id_athlete = :idAthlete AND p.id_epreuve = :idEpreuve";
    $statementResult = $connexion->prepare($queryResult);
    $statementResult->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
    $statementResult->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);
    $statementResult->execute();

    if ($statementResult->rowCount() > 0) {
        $resultData = $statementResult->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Résultat non trouvé.";
        header("Location: manage-results.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-results.php");
    exit();
}

try {
    $queryAthlete = "SELECT id_athlete, nom_athlete FROM ATHLETE ORDER BY nom_athlete";
    $statementAthlete = $connexion->prepare($queryAthlete);
    $statementAthlete->execute();
    $athletes = $statementAthlete->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données lors de la récupération des sports : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-events.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez que le résultat est bien fourni
    if (empty($resultat)) {
        $_SESSION['error'] = "Le champ résultat doit être rempli.";
        header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }

    try {
        // Requête pour mettre à jour le résultat
        $queryUpdate = "UPDATE PARTICIPER SET resultat = :resultat 
                        WHERE id_athlete = :idAthlete AND id_epreuve = :idEpreuve";
        $statementUpdate = $connexion->prepare($queryUpdate);
        $statementUpdate->bindParam(":resultat", $resultat, PDO::PARAM_STR);
        $statementUpdate->bindParam(":idAthlete", $id_athlete, PDO::PARAM_INT);
        $statementUpdate->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statementUpdate->execute()) {
            $_SESSION['success'] = "Le résultat a été modifié avec succès.";
            header("Location: manage-results.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du résultat.";
            header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
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
    <title>Modifier un Résultat - Jeux Olympiques - Los Angeles 2028</title>
</head>
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
        <h1>Modifier un Résultat</h1>

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

        <form action="modify-results.php?id_athlete=<?php echo $id_athlete; ?>&id_epreuve=<?php echo $id_epreuve; ?>" method="post"
              onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce résultat ?')">
            <label for="athlete">Athlète :</label>
            <input type="text" id="athlete" value="<?php echo htmlspecialchars($resultData['nom_athlete'] . ' ' . $resultData['prenom_athlete'], ENT_QUOTES, 'UTF-8'); ?>" disabled>

            <label for="epreuve">Épreuve :</label>
            <input type="text" id="epreuve" value="<?php echo htmlspecialchars($resultData['nom_epreuve'], ENT_QUOTES, 'UTF-8'); ?>" disabled>

            <label for="genre">Genre :</label>
            <input type="text" id="genre" value="<?php echo htmlspecialchars($resultData['nom_genre'], ENT_QUOTES, 'UTF-8'); ?>" disabled>

            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" value="<?php echo htmlspecialchars($resultData['resultat'], ENT_QUOTES, 'UTF-8'); ?>" required>
            
            <input type="submit" value="Modifier le résultat">
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