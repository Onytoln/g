<?php
$pageTitle = 'Editace (Admin)';

require_once 'inc/user.php';
require_once 'utils/trains_utils.php';

include 'inc/header.php';

$_SESSION['home'] = false;


if (!UserAdmin()) header('Location: index.php');


if (!empty($_POST['edit_train'])) {
    $_SESSION['train_edit_id'] = $_POST['edit_train'];

    header('Location: edit_train.php');
    exit();
}

if (!empty($_POST['delete_val_train'])) {
    try {
        $deleteQuery = $db->prepare('DELETE FROM trains WHERE id=:id;');
        $deleteQuery->execute([
            ':id' => $_POST['delete_val_train']
        ]);
    } catch (Exception $exc) {
        $_SESSION['general_error'] = 'Vlak - ID: ' . $_POST['delete_val_train'] . ' nejde smazat, jelikož je přiřazen k jízdnímu řádu.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
}

if (!empty($_POST['edit_location'])) {
    $_SESSION['location_edit_id'] = $_POST['edit_location'];

    header('Location: edit_location.php');
    exit();
}

if (!empty($_POST['delete_val_location'])) {
    try {
        $deleteQuery = $db->prepare('DELETE FROM location WHERE id=:id;');
        $deleteQuery->execute([
            ':id' => $_POST['delete_val_location']
        ]);
    } catch (Exception $exc) {
        $_SESSION['general_error'] = 'Lokace - ID: ' . $_POST['delete_val_location'] . ' nejde smazat, jelikož je přiřazena k jízdnímu řádu.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
}

?>

    <a href="add_train.php" class="btn btn-primary">Zaevidovat nový vlak</a>
    <a href="add_location.php" class="btn btn-primary">Přidat novou lokaci</a>
    <a href="add_time_schedule.php" class="btn btn-primary">Přidat nový záznam jízdy</a>
    <a href="index.php" class="btn btn-secondary">Zpět na úvodní stránku</a>
    <a href="timeTable_edit.php" class="btn btn-info">Obnovit stránku</a>

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


echo '<h2 class="mt-5">Lokace</h2>';

$locationsQuery = $db->prepare('SELECT * FROM location;');
$locationsQuery->execute();

$locations = $locationsQuery->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="scroll_div">';
echo '<table class="table">';

echo '<thead class="thead-dark"><tr>
<th>ID</th>
<th>Název lokace</th>
<th class="text-center">Úpravy</th>
</tr></thead>';

if (!empty($locations)) {
    foreach ($locations as $location) {
        echo '<tr>';
        echo '<td>' . $location['id'] . '</td>';
        echo '<td>' . htmlspecialchars($location['name']) . '</td>';
        echo '<td class="text-center">';
        ?>
        <form method="post">
            <button type="submit" class="btn btn-primary" name="edit_location" value="<?php echo $location['id']; ?>"
                    title="Upravit">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-pen-fill" viewBox="0 0 16 16">
                    <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001z"/>
                </svg>
            </button>
            <button type="submit" class="btn btn-danger" name="delete_val_location"
                    value="<?php echo $location['id']; ?>"
                    title="Odstranit"
                    onclick="return confirm('Opravdu chcete smazat lokaci ( <?php
                    echo 'ID: ' . $location['id'] . ' ,Název: ' . $location['name'];
                    ?>)?')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                </svg>
            </button>
        </form>
        <?php
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="100%"><div class="alert alert-primary">Není evidována žádná lokace!</div></td></tr>';
}

echo '</table>';
echo '</div>';

echo '<h2 class="mt-5">Zaevidované vlaky</h2>';

$trainsQuery = $db->prepare('SELECT * FROM trains;');
$trainsQuery->execute();

$trains = $trainsQuery->fetchAll(PDO::FETCH_ASSOC);


echo '<div class="scroll_div">';

echo '<table class="table">';

if (!empty($trains)) {

    echo '<thead class="thead-dark"><tr>
<th>ID</th>
<th>Název vlaku</th>
<th>Počet sedadel</th>
<th>Přidělena jízda</th>
<th class="text-center">Úpravy</th>
</tr></thead>';

    foreach ($trains as $train) {

        $schQ = $db->prepare("SELECT * FROM time_schedule WHERE train_id =:train_id");
        $schQ->execute([
            ':train_id' => $train['id']
        ]);

        if($schQ->rowCount() == 0){
            $updateTrainActiveQ = $db->prepare("UPDATE trains SET active=0 WHERE id=:id");
            $updateTrainActiveQ->execute([
                    ':id' => $train['id']
            ]);
            $train['active'] = false;
        }

        echo '<tr>';
        echo '<td>' . $train['id'] . '</td>';
        echo '<td>' . htmlspecialchars($train['name']) . '</td>';
        echo '<td>' . $train['seat_count'] . '</td>';
        if ($train['active'] == true) {
            echo '<td>Ano</td>';
        } else {
            echo '<td>Ne</td>';
        }
        echo '<td class="text-center">';
        ?>
        <form method="post">
            <button type="submit" class="btn btn-primary" name="edit_train" value="<?php echo $train['id']; ?>"
                    title="Upravit">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-pen-fill" viewBox="0 0 16 16">
                    <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001z"/>
                </svg>
            </button>
            <button type="submit" class="btn btn-danger" name="delete_val_train" value="<?php echo $train['id']; ?>"
                    title="Odstranit"
                    onclick="return confirm('Opravdu chcete smazat vlak ( <?php
                    echo 'ID: ' . $train['id'] . ' ,Název: ' . $train['name'];
                    ?>)?')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                </svg>
            </button>
        </form>
        <?php
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="100%"><div class="alert alert-primary">Není evidován žádný vlak!</div></td></tr>';
}
echo '</table>';
echo '</div>';

require 'schedule.php';
