<?php

require_once "config.php";

$id_recipe = $_GET["id"] ?? null;

if ($id_recipe === null) {
    header("Location: index.php");
    exit;
}

$sql = "
    SELECT
        r.id_recipe,
        r.titre,
        r.description,
        r.image,
        r.ingredients,
        r.instructions,
        r.temps_preparation,
        r.difficulte,
        c.nom AS chef,
        cu.nom AS cuisine,
        ca.nom AS category
    FROM Recipe r
    INNER JOIN Chef c ON r.id_chef = c.id_chef
    INNER JOIN Cuisine cu ON r.id_cuisine = cu.id_cuisine
    INNER JOIN Category ca ON r.id_category = ca.id_category
    WHERE r.id_recipe = :id_recipe
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id_recipe" => $id_recipe]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo htmlspecialchars($recipe["titre"]); ?> | Cooking App</title>
</head>

<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="index.php" class="logo">Cooking App</a>
            <a href="index.php" class="button-secondary">Retour aux recettes</a>
        </nav>
    </header>

    <main class="container">
        <article class="recipe-detail">
            <img
                class="recipe-image"
                src="<?php echo htmlspecialchars($recipe['image']); ?>"
                alt="<?php echo htmlspecialchars($recipe['titre']); ?>"
            >

            <h1 class="page-title"><?php echo htmlspecialchars($recipe["titre"]); ?></h1>
            <p><?php echo htmlspecialchars($recipe["description"]); ?></p>

            <div class="recipe-info">
                <p><strong>Chef :</strong> <?php echo htmlspecialchars($recipe["chef"]); ?></p>
                <p><strong>Cuisine :</strong> <?php echo htmlspecialchars($recipe["cuisine"]); ?></p>
                <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($recipe["category"]); ?></p>
                <p><strong>Préparation :</strong> <?php echo htmlspecialchars($recipe["temps_preparation"]); ?> minutes</p>
                <p><strong>Difficulté :</strong> <?php echo htmlspecialchars($recipe["difficulte"]); ?></p>
            </div>

            <h2>Ingrédients</h2>
            <p><?php echo nl2br(htmlspecialchars($recipe["ingredients"])); ?></p>

            <h2>Instructions</h2>
            <p><?php echo nl2br(htmlspecialchars($recipe["instructions"])); ?></p>
        </article>
    </main>
</body>

</html>