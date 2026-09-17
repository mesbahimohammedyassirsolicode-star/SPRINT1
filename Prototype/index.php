<?php   
require_once "config.php";

$recipes = $pdo->query(
    "SELECT r.id_recipe, r.titre, r.description, r.temps_preparation, r.difficulte, c.nom AS chef, cu.nom AS cuisine, ca.nom AS category
     FROM recipe r
     JOIN chef c ON r.id_chef = c.id_chef
     JOIN cuisine cu ON r.id_cuisine = cu.id_cuisine
     JOIN category ca ON r.id_category = ca.id_category"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <title>add</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="index.php" class="logo">Cooking App</a>
            <a href="add_recipe.php" class="nav-link">Ajouter une recette</a>
        </nav>
    </header>
    <main>
    

        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Description</th>
                    <th>Chef</th>
                    <th>Cuisine</th>
                    <th>Category</th>
                    <th>Temps (min)</th>
                    <th>Difficulte</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipes as $recipe): ?>
                <tr>
                    <td><?php echo htmlspecialchars($recipe['titre']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['description']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['chef']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['cuisine']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['category']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['temps_preparation']); ?></td>
                    <td><?php echo htmlspecialchars($recipe['difficulte']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    </main>
</body>
</html>