<?php
require_once 'user.php';
try {
?>
    <!DOCTYPE html>
    <html lang="cs">
    <head>
        <title><?php echo(!empty($pageTitle) ? $pageTitle . ' - ' : '') ?>Jízdní řády vlaků</title>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css"/>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">-->
        <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
              integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">-->
        <link rel="stylesheet" type="text/css" href="style/style.css">
        <script src="https://code.jquery.com/jquery-latest.js"></script>
        <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.js"
                integrity="sha256-6XMVI0zB8cRzfZjqKcD01PBsAy3FlDASrlC8SxCpInY="
                crossorigin="anonymous"></script>
    </head>
<body>
    <header class="container bg-dark <?php echo((!empty($home) && $home) ? 'home' : ''); ?>">
        <?php
        if (UserAdmin()) {
            echo '<h1 class="text-white py-4 px-2">Jízdní řády vlaků - <span class="admin_style">Admin</span></h1>';
        } else {
            echo '<h1 class="text-white py-4 px-2">Jízdní řády vlaků</h1>';
        }
        ?>
        <div class="text-right text-white">
            <?php
            if (!empty($_SESSION['user_id'])) {
                echo '<strong>' . htmlspecialchars($_SESSION['user_name']) . '</strong>';
                echo ' - ';
                echo '<a href="logout.php" class="text-white p-1">Odhlásit se</a>';
            } else {
                echo '<a href="login.php" class="text-white p-1">Přihlásit se</a>';
            }
            ?>
        </div>
    </header>
<main class="container pt-2 <?php echo((!empty($home) && $home) ? 'home' : ''); ?>">
    <?php } catch (Exception $exc){
    LogUserOff();
} ?>