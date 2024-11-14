<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

require_once("../../../database/database.php");

// Fonction pour vérifier le token CSRF
function checkCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Token CSRF invalide.');
        }
    }
}

// Générer un token CSRF si ce n'est pas déjà fait
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    <title>Gestion Résultats - Jeux Olympiques - Los Angeles 2028</title>
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
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header> 

    <main>
        <h1>Gestion des Résultats</h1>
        <div class="action-buttons">
            <button onclick="openAddResultForm()">Ajouter un Résultat</button>
        </div>

        <!-- Tableau des résultats -->
        <?php
        try {
            // Requête pour récupérer la liste des résultats
            $query = "SELECT p.id_athlete, p.id_epreuve, a.nom_athlete, a.prenom_athlete, e.nom_epreuve, g.nom_genre, p.resultat, pa.nom_pays
                      FROM PARTICIPER p
                      JOIN ATHLETE a ON p.id_athlete = a.id_athlete
                      JOIN EPREUVE e ON p.id_epreuve = e.id_epreuve
                      JOIN PAYS pa ON a.id_pays = pa.id_pays
                      JOIN GENRE g ON a.id_genre = g.id_genre
                      ORDER BY a.nom_athlete, e.nom_epreuve";

            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table><tr><th>Athlète</th><th>Pays</th><th>Épreuve</th><th>Genre</th><th>Résultat</th><th>Modifier</th><th>Supprimer</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nom_athlete'] . ' ' . $row['prenom_athlete']?? ' ', ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_pays'] ?? ' ', ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_epreuve'] ?? ' ', ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_genre'] ?? ' ', ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['resultat'] ?? ' ', ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td><button onclick='openModifyResultForm(" . $row['id_athlete'] . ", " . $row['id_epreuve'] . ")'>Modifier</button></td>";
                    echo "<td><button onclick='deleteResultConfirmation(" . $row['id_athlete'] . ", " . $row['id_epreuve'] . ")'>Supprimer</button></td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun résultat trouvé.</p>";
            }
        } catch (PDOException $e) {
            echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
        ?>
        
        <p class="paragraph-link">
            <a class="link-home" href="../admin.php">Accueil administration</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

    <script>
        function openAddResultForm() {
            window.location.href = 'add-results.php';
        }

        function openModifyResultForm(id_athlete, id_epreuve) {
            window.location.href = 'modify-results.php?id_athlete=' + id_athlete + '&id_epreuve=' + id_epreuve;
        }

        function deleteResultConfirmation(id_athlete, id_epreuve) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce résultat?")) {
                window.location.href = 'delete-results.php?id_athlete=' + id_athlete + '&id_epreuve=' + id_epreuve;
            }
        }
    </script>
</body>

</html>