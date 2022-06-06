<?php

require_once 'inc/user.php';

$pageTitle = 'Editace vlaku (Admin)';
include 'inc/header.php';

if (!UserAdmin()) header('Location: index.php');

$errors = [];

if (empty($_SESSION['train_edit_id'])) header('Location: timeTable_edit.php');

$query = $db->prepare('SELECT * FROM trains WHERE id=:id LIMIT 1;');
$query->execute([
    ':id' => trim($_SESSION['train_edit_id'])
]);

$train = $query->fetch(PDO::FETCH_ASSOC);

if (!empty($_POST)) {

    $name = trim(@$_POST['name']);

    $errors['name'] = [];
    if (empty($name)) {
        $errors['name'][] = 'Musíte zadat nový název vlaku.';
    }

    ForbiddenCharUsageLight($errors, $name);

    if (empty($errors['name'])) {
        unset($errors['name']);
    }

    $seat_count = $_POST['seat_count'];
    if ($seat_count <= 0) {
        $errors['seat_count'] = 'Počet sedadel vlaku musí být větší než 0';
    }

    if (empty($errors)) {
        try {
            $updateQuery = $db->prepare('UPDATE trains SET name=:name, seat_count=:seat_count WHERE id=:id;');
            $updateQuery->execute([
                ':name' => $_POST['name'],
                ':seat_count' => $_POST['seat_count'],
                ':id' => $_SESSION['train_edit_id']
            ]);
        } catch (Exception $exc) {
            $_SESSION['error_edit_train'] = 'Vlak \'' . $name . '\' již existuje. Zadejte jiný název vlaku. Duplicitní hodnoty nejsou povoleny.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        unset($_SESSION['train_edit_id']);

        $_SESSION['general_success'] = 'Vlak \'' . $train['name'] . '\' byl úspěšně upraven na \'' . $name .'\'.';
        header('Location: timeTable_edit.php');
        exit();
    }
}

?>
<a href="timeTable_edit.php" class="btn btn-primary">Zpět na editaci</a>
<a href="index.php" class="btn btn-secondary">Zpět na úvodní stránku</a>

<h2 class="mt-5">Nové údaje vlaku - <?php echo 'ID: ' . @$train['id'] . ' ,Název: ' . @$train['name']; ?></h2>

<form method="post">
    <div class="form-group">
        <label for="name">Nový název vlaku:</label>
        <input type="text" name="name" id="name" required
               class="form-control <?php echo(!empty($errors['name'] || !empty($_SESSION['error_edit_train'])) ? 'is-invalid' : ''); ?>"
               value="<?php
               if (!empty($_POST['name'])) {
                   echo htmlspecialchars(@$_POST['name']);
               } else {
                   if (!empty($train)) {
                       echo htmlspecialchars($train['name']);
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
            if (!empty($_SESSION['error_edit_train'])) {
                echo '<div class="text-danger">';
                echo $_SESSION['error_edit_train'];
                echo '</div>';

                unset($_SESSION['error_edit_train']);
            }
        }
        ?>
    </div>
    <div class="form-group">
        <label for="seat_count">Nový počet sedadel:</label>
        <input type="number" name="seat_count" id="seat_count" required
               class="form-control <?php echo(!empty($errors['seat_count']) ? 'is-invalid' : ''); ?>"
               value="<?php
               if (isset($_POST['seat_count'])) {
                   echo $_POST['seat_count'];
               } else {
                   if (!empty($train)) {
                       echo $train['seat_count'];
                   }
               }
               ?>"/>
        <?php
        echo(!empty($errors['seat_count']) ? '<div class="invalid-feedback">' . $errors['seat_count'] . '</div>' : '');
        ?>
    </div>

    <button type="submit" class="btn btn-primary">Upravit</button>
    <a href="timeTable_edit.php" class="btn btn-secondary">Zrušit</a>
</form>


