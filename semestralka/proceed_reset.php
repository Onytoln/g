<?php
require_once 'inc/user.php';

$pageTitle = 'Změna hesla';
include 'inc/header.php';

$errorEmail = null;

$deleteOldCodesQ = $db->prepare("DELETE FROM passwd_reset_codes WHERE expiry_time <= :now;");
$deleteOldCodesQ->execute([
    ':now' => date("Y-m-d H:i:s", time())
]);

if (empty($_SESSION['reset_mail'])) {
    $_SESSION['general_error'] = "Nastala chyba s obnovením hesla. Prosím zkuste to znovu.";
    header('Location: login.php');
};

try {
    $userQ = $db->prepare("SELECT * FROM users WHERE email=:email LIMIT 1");
    $userQ->execute([
        ':email' => $_SESSION['reset_mail']
    ]);

    $user = $userQ->fetchObject();

    $userID = $user->id;

    if (!empty($_GET['newC']) && $_GET['newC'] == "y") {
        $code = generateCode(6);

        $codeQ = $db->prepare("INSERT INTO passwd_reset_codes (user_id, code, expiry_time) 
                                     VALUES (:user_id, :code, :expiry_time);");

        $currentTime = time();

        $codeQ->execute([
            ':user_id' => $userID,
            ':code' => $code,
            ':expiry_time' => date("Y-m-d H:i:s", strtotime('+1 day', $currentTime))
        ]);


        $to = $user->email;
        $subject = "Kód k obnově hesla - Jízdní řády";

        $message = "<b>Váš kód k obnově hesla: </b>" . $code;

        $header = "From:jizdnirady@vse.cz \r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-type: text/html\r\n";

        mail ($to,$subject,$message,$header);
        header("Location: proceed_reset.php");
    }

    $errors = [];
    if (!empty($_POST)) {
        $errors['password'] = [];

        $codeQ = $db->prepare("SELECT * FROM passwd_reset_codes WHERE (user_id=:user_id AND expiry_time > :now );");
        $codeQ->execute([
            ':user_id' => $userID,
            ':now' => date("Y-m-d H:i:s", time())
        ]);

        $results = $codeQ->fetchAll(PDO::FETCH_ASSOC);

        if (empty($_POST['code']) || count($results) == 0) {
            $errors['code'] = "Nesprávně zadaný ověřovací kód";
        } else {
            $foundCode = false;
            foreach ($results as $result){
                if($result['code'] == trim($_POST['code'])){
                    $foundCode = true;
                }
            }
        }

        if(!$foundCode){
            $errors['code'] = "Nesprávně zadaný ověřovací kód";
        }

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

            $updateQuery = $db->prepare('UPDATE users SET password=:passwd WHERE id=:id;');
            $updateQuery->execute([
                ':passwd' => $password,
                ':id' => $userID
            ]);

            $deleteOldCodesQ = $db->prepare("DELETE FROM passwd_reset_codes WHERE user_id =:user_id;");
            $deleteOldCodesQ->execute([
                ':user_id' => $userID
            ]);

            unset($_SESSION['reset_mail']);
            LogUserOff();
        }
    }
} catch (Exception $exc) {
    $_SESSION['general_error'] = "Nastala chyba s obnovením hesla. Prosím zkuste to znovu.";
    header('Location: login.php');
    exit();
}

?>

<h2 class="mt-5">Obnova hesla</h2>

<form method="post">
    <div class="form-group">
        <label for="code">Osobní kód:</label>
        <input type="text" name="code" id="code" required
               class="form-control <?php echo(!empty($errors['code']) ? 'is-invalid' : ''); ?>"
               value="<?php echo htmlspecialchars(@$_POST['code']) ?>"/>
        <?php
        if (!empty($errors['code'])) {
            echo '<div class="invalid-feedback"><ul>';
            echo $errors['code'];
            echo '<br/><a href="proceed_reset.php?newC=y">Zaslat nový kód</a>';
            echo '</ul></div>';
        }
        ?>
    </div>
    <!--<a href="proceed_reset.php?newC=y" class="btn btn-primary mb-3">Zaslat nový kód</a>-->
    <div class="form-group">
        <label for="password">Nové heslo:</label>
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
    <button type="submit" class="btn btn-primary">Změnit heslo</button>
    <a href="login.php" class="btn btn-light">Přihlásit se</a>
    <a href="index.php" class="btn btn-light">Zpět na domovskou stránku</a>
</form>
