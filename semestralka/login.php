<?php
require_once 'inc/user.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = false;
if (!empty($_POST)) {
    $userQuery = $db->prepare('SELECT * FROM users WHERE email=:email LIMIT 1;');
    $userQuery->execute([
        ':email' => trim($_POST['email'])
    ]);
    if ($user = $userQuery->fetch(PDO::FETCH_ASSOC)) {

        if (password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: index.php');
            exit();
        } else {
            $errors = true;
        }

    } else {
        $errors = true;
    }
}

$pageTitle = 'Přihlášení';
include 'inc/header.php';

if (!empty($_SESSION['general_error'])) {
    echo '<div class="alert alert-danger mt-4">';
    echo $_SESSION['general_error'];
    echo '</div>';

    unset($_SESSION['general_error']);
}
?>
    <h2>Přihlášení uživatele</h2>

    <form method="post">
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" required
                   class="form-control <?php echo($errors ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['email']) ?>"/>
            <?php
            echo($errors ? '<div class="invalid-feedback">Neplatná kombinace přihlašovacího e-mailu a hesla.</div>' : '');
            ?>
        </div>
        <div class="form-group">
            <label for="password">Heslo:</label>
            <input type="password" name="password" id="password" required
                   class="form-control <?php echo($errors ? 'is-invalid' : ''); ?>"/>
        </div>
        <button type="submit" class="btn btn-primary">Přihlásit se</button>
        <a href="registration.php" class="btn btn-primary">Registrovat se</a>
        <a href="reset_passwd.php" class="btn btn-info">Obnovit heslo</a>
        <a href="index.php" class="btn btn-light">Zrušit</a>
    </form>

<?php
include 'inc/footer.php';
