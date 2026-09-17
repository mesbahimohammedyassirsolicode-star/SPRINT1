<?php

require_once "config.php";

$sql = "
    SELECT 
        r.id_recipe,
        r.titre,
        r.description,
        r.image,
        r.temps_preparation,
        r.difficulte,
        c.nom AS chef,
        cu.nom AS cuisine,
        ca.nom AS category
    FROM Recipe r
    INNER JOIN Chef c ON r.id_chef = c.id_chef
    INNER JOIN Cuisine cu ON r.id_cuisine = cu.id_cuisine
    INNER JOIN Category ca ON r.id_category = ca.id_category
    ORDER BY r.date_creation DESC
";

$stmt = $pdo->query($sql);

$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Cooking App</title>
</head>

<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="index.php" class="logo">Cooking App</a>
            <a href="add_recipe.php" class="nav-link">Ajouter une recette</a>
        </nav>
    </header>

    <main class="container">
        <h1 class="page-title">Toutes les recettes</h1>
        <p class="page-intro">Trouvez une idée simple pour votre prochain repas.</p>

        <?php if (count($recipes) > 0): ?>
            <div class="recipe-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <article class="recipe-card">
                        <img
                            class="recipe-image"
                            src="<?php echo htmlspecialchars($recipe['image']); ?>"
                            alt="<?php echo htmlspecialchars($recipe['titre']); ?>"
                        >
                        <div class="recipe-content">
                            <h2 class="recipe-title"><?php echo htmlspecialchars($recipe['titre']); ?></h2>
                            <p class="recipe-description"><?php echo htmlspecialchars($recipe['description']); ?></p>
                            <div class="recipe-info">
                                <p><strong>Chef :</strong> <?php echo htmlspecialchars($recipe['chef']); ?></p>
                                <p><strong>Cuisine :</strong> <?php echo htmlspecialchars($recipe['cuisine']); ?></p>
                                <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($recipe['category']); ?></p>
                                <p><strong>Préparation :</strong> <?php echo $recipe['temps_preparation']; ?> minutes</p>
                                <p><strong>Difficulté :</strong> <?php echo htmlspecialchars($recipe['difficulte']); ?></p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>Aucune recette disponible</h2>
                <p>Ajoutez votre première recette pour commencer.</p>
                <a href="add_recipe.php" class="button button-primary">Ajouter une recette</a>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>
