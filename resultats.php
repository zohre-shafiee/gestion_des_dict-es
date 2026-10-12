<?php
include("config.php");

/* ---------------------------
   Vérifier si la colonne analyse existe
--------------------------- */
$checkAnalyse = $conn->query("SHOW COLUMNS FROM g1_dictes_resultats LIKE 'analyse'");
$hasAnalyse = ($checkAnalyse && $checkAnalyse->num_rows > 0);

/* ---------------------------
   Récupérer les filtres
--------------------------- */
$classe = $_GET['classe'] ?? '';
$eleve = $_GET['eleve'] ?? '';
$typeDictee = $_GET['type_dictee'] ?? '';
$typeErreur = $_GET['type_erreur'] ?? '';
$date = $_GET['date'] ?? '';

/* ---------------------------
   SQL principal
--------------------------- */
$sql = "
SELECT
    r.id_resultat,
    r.id_eleve,
    r.id_dictee,
    d.niveau_scolaire,
    d.type_dictee,
    d.contenu_reference AS dictee_modele,
    r.dictee_eleve,
";

if ($hasAnalyse) {
    $sql .= "    IFNULL(r.analyse, '') AS analyse,\n";
} else {
    $sql .= "    '' AS analyse,\n";
}

$sql .= "
    r.date_saisie,
    (
        r.faute_orthographique +
        r.mot_ou_ponctuation_manquant +
        r.accord_adjectif +
        r.nom_pluriel +
        r.accord_sujet_verbe
    ) AS nb_erreurs,
    0 AS score,
    r.faute_orthographique,
    r.mot_ou_ponctuation_manquant,
    r.accord_adjectif,
    r.nom_pluriel,
    r.accord_sujet_verbe
FROM g1_dictes_resultats r
JOIN g1_dictee d
    ON r.id_dictee = d.id_dictee
WHERE 1=1
";

/* ---------------------------
   Ajouter filtres
--------------------------- */
if ($classe !== '') {
    $classeSafe = $conn->real_escape_string($classe);
    $sql .= " AND d.niveau_scolaire = '$classeSafe'";
}

if ($eleve !== '') {
    $eleveSafe = (int)$eleve;
    $sql .= " AND r.id_eleve = $eleveSafe";
}

if ($typeDictee !== '') {
    $typeDicteeSafe = $conn->real_escape_string($typeDictee);
    $sql .= " AND d.type_dictee = '$typeDicteeSafe'";
}

if ($date !== '') {
    $dateSafe = $conn->real_escape_string($date);
    $sql .= " AND r.date_saisie = '$dateSafe'";
}

if ($typeErreur !== '') {
    if ($typeErreur === 'orthographe') {
        $sql .= " AND r.faute_orthographique > 0";
    } elseif ($typeErreur === 'mot') {
        $sql .= " AND r.mot_ou_ponctuation_manquant > 0";
    } elseif ($typeErreur === 'accord_adjectif') {
        $sql .= " AND r.accord_adjectif > 0";
    } elseif ($typeErreur === 'pluriel') {
        $sql .= " AND r.nom_pluriel > 0";
    } elseif ($typeErreur === 'accord_sujet_verbe') {
        $sql .= " AND r.accord_sujet_verbe > 0";
    }
}

$sql .= " ORDER BY r.date_saisie DESC, r.id_resultat DESC";

$result = $conn->query($sql);

