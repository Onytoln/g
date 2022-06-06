<?php
require_once 'inc/user.php';
require_once 'utils/trains_utils.php';

$pageTitle = 'Nákup jízdenek';
include 'inc/header.php';

if (!LoggedIn()) {
    $_SESSION['general_error'] = "Pro koupu jízdenek musíte být přihlášen.";
    header('Location: index.php');
}

if (empty($_SESSION['purchase_schedule_id'])) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

if (empty($_SESSION['ticket_purchase_id'])) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

if (empty($_SESSION['ticket_data'])) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

//var_dump($_SESSION['ticket_purchase_id']);
//var_dump($_SESSION['ticket_data']);
$ticketQ = $db->prepare("SELECT * FROM ticket WHERE id=:id LIMIT 1;");
$ticketQ->execute([
    ':id' => $_SESSION['ticket_purchase_id']
]);

//var_dump($ticketQ);
$ticketResult = $ticketQ->fetchObject();
//var_dump($ticketResult);

if (empty($ticketResult)) {
    $_SESSION['general_error'] = "Nastala neznámá chyba při výběru lístku. Prosím zkuste to znovu.";
    header('Location: index.php');
}

$ticketTypeQ = $db->prepare("SELECT * FROM ticket_type;");
$ticketTypeQ->execute();

$ticketTypeResult = $ticketTypeQ->fetchAll(PDO::FETCH_ASSOC);

if (empty($ticketTypeResult)) {
    $_SESSION['general_error'] = "Nastala neznámá chyba při výběru typu lístků. Prosím zkuste to znovu.";
    header('Location: index.php');
}

$errors = [];
$generalErr = null;
$totalPrice = 0;
$priceConfirmed = false;
$typesPurchased = [];
$typeResult = "";

if (!empty($_POST['confirm'])) {
    $priceConfirmed = true;
}

if (!empty($_POST)) {
    for ($i = 1; $i <= $ticketResult->seat_count; $i++) {
        if (!empty($_POST[$i])) {
            $foundPrice = false;
            foreach ($ticketTypeResult as $ticketType) {
                if ($ticketType['id'] == $_POST[$i]) {
                    $totalPrice += $ticketType['price'];
                    if (!array_key_exists($ticketType['name'], $typesPurchased)) {
                        $typesPurchased[$ticketType['name']] = 0;
                    }
                    $typesPurchased[$ticketType['name']] += 1;
                    $foundPrice = true;
                    break;
                }
            }
            if ($foundPrice === false) {
                $generalErr = "Chyba při zadávání hodnot. Zkuste to znova, nebo začněte nákup nového lístku z jízního řádu znova.";
                break;
            }
        } else {
            $generalErr = "Chyba při zadávání hodnot. Zkuste to znova, nebo začněte nákup nového lístku z jízního řádu znova.";
            break;
        }
    }

    //var_dump($typesPurchased);
    //var_dump($_SESSION['totalPriceSet']);
    if (!empty($_POST['continue'])) {
        try {
            foreach ($typesPurchased as $key => $count) {
                $typeResult .= $key . ':' . $count . ';';
            }

            $updateTicketQ = $db->prepare("UPDATE ticket SET ticket_types=:ticket_types, total_price=:total_price WHERE id=:id;");
            $updateTicketQ->execute([
                ':ticket_types' => $typeResult,
                ':total_price' => $totalPrice,
                ':id' => $_SESSION['ticket_purchase_id']
            ]);

            $_SESSION['ticket_purchase_schedule_id'] = $ticketResult->time_schedule_id;
            header('Location: select_seats.php');
        } catch (Exception $exc) {
            $_SESSION['general_error'] = "Nastala neznámá chyba při výběru typu lístků. Prosím zkuste to znovu.";
            header('Location: index.php');
            exit();
        }
    }
    //var_dump($priceConfirmed);
}

?>
<h2 class="mt-5">Nákup jízdenky pro</h2>
<span class="h6">
<?php
echo '<br/>Vlak: <b>' . $_SESSION['ticket_data']['train_name'] .
    '</b><br/>z lokace: <b>' . $_SESSION['ticket_data']['from_location'] . '</b> do lokace: <b>' . $_SESSION['ticket_data']['to_location'] .
    '</b><br/>Datum odjezdu: <b>' . $_SESSION['ticket_data']['start_time'] . '</b><br/> Datum příjezdu: <b>' .
    $_SESSION['ticket_data']['end_time'] . '</b>';

echo '</span>';
?>

    <h3 class="mt-5">Vyberte typ jízenek</h3>
    <?php
    if ($generalErr !== null) {
        echo '<div class="alert alert-danger">';
        echo $generalErr;
        echo '</div>';
    }
    ?>
    <div class="form_input">
    <form method="post">
        <?php
        for ($i = 1; $i <= $ticketResult->seat_count; $i++) {
            echo '<div class="form-group">';
            echo '<label for="' . $i . '" class="mr-3">Osoba ' . $i . ': </label>';
            echo '<select name="' . $i . '" id="' . $i . '" required>';
            echo '<option value="">---vyberte---</option>';
            foreach ($ticketTypeResult as $ticketType) {
                echo '<option value="' . $ticketType['id'] . '" ' . (@$_POST[$i] == $ticketType['id'] ? 'selected' : '') . '>';
                echo $ticketType['name'] . ' - ' . $ticketType['price'] . ' Kč';
                echo '</option>';
            }
            echo '</select>';
            if (!empty($errors[$i])) {
                echo '<div class="text-danger">';
                echo $errors[$i];
                echo '</div>';
            }
            echo '</div>';
        }
        if ($totalPrice != 0) {
            echo 'Celková cena: ' . $totalPrice . ' Kč<br/>' . '<b>Prosím potvrďte cenu tlačítkem pokračovat!</b><br/>';
        }
        if (!$priceConfirmed) {
            ?>
            <button type="submit" name="confirm" value="1" class="btn btn-primary">Potvrdit cenu</button>
        <?php } else { ?>
            <button type="submit" name="continue" value="1" class="btn btn-primary">Pokračovat</button>
        <?php } ?>
        <a href="index.php" class="btn btn-danger">Zrušit</a>
    </form>
    </div>
