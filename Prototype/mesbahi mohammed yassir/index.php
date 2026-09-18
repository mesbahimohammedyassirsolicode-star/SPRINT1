<?php
require_once 'config.php';


$recipes=$connx->query(
"SELECT * FROM recipe;
");
$recipes->fetch(PDO::FETCH_ASSOC);











?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <h1>
            cooking app
        </h1>
        <a href="add.php">add recipe</a>
    </nav>
    <main>
        <table>
            
            <tr>
                <th>titre</th>
                <th>description</th>
                <th>ingredients</th>
                <th>instructions</th>
                <th>temps preparation</th>
                <th>difficulte</th>
            </tr>
            <?php 
            foreach($recipes as $recipe):
            ?>
            <tr>
            
                <td>
                <?php echo $recipe['titre']?>
                </td>
                <td>
                <?php echo $recipe['description']?>
                </td>
                <td>
                <?php echo $recipe['ingredients']?>
                </td>
                <td>
                <?php echo $recipe['instructions']?>
                </td>
                <td>
                <?php echo $recipe['temps_preparation']?>
                </td>
                <td>
                <?php echo $recipe['difficulte']?>
                </td>    
            </tr>
            <?php endforeach?>
        </table>
    </main>
</body>
</html>