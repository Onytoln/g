<?php

require_once 'inc/user.php';

$pageTitle = 'Změna hesla';
include 'inc/header.php';

$errorEmail = null;

$deleteOldCodesQ = $db->prepare("DELETE FROM passwd_reset_codes WHERE expiry_time <= :now;");
$deleteOldCodesQ->execute([
        ':now' => date("Y-m-d H:i:s", time())
]);

if (!empty($_POST['email'])) {
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errorEmail = "Musíte zadat platný e-mail";
    }

    $query = $db->prepare("SELECT * FROM users WHERE email=:email LIMIT 1");
    $query->execute([
        ':email' => $_POST['email']
    ]);

    $user = $query->fetchObject();

    if (empty($errorEmail) && $query->rowCount() == 0) {
        $errorEmail = "Pod tímto e-mailem není registrovaný žádný účet.";
    }

    if (empty($errorEmail)) {
        $code = generateCode(6);

        $codeQ = $db->prepare("INSERT INTO passwd_reset_codes (user_id, code, expiry_time) 
                                     VALUES (:user_id, :code, :expiry_time);");

        $currentTime = time();

        $codeQ->execute([
            ':user_id' => $user->id,
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

        $_SESSION['reset_mail'] = $user->email;
        header("Location: proceed_reset.php");
    }
}
?>


    <a href="index.php" class="btn btn-primary">Na úvodní stránku</a>
    <a href="registration.php" class="btn btn-primary">Registrovat se</a>

    <h2 class="mt-5">Obnova hesla</h2>

    <form method="post">
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" required
                   class="form-control <?php echo(!empty($errorEmail) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['email']) ?>"/>
            <?php
            if (!empty($errorEmail)) {
                echo '<div class="invalid-feedback">';
                echo $errorEmail;
                echo '</div>';
            }
            ?>
        </div>

        <button type="submit" class="btn btn-primary">Zaslat ověřovací kód</button>
        <a href="index.php" class="btn btn-light">Zrušit</a>
    </form>

<?php

