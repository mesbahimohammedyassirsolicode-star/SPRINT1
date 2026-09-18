<?php
require_once 'config.php';
$chefs = $connx->query(
    "SELECT id_chef, nom FROM chef;
"
);
$chefss=$chefs->fetchAll(PDO::FETCH_ASSOC);
$category = $connx->query(
    'SELECT id_category, nom
FROM category'
);
$catagories=$category->fetchall(PDO::FETCH_ASSOC);
$cuisine = $connx->query(
    "SELECT nom, id_cuisine
FROM cuisine;
"
);
$cuisins=$cuisine->fetchall(PDO::FETCH_ASSOC);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = $_POST['title'];
    $desc = $_POST['desc'];
    $instructions = $_POST["instructions"];
    $ingredients = $_POST['ingredients'];
    $temps = $_POST['temps'];
    $difficulte = $_POST['difficulte'];
    $chef=$_POST['chef'];
    $cuisin=$_POST['cuisin'];
    $catagory=$_POST['catagory'];
    $sql = '

    INSERT INTO `cooking_app`.`recipe`
(
`titre`,
`description`,
`ingredients`,
`instructions`,
`temps_preparation`,
`difficulte`,
`id_chef`,
`id_cuisine`,
`id_category`)
VALUES
(?,?,?,?,?,?,?,?,?)
';
$stmt=$connx->prepare($sql);

$stmt->execute([$titre,$desc,$ingredients,$instructions,$temps,$difficulte,$chef,$cuisin,$catagory]);
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>add</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <nav>
            <h1>
                cooking app
            </h1>
            <a href="index.php">home</a>
        </nav>
    </header>
    <main>
        <form method="post">
            <label for="tite">titre</label>
            <input type="text" name="title">
            <label for="descrtiption">descr</label>
            <input type="text" name="desc">
            <label for="ingredients">ingredients</label>
            <input type="text" name="ingredients">
            <label for="instructions">instructions</label>
            <input type="text" name="instructions">
            <label for="temps">temps</label>
            <input type="number" name="temps">
            <label for="difficulte">difficulte</label>
            <input type="text" name="difficulte">
                <select name='chef'>
                    <option disabled>---choisir les chefs </option>
                    <?php foreach($chefss as $chef ):?>
                    <option value="<?php echo $chef['id_chef'] ?>"><?php echo htmlspecialchars($chef['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name='cuisin'>
                    <option disabled >---choisir les cuisine </option>
                    <?php foreach($cuisins as $cuisin ):?>
                    <option value="<?php echo $cuisin['id_cuisine'] ?>"><?php echo htmlspecialchars($cuisin['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                     <select name='catagory'>
                    <option disabled >---choisir les catagories </option>
                    <?php foreach($catagories as $catagory ):?>
                    <option value="<?php echo $catagory['id_category'] ?>"><?php echo htmlspecialchars($catagory['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name='ok'>add</button>
        </form>
    </main>
</body>

</html>