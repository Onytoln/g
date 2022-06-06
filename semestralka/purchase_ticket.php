<?php
require_once 'inc/user.php';
require_once 'utils/trains_utils.php';

$pageTitle = 'Nákup jízdenek';
include 'inc/header.php';

$deleteUnassignedTickets = $db->prepare("DELETE FROM ticket WHERE seats is null;");
$deleteUnassignedTickets->execute();

if (!LoggedIn()) {
    $_SESSION['general_error'] = "Pro koupu jízdenek musíte být přihlášen.";
    header('Location: index.php');
}

if (empty($_SESSION['purchase_schedule_id'])) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

$schQ = $db->prepare("SELECT * FROM time_schedule WHERE id=:id LIMIT 1;");
$schQ->execute([
    ':id' => $_SESSION['purchase_schedule_id']
]);

$schRes = $schQ->fetchObject();

if(empty($schRes)){
    $_SESSION['general_error'] = "Nastala neznámá chyba při čtení z databáze. Prosím zkuste to znovu.";
    header('Location: index.php');
    exit();
}

$trainNameQ = $db->prepare("SELECT name FROM trains WHERE id=:id LIMIT 1;");
$trainNameQ->execute([
    ':id' => $schRes->train_id
]);

$trainName = $trainNameQ->fetchColumn();

if(empty($trainName)){
    $_SESSION['general_error'] = "Nastala neznámá chyba při čtení z databáze. Prosím zkuste to znovu.";
    header('Location: index.php');
    exit();
}

$locQ = $db->prepare("(SELECT name FROM location WHERE id=:id1 LIMIT 1) UNION (SELECT name FROM location WHERE id=:id2 LIMIT 1);");
$locQ->execute([
    ':id1' => $schRes->from_location,
    ':id2' => $schRes->to_location
]);
$locationNames = $locQ->fetchAll(PDO::FETCH_ASSOC);

if(empty($locationNames)){
    $_SESSION['general_error'] = "Nastala neznámá chyba při čtení z databáze. Prosím zkuste to znovu.";
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

    if (count(explode(" ", $name)) < 2) {
        $errors['name'][] = 'Neplatně zadané jméno a příjmení. Zadejte <b>Jméno</b> <i>mezera</i> <b>Příjmení</b>';
    }

    if (!isset($_POST['seats_reserved'])) {
        $errors['seats_reserved'] = "Musíte zadat počet sedadel k rezervaci.";
    }

    if (empty($errors['seats_reserved'])) {
        $seatsQ = $db->prepare("SELECT * FROM time_schedule WHERE id=:id");
        $seatsQ->execute([
            ':id' => $schRes->id
        ]);

        $coreInfo = GetCoreTrainInfo(($seatsQ->fetchAll(PDO::FETCH_ASSOC))[0], false);

        if (($coreInfo->totalSeats - $coreInfo->takenSeats) == 0) {
            $_SESSION['general_error'] = "Během vaší registrace došlo ke kompletnímu zaplnění míst v daném vlaku.
                                          <br/>Koupě lístku proběhla neuspěšně.";
            header('Location: index.php');
        }

        if ((($coreInfo->totalSeats - $coreInfo->takenSeats) - $_POST['seats_reserved']) < 0) {
            $errors['seats_reserved'] = "Nemůžete zarezervovat toto množství sedadel. <br/> Zbývá jich: " .
                ($coreInfo->totalSeats - $coreInfo->takenSeats);
        }
    }

    if (empty($errors['name'])) {
        unset($errors['name']);
    }

    if (empty($errors)) {
        try {
            $createTicketQ = $db->prepare("INSERT INTO ticket (time_schedule_id, user_id,name, seat_count) 
                                                 VALUES (:time_schedule_id, :user_id, :name, :seat_count); ");
            $createTicketQ->execute([
                ':time_schedule_id' => $_SESSION['purchase_schedule_id'],
                ':user_id' => $_SESSION['user_id'],
                ':name' => $name,
                ':seat_count' => $_POST['seats_reserved']
            ]);

            $_SESSION['ticket_purchase_id'] = $db->lastInsertId();
            $_SESSION['ticket_data'] = [];
            $_SESSION['ticket_data']['train_name'] = $trainName;
            $_SESSION['ticket_data']['from_location'] = $locationNames[0]['name'];
            $_SESSION['ticket_data']['to_location'] = $locationNames[1]['name'];
            $_SESSION['ticket_data']['start_time'] = date("d.m.Y H:i:s", strtotime($schRes->start_time));
            $_SESSION['ticket_data']['end_time'] = date("d.m.Y H:i:s", strtotime($schRes->end_time));
            header("Location: select_ticket_category.php");
        } catch (Exception $exc) {
            $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
            header('Location: index.php');
        }
    }
}

if (UserAdmin()) { ?>
    <a href="timeTable_edit.php" class="btn btn-primary">Správa jízdního řádu</a>
<?php }
if (LoggedIn()) {
    ?>
    <a href="#" class="btn btn-primary">Správa jízdenek</a>
<?php } ?>
<a href="purchase_ticket.php" class="btn btn-info">Obnovit stránku</a>
<a href="index.php" class="btn btn-secondary">Zpět na úvodní stránku</a>
<h2 class="mt-5">Nákup jízdenky pro</h2>
<span class="h6">
<?php
echo '<br/>Vlak: <b>' . $trainName .
    '</b><br/>z lokace: <b>' . $locationNames[0]['name'] . '</b> do lokace: <b>' . $locationNames[1]['name'] .
    '</b><br/>Datum odjezdu: <b>' . date("d.m.Y H:i:s", strtotime($schRes->start_time)) . '</b><br/> Datum příjezdu: <b>' .
    date("d.m.Y H:i:s", strtotime($schRes->end_time)) . '</b>';

echo '</span>';

?>
    <h3 class="mt-5">Zadejte klíčové informace</h3>
    <div class="form_input">
    <form method="post">
        <div class="form-group">
            <label for="name">Jméno a příjmení:</label>
            <input type="text" name="name" id="name" required placeholder="Jméno a příjmení"
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
            <label for="seats_reserved">Počet sedadel k rezervaci:</label>
            <input type="number" name="seats_reserved" id="seats_reserved" required
                   class="form-control <?php echo(!empty($errors['seats_reserved']) ? 'is-invalid' : ''); ?>"
                   value="<?php
                   if (!empty($_POST['seats_reserved'])) {
                       echo htmlspecialchars(@$_POST['seats_reserved']);
                   } else {
                       echo 1;
                   }
                   ?>"/>
            <?php
            echo(!empty($errors['seats_reserved']) ? '<div class="invalid-feedback">' . $errors['seats_reserved'] . '</div>' : '');
            ?>
        </div>
       <button type="submit" class="btn btn-primary">Pokračovat</button>
        <a href="index.php" class="btn btn-danger">Zrušit</a>
    </form>
    </div>

