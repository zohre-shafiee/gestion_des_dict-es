<?php
session_start();
require "config.php"; //

// Security: Check if logged in
if (!isset($_SESSION["id"])) {
    header("Location: index.php");
    exit();
}

$id_professeur = $_SESSION["id"];
$identifiant   = $_SESSION["identifiant"];

// Get the class ID from URL
if (!isset($_GET["id"])) {
    header("Location: classes.php");
    exit();
}
$id_classe = (int)$_GET["id"];

// 1. Fetch Class Info to display name
$sql_classe = "SELECT nom_classe FROM g1_classe WHERE id_classe = ? AND id_professeur = ?";
$stmt_c = $conn->prepare($sql_classe);
$stmt_c->bind_param("ii", $id_classe, $id_professeur);
$stmt_c->execute();
$res_c = $stmt_c->get_result();
$classe = $res_c->fetch_assoc();

if (!$classe) {
    header("Location: classes.php");
    exit();
}

// 2. Fetch Students in this class
$sql_eleves = "SELECT id_eleve FROM g1_eleve WHERE id_classe = ? ORDER BY id_eleve";
$stmt_e = $conn->prepare($sql_eleves);
$stmt_e->bind_param("i", $id_classe);
$stmt_e->execute();
$eleves = $stmt_e->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Fetch Dictations created by this professor
$sql_dictees = "SELECT id_dictee, niveau_scolaire, date_passation FROM g1_dictee WHERE id_professeur = ? ORDER BY date_passation DESC";
$stmt_d = $conn->prepare($sql_dictees);
$stmt_d->bind_param("i", $id_professeur);
$stmt_d->execute();
$dictees = $stmt_d->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_eleve             = (int)$_POST["id_eleve"];
    $id_dictee            = (int)$_POST["id_dictee"];
    $dictee_eleve         = trim($_POST["dictee_eleve"]);
    $date_saisie          = date("Y-m-d");
    $faute_ortho          = (int)$_POST["faute_orthographique"];
    $manquant             = (int)$_POST["mot_ou_ponctuation_manquant"];
    $accord_adj           = (int)$_POST["accord_adjectif"];
    $nom_pluriel          = (int)$_POST["nom_pluriel"];
    $accord_sv            = (int)$_POST["accord_sujet_verbe"];

    $sql_ins = "INSERT INTO g1_dictes_resultats 
                (id_eleve, id_dictee, dictee_eleve, date_saisie, faute_orthographique, mot_ou_ponctuation_manquant, accord_adjectif, nom_pluriel, accord_sujet_verbe) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_i = $conn->prepare($sql_ins);
    $stmt_i->bind_param("iissiiiii", $id_eleve, $id_dictee, $dictee_eleve, $date_saisie, $faute_ortho, $manquant, $accord_adj, $nom_pluriel, $accord_sv);

    if ($stmt_i->execute()) {
        $message = "succes";
    } else {
        $message = "erreur";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisir Réponses — Gestion des Dictées</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="saisir_reponses.css">
</head>
<body>

<header>
    <span class="brand">Gestion des Dictées</span>
    <span class="bonjour">Bonjour, <?= htmlspecialchars($identifiant) ?></span>
    <button class="btn-deconnexion" onclick="window.location.href='deconnexion.php'">⇥ Déconnexion</button>
</header>

<main>
    <a class="retour" href="eleves.php?id=<?= $id_classe ?>">← Retour à la classe</a>

    <div class="container">
        <h1>Saisie des résultats : <?= htmlspecialchars($classe["nom_classe"]) ?></h1>

        <?php if ($message == "succes"): ?>
            <p class="msg succes">Le résultat a été enregistré avec succès !</p>
        <?php elseif ($message == "erreur"): ?>
            <p class="msg erreur">Une erreur est survenue lors de l'enregistrement.</p>
        <?php endif; ?>

        <form action="saisir_reponses.php?id=<?= $id_classe ?>" method="POST">
            <div class="grid-top">
                <div class="form-group">
                    <label>Sélectionner l'élève</label>
                    <select name="id_eleve" required>
                        <option value="">-- Choisir un élève --</option>
                        <?php foreach ($eleves as $e): ?>
                            <option value="<?= $e["id_eleve"] ?>">Élève #<?= $e["id_eleve"] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Sélectionner la dictée</label>
                    <select name="id_dictee" required>
                        <option value="">-- Choisir une dictée --</option>
                        <?php foreach ($dictees as $d): ?>
                            <option value="<?= $d["id_dictee"] ?>">
                                <?= htmlspecialchars($d["niveau_scolaire"]) ?> (<?= $d["date_passation"] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Texte produit par l'élève</label>
                <textarea name="dictee_eleve" rows="8" placeholder="Copiez ici la dictée de l'élève..." required></textarea>
            </div>

            <h3>Décompte des erreurs</h3>
            <div class="errors-grid">
                <div class="form-group">
                    <label>Orthographe lexicale</label>
                    <input type="number" name="faute_orthographique" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Mots/Ponctuation manquants</label>
                    <input type="number" name="mot_ou_ponctuation_manquant" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Accord adjectif</label>
                    <input type="number" name="accord_adjectif" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Nom pluriel</label>
                    <input type="number" name="nom_pluriel" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Accord sujet-verbe</label>
                    <input type="number" name="accord_sujet_verbe" value="0" min="0">
                </div>
            </div>

            <button type="submit" class="btn-save">Enregistrer les résultats</button>
        </form>
    </div>
</main>

<footer>
    Gestion des Dictées — Université Grenoble Alpes<br>
    M1 Industrie de la Langue — 2025-2026
</footer>

</body>
</html>