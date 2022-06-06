<?php
require_once 'inc/user.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];

if (!empty($_POST)) {
    $name = trim(@$_POST['name']);

    $errors['name'] = [];
    if (empty($name)) {
        $errors['name'][] = 'Musíte zadat své jméno či přezdívku.';
    }

    ForbiddenCharUsageHeavy($errors, $name);

    $email = trim(@$_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Musíte zadat platnou e-mailovou adresu.';
    } else {
        $mailQuery = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
        $mailQuery->execute([
            ':email' => $email
        ]);
        if ($mailQuery->rowCount() > 0) {
            $errors['name'][] = 'Uživatelský účet s touto e-mailovou adresou již existuje.';
        }
    }

    if (empty($errors['name'])) {
        unset($errors['name']);
    }

    $errors['password'] = [];
    if (empty($_POST['password']) || (strlen($_POST['password']) < 6)) {
        $errors['password'][] = 'Musíte zadat heslo o délce alespoň 6 znaků.';
    }

    if (!preg_match('/[A-Z]/', $_POST['password'])) {
        $errors['password'][] = 'Heslo musí obsahovat alespoň jedno velké písmeno.';
    }

    if (!preg_match('~[0-9]+~', $_POST['password'])) {
        $errors['password'][] = 'Heslo musí obsahovat alespoň jednu číslici.';
    }


    if ($_POST['password'] != $_POST['password2']) {
        $errors['password2'] = 'Zadaná hesla se neshodují.';
    }

    if (empty($errors['password'])) {
        unset($errors['password']);
    }

    if (empty($errors)) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $query = $db->prepare('INSERT INTO users (name, email, password, active) VALUES (:name, :email, :password, 1);');
        $query->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password
        ]);

        $_SESSION['user_id'] = $db->lastInsertId();
        $_SESSION['user_name'] = $name;

        header('Location: index.php');
        exit();
    }
}

$pageTitle = 'Registrace';
include 'inc/header.php';
?>

    <h2>Registrace nového uživatele</h2>

    <form method="post">
        <div class="form-group">
            <label for="name">Jméno či přezdívka:</label>
            <input type="text" name="name" id="name" required
                   class="form-control <?php echo(!empty($errors['name']) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$name); ?>"/>
            <?php
            if (!empty($errors['name'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['name'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" required
                   class="form-control <?php echo(!empty($errors['email']) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$email); ?>"/>
            <?php
            echo(!empty($errors['email']) ? '<div class="invalid-feedback">' . $errors['email'] . '</div>' : '');
            ?>
        </div>
        <div class="form-group">
            <label for="password">Heslo:</label>
            <input type="password" name="password" id="password" required
                   class="form-control <?php echo(!empty($errors['password']) ? 'is-invalid' : ''); ?>"/>
            <?php
            if (!empty($errors['password'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['password'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="password2">Potvrzení hesla:</label>
            <input type="password" name="password2" id="password2" required
                   class="form-control <?php echo(!empty($errors['password2']) ? 'is-invalid' : ''); ?>"/>
            <?php
            echo(!empty($errors['password2']) ? '<div class="invalid-feedback">' . $errors['password2'] . '</div>' : '');
            ?>
        </div>
        <button type="submit" class="btn btn-primary">Registrovat se</button>
        <a href="login.php" class="btn btn-light">Přihlásit se</a>
        <a href="index.php" class="btn btn-light">Zpět na domovskou stránku</a>
    </form>

<?php
include 'inc/footer.php';