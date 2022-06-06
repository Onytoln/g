<?php
require_once 'inc/user.php';

$pageTitle = 'Editace lokace (Admin)';
include 'inc/header.php';

if (!UserAdmin()) header('Location: index.php');

$errors = [];

if (empty($_SESSION['location_edit_id'])) header('Location: timeTable_edit.php');

$query = $db->prepare('SELECT * FROM location WHERE id=:id LIMIT 1;');
$query->execute([
    ':id' => trim($_SESSION['location_edit_id'])
]);

$location = $query->fetch(PDO::FETCH_ASSOC);

if (!empty($_POST)) {

    $name = trim(@$_POST['name']);

    $errors['name'] = [];
    if (empty($name)) {
        $errors['name'][] = 'Musíte zadat nový název lokace.';
    }

    if (strlen($name) < 3) {
        $errors['name'][] = 'Název lokace musí mít alespoň 3 znaky.';
    }

    ForbiddenCharUsageLight($errors, $name);

    if (empty($errors['name'])) {
        unset($errors['name']);
    }

    if (empty($errors)) {
        try {
            $updateQuery = $db->prepare('UPDATE location SET name=:name WHERE id=:id;');
            $updateQuery->execute([
                ':name' => $_POST['name'],
                ':id' => $_SESSION['location_edit_id']
            ]);
        } catch (Exception $exc) {
            $_SESSION['error_edit_loc'] = 'Lokace \'' . $name . '\' již existuje. Zadejte jiný název lokace. Duplicitní hodnoty nejsou povoleny.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        unset($_SESSION['location_edit_id']);

        $_SESSION['general_success'] = 'Lokace \'' . $location['name'] . '\' byla úspěšně upravena na \'' . $name .'\'.';
        header('Location: timeTable_edit.php');
        exit();
    }
}

?>
<a href="timeTable_edit.php" class="btn btn-primary">Zpět na editaci</a>
<a href="index.php" class="btn btn-secondary">Zpět na úvodní stránku</a>

<h2 class="mt-5">Nové údaje lokace - <?php echo 'ID: ' . @$location['id'] . ' ,Název: ' . @$location['name']; ?></h2>

<form method="post">
    <div class="form-group">
        <label for="name">Nový název lokace:</label>
        <input type="text" name="name" id="name" required
               class="form-control <?php echo(!empty($errors['name'] || !empty($_SESSION['error_edit_loc'])) ? 'is-invalid' : ''); ?>"
               value="<?php
               if (!empty($_POST['name'])) {
                   echo htmlspecialchars(@$_POST['name']);
               } else {
                   if (!empty($location)) {
                       echo htmlspecialchars($location['name']);
                   }
               }
               ?>"/>
        <?php
        if (!empty($errors['name'])) {
            echo '<div class="invalid-feedback"><ul>';

            foreach ($errors['name'] as $error) {
                echo '<li>' . $error . '</li>';
            }

            echo '</ul></div>';
        } else {
            if (!empty($_SESSION['error_edit_loc'])) {
                echo '<div class="text-danger">';
                echo $_SESSION['error_edit_loc'];
                echo '</div>';

                unset($_SESSION['error_edit_loc']);
            }
        }
        ?>
    </div>

    <button type="submit" class="btn btn-primary">Upravit</button>
    <a href="timeTable_edit.php" class="btn btn-secondary">Zrušit</a>
</form>