if (!$result) {
    die("SQL error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Résultats — Gestion des Dictées</title>

  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="resultats.css">
</head>

<body>

<header>
  <span class="brand">Gestion des Dictées</span>
  <span class="bonjour">Bonjour, Prof. Dupont</span>
  <button class="btn-deconnexion" onclick="window.location.href='deconnexion.php'">
    ⇥ Déconnexion
  </button>
</header>

<main>

  <div class="top">
    <h1>Résultats</h1>
    <button class="btn-export" onclick="exportCSV()">⬇ Exporter CSV</button>
  </div>

  <form method="GET">
    <div class="filters">

      <div class="filter-group">
        <label>Classe</label>
        <select name="classe">
          <option value="">Toutes les classes</option>
          <option value="CE1" <?= $classe === 'CE1' ? 'selected' : '' ?>>CE1</option>
          <option value="CM1" <?= $classe === 'CM1' ? 'selected' : '' ?>>CM1</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Élève</label>
        <select name="eleve">
          <option value="">Tous les élèves</option>
          <option value="1" <?= $eleve === '1' ? 'selected' : '' ?>>1</option>
          <option value="2" <?= $eleve === '2' ? 'selected' : '' ?>>2</option>
          <option value="3" <?= $eleve === '3' ? 'selected' : '' ?>>3</option>
          <option value="4" <?= $eleve === '4' ? 'selected' : '' ?>>4</option>
          <option value="5" <?= $eleve === '5' ? 'selected' : '' ?>>5</option>
          <option value="6" <?= $eleve === '6' ? 'selected' : '' ?>>6</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Type de dictée</label>
        <select name="type_dictee">
          <option value="">Tous les types</option>
          <option value="texte" <?= $typeDictee === 'texte' ? 'selected' : '' ?>>Texte</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Type d'erreur</label>
        <select name="type_erreur">
          <option value="">Toutes les fautes</option>
          <option value="orthographe" <?= $typeErreur === 'orthographe' ? 'selected' : '' ?>>Orthographe</option>
          <option value="mot" <?= $typeErreur === 'mot' ? 'selected' : '' ?>>Mot manquant</option>
          <option value="accord_adjectif" <?= $typeErreur === 'accord_adjectif' ? 'selected' : '' ?>>Accord adjectif</option>
          <option value="pluriel" <?= $typeErreur === 'pluriel' ? 'selected' : '' ?>>Pluriel</option>
          <option value="accord_sujet_verbe" <?= $typeErreur === 'accord_sujet_verbe' ? 'selected' : '' ?>>Accord sujet-verbe</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
      </div>

      <div class="filter-actions">
        <button type="submit" class="btn-search">Rechercher</button>
        <a href="resultats.php">
          <button type="button" class="btn-reset">Réinitialiser</button>
        </a>
      </div>

    </div>
  </form>

  <div class="table-container">
    <table id="resultsTable">
      <thead>
        <tr>
          <th>Classe</th>
          <th>Élève</th>
          <th>Type dictée</th>
          <th>Dictée modèle</th>
          <th>Dictée élève</th>
          <th>Analyse</th>
          <th>Date</th>
          <th>Score</th>
          <th>Nb erreurs</th>
          <th>Faute orthographique</th>
          <th>Mot ou ponctuation manquant</th>
          <th>Accord adjectif</th>
          <th>Nom pluriel</th>
          <th>Accord sujet-verbe</th>
        </tr>
      </thead>

      <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
          <tr>
            <td><?= htmlspecialchars($row["niveau_scolaire"]) ?></td>
            <td><?= htmlspecialchars($row["id_eleve"]) ?></td>
            <td><?= htmlspecialchars($row["type_dictee"]) ?></td>
            <td class="dictee"><?= htmlspecialchars($row["dictee_modele"]) ?></td>
            <td class="dictee"><?= htmlspecialchars($row["dictee_eleve"]) ?></td>
            <td class="analyse"><?= htmlspecialchars($row["analyse"]) ?></td>
            <td><?= htmlspecialchars($row["date_saisie"]) ?></td>
            <td><?= htmlspecialchars($row["score"]) ?></td>
            <td class="<?= $row["nb_erreurs"] > 0 ? 'error' : '' ?>">
              <?= htmlspecialchars($row["nb_erreurs"]) ?>
            </td>
            <td class="<?= $row["faute_orthographique"] > 0 ? 'error' : '' ?>">
              <?= htmlspecialchars($row["faute_orthographique"]) ?>
            </td>
            <td class="<?= $row["mot_ou_ponctuation_manquant"] > 0 ? 'error' : '' ?>">
              <?= htmlspecialchars($row["mot_ou_ponctuation_manquant"]) ?>
            </td>
            <td class="<?= $row["accord_adjectif"] > 0 ? 'error' : '' ?>">
              <?= htmlspecialchars($row["accord_adjectif"]) ?>
            </td>
            <td class="<?= $row["nom_pluriel"] > 0 ? 'error' : '' ?>">
              <?= htmlspecialchars($row["nom_pluriel"]) ?>
            </td>
            <td class="<?= $row["accord_sujet_verbe"] > 0 ? 'error' : '' ?>">
              <?= htmlspecialchars($row["accord_sujet_verbe"]) ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

</main>

<footer>
  Gestion des Dictées — Université Grenoble Alpes<br>
  Projet réalisé par : Ana Laura Donega-Cantuaria, Catherine Kovach, Darya Zdrelyuk, Ekaterina Leonova, Zohre Shafiee<br>
  M1 Industrie de la Langue — 2025-2026
</footer>

<script src="resultats.js"></script>

</body>
</html>