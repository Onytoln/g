<?php

$pageTitle = 'Úvod';

require_once 'inc/user.php';
require_once 'utils/trains_utils.php';

include 'inc/header.php';

$_SESSION['home'] = true;

if (UserAdmin()) { ?>
    <a href="timeTable_edit.php" class="btn btn-primary" >Správa jízdního řádu</a>
<?php }
if (LoggedIn()) {
    ?>
    <a href="#" class="btn btn-primary">Správa jízdenek</a>
<?php } ?>
    <a href="index.php" class="btn btn-info">Obnovit stránku</a>

<?php

if (!empty($_SESSION['general_success'])) {
    echo '<div class="alert alert-success mt-4">';
    echo $_SESSION['general_success'];
    echo '</div>';

    unset($_SESSION['general_success']);
}
if (!empty($_SESSION['general_error'])) {
    echo '<div class="alert alert-danger mt-4">';
    echo $_SESSION['general_error'];
    echo '</div>';

    unset($_SESSION['general_error']);
}

require 'schedule.php';

include 'inc/footer.php';

