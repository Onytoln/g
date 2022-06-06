<?php

require_once 'inc/user.php';

$pageTitle = 'Nový vlak';
include 'inc/header.php';

if (!UserAdmin()) header('Location: index.php');

$errors = [];

if (!empty($_POST)) {

    $name = trim(@$_POST['name']);

    $errors['name'] = [];
    if (empty($name)) {
        $errors['name'][] = 'Musíte zadat název vlaku.';
    }

    ForbiddenCharUsageLight($errors, $name);

    if (empty($errors['name'])) {
        unset($errors['name']);
    }

    $seat_count = $_POST['seat_count'];
    if (empty($seat_count) || $seat_count <= 0) {
        $errors['seat_count'] = 'Počet sedadel vlaku musí být větší než 0';
    }

    if (empty($errors)) {
        try {
            $query = $db->prepare('INSERT INTO trains (name, seat_count) VALUES (:name, :seat_count);');
            $query->execute([
                ':name' => $name,
                ':seat_count' => $seat_count,
            ]);
        } catch (Exception $exc) {
            $_SESSION['error_add_train'] = 'Vlak \'' . $name . '\' již existuje. Zadejte nový název vlaku. Duplicitní hodnoty nejsou povoleny.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }

        $_SESSION['general_success'] = 'Vlak \'' . $name . '\' byl úspěšně přidán.';
        header('Location: timeTable_edit.php');
        exit();
    }
}
?>

    <a href="timeTable_edit.php" class="btn btn-primary">Zpět na editaci</a>
    <a href="index.php" class="btn btn-secondary">Zpět na úvodní stránku</a>

    <h2 class="mt-5">Údaje</h2>
    <form method="post">
        <div class="form-group">
            <label for="name">Název vlaku:</label>
            <input type="text" name="name" id="name" required
                   class="form-control <?php echo(!empty($errors['name'] || !empty($_SESSION['error_add_train'])) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['name']) ?>"/>
            <?php
            if (!empty($errors['name'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['name'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            } else {
                if (!empty($_SESSION['error_add_train'])) {
                    echo '<div class="text-danger">';
                    echo $_SESSION['error_add_train'];
                    echo '</div>';

                    unset($_SESSION['error_add_train']);
                }
            }
            ?>
        </div>
        <div class="form-group">
            <label for="seat_count">Počet sedadel:</label>
            <input type="number" name="seat_count" id="seat_count" required
                   class="form-control <?php echo(!empty($errors['seat_count']) ? 'is-invalid' : ''); ?>"
                   value="<?php
                   if (!empty($_POST['seat_count'])) {
                       echo @$_POST['seat_count'];
                   } else {
                       echo 0;
                   }
                   ?>"/>
            <?php
            echo(!empty($errors['seat_count']) ? '<div class="invalid-feedback">' . $errors['seat_count'] . '</div>' : '');
            ?>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Zaevidovat</button>
            <a href="timeTable_edit.php" class="btn btn-secondary">Zrušit</a>
        </div>
    </form>
<?php
include 'inc/footer.php';


















