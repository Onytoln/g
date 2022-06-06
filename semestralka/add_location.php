<?php
require_once 'inc/user.php';

$pageTitle = 'Nová destinace';
include 'inc/header.php';

if (!UserAdmin()) header('Location: index.php');


$errors = [];

if (!empty($_POST)) {

    $name = trim(@$_POST['name']);

    $errors['name'] = [];

    if (empty($name)) {
        $errors['name'][] = 'Musíte zadat název lokace.';
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
            $query = $db->prepare('INSERT INTO location (name) VALUES (:name);');
            $query->execute([
                ':name' => $name
            ]);
        } catch (Exception $exc) {
            $_SESSION['error_add_loc'] = 'Lokace \'' . $name . '\' již existuje. Zadejte nový název lokace. Duplicitní hodnoty nejsou povoleny.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }

        $_SESSION['general_success'] = 'Lokace \'' . $name . '\' byla úspěšně přidána.';
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
            <label for="name">Název lokace:</label>
            <input type="text" name="name" id="name" required
                   class="form-control <?php echo(!empty($errors['name'] || !empty($_SESSION['error_add_loc'])) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['name']) ?>"/>
            <?php
            if (!empty($errors['name'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['name'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            } else {
                if (!empty($_SESSION['error_add_loc'])) {
                    echo '<div class="text-danger">';
                    echo $_SESSION['error_add_loc'];
                    echo '</div>';

                    unset($_SESSION['error_add_loc']);
                }
            }
            ?>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Přidat</button>
            <a href="timeTable_edit.php" class="btn btn-secondary">Zrušit</a>
        </div>
    </form>
<?php
include 'inc/footer.php';
