<?php

require_once "config.php";


// =========================
// 1. Get data for selects
// =========================

$chefs = $pdo->query(
    "SELECT id_chef, nom FROM Chef"
)->fetchAll(PDO::FETCH_ASSOC);

$cuisines = $pdo->query(
    "SELECT id_cuisine, nom FROM Cuisine"
)->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query(
    "SELECT id_category, nom FROM Category"
)->fetchAll(PDO::FETCH_ASSOC);


// =========================
// 2. Add recipe
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titre = $_POST["titre"];
    $description = $_POST["description"];
    $image = trim($_POST["image"]);

    // Add the images folder when only a filename is entered.
    if ($image !== "" && strpos($image, "images/") !== 0) {
        $image = "images/" . $image;
    }
    $ingredients = $_POST["ingredients"];
    $instructions = $_POST["instructions"];
    $temps_preparation = $_POST["temps_preparation"];
    $difficulte = $_POST["difficulte"];
    $id_chef = $_POST["id_chef"];
    $id_cuisine = $_POST["id_cuisine"];
    $id_category = $_POST["id_category"];


    $sql = "
        INSERT INTO Recipe (
            titre,
            description,
            image,
            ingredients,
            instructions,
            temps_preparation,
            difficulte,
            id_chef,
            id_cuisine,
            id_category
        )
        VALUES (
            :titre,
            :description,
            :image,
            :ingredients,
            :instructions,
            :temps_preparation,
            :difficulte,
            :id_chef,
            :id_cuisine,
            :id_category
        )
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":titre" => $titre,
        ":description" => $description,
        ":image" => $image,
        ":ingredients" => $ingredients,
        ":instructions" => $instructions,
        ":temps_preparation" => $temps_preparation,
        ":difficulte" => $difficulte,
        ":id_chef" => $id_chef,
        ":id_cuisine" => $id_cuisine,
        ":id_category" => $id_category
    ]);


    // Redirect to home page
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link rel="stylesheet" href="styles.css">
    <title>Cooking App | Ajouter une recette</title>

</head>


<body>

    <header class="site-header">
        <nav class="navbar">
            <a href="index.php" class="logo">Cooking App</a>
            <a href="index.php" class="button-secondary">Retour aux recettes</a>
        </nav>
    </header>

    <main class="form-container">
        <div>
            <h1 class="form-title">Ajouter une recette</h1>
            <p class="form-intro">Remplissez le formulaire pour partager votre recette.</p>
        </div>

    <form method="POST" class="recipe-form">


        <!-- Title -->

        <div class="form-group">
            <label for="titre">Titre de la recette</label>

            <input
                type="text"
                id="titre"
                name="titre"
                required
            >
        </div>


        <!-- Description -->

        <div class="form-group">
            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                rows="4"
            ></textarea>
        </div>


        <!-- Image -->

        <div class="form-group">
            <label for="image">Image</label>

            <input
                type="text"
                id="image"
                name="image"
                placeholder="Example: images/pizza.svg or pizza.svg"
            >
        </div>


        <!-- Ingredients -->

        <div class="form-group">
            <label for="ingredients">Ingrédients</label>

            <textarea
                id="ingredients"
                name="ingredients"
                rows="5"
                required
            ></textarea>
        </div>


        <!-- Instructions -->

        <div class="form-group">
            <label for="instructions">Instructions</label>

            <textarea
                id="instructions"
                name="instructions"
                rows="6"
                required
            ></textarea>
        </div>


        <!-- Preparation time -->

        <div class="form-group">
            <label for="temps_preparation">
                Temps de préparation (minutes)
            </label>

            <input
                type="number"
                id="temps_preparation"
                name="temps_preparation"
                min="1"
                required
            >
        </div>


        <!-- Difficulty -->

        <div class="form-group">
            <label for="difficulte">Difficulté</label>

            <select
                id="difficulte"
                name="difficulte"
                required
            >

                <option value="">
                    -- Choisir --
                </option>

                <option value="Facile">
                    Facile
                </option>

                <option value="Moyenne">
                    Moyenne
                </option>

                <option value="Difficile">
                    Difficile
                </option>

            </select>

        </div>


        <!-- Chef -->

        <div class="form-group">
            <label for="id_chef">Chef</label>

            <select
                id="id_chef"
                name="id_chef"
                required
            >

                <option value="">
                    -- Choisir un chef --
                </option>

                <?php foreach ($chefs as $chef): ?>

                    <option value="<?= $chef["id_chef"] ?>">
                        <?= htmlspecialchars($chef["nom"]) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- Cuisine -->

        <div class="form-group">
            <label for="id_cuisine">Cuisine</label>

            <select
                id="id_cuisine"
                name="id_cuisine"
                required
            >

                <option value="">
                    -- Choisir une cuisine --
                </option>

                <?php foreach ($cuisines as $cuisine): ?>

                    <option value="<?= $cuisine["id_cuisine"] ?>">
                        <?= htmlspecialchars($cuisine["nom"]) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- Category -->

        <div>
            <label for="id_category">Catégorie</label>

            <select
                id="id_category"
                name="id_category"
                required
            >

                <option value="">
                    -- Choisir une catégorie --
                </option>

                <?php foreach ($categories as $category): ?>

                    <option value="<?= $category["id_category"] ?>">
                        <?= htmlspecialchars($category["nom"]) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- Submit -->

        <div class="form-actions">
        <a href="index.php" class="button button-secondary">Annuler</a>
        <button type="submit" class="button button-primary">
            Ajouter la recette
        </button>
        </div>

    </form>

    </main>

</body>

</html>