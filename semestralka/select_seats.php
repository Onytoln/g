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

if (empty($_SESSION['ticket_purchase_schedule_id'])) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

$seatAmountQ = $db->prepare("SELECT seat_count FROM ticket WHERE id=:id");
$seatAmountQ->execute([
    ':id' => $_SESSION['ticket_purchase_id']
]);

$seatAmount = 0;
$seatAmount = $seatAmountQ->fetchColumn();

if (empty($seatAmount)) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

$scheduleQ = $db->prepare("SELECT * FROM time_schedule WHERE id=:id");
$scheduleQ->execute([
    ':id' => $_SESSION['ticket_purchase_schedule_id']
]);

$scheduleResult = $scheduleQ->fetchObject();

if (empty($scheduleResult)) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

$seatData = explode(";", $scheduleResult->seats);
$seatFinalData = [];
$seatCount = -1;

foreach ($seatData as $seat) {
    $seatCount++;
    $s = explode(":", $seat);
    $seatFinalData[$s[0]] = @$s[1];
}

array_pop($seatFinalData);

if ($seatCount <= 0) {
    $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
    header('Location: index.php');
}

//var_dump($seatFinalData);

$errors = [];
$generalErr = null;
$seatsCounted = 0;

//var_dump($_POST);

if (!empty($_POST)) {
    $scheduleSeatResult = "";
    $ticketSeatResult = "";
    try {
        for ($i = 1; $i <= $seatCount; $i++) {
            if (isset($_POST[$i]) && $_POST[$i] === "on") {
                if ($seatFinalData[$i] === "1") {
                    echo "true";
                    unset($_POST[$i]);
                    $generalErr = 'V průběhu vaší objednávky bylo místo ' . $i . ' zabráno! Vyberte nová sedadla nebo zrušte objednávku.';
                    break;
                }

                $seatsCounted++;

                if ($seatAmount == $seatsCounted) {
                    $ticketSeatResult .= $i;
                } else {
                    $ticketSeatResult .= $i . ',';
                }

                $scheduleSeatResult .= $i . ':1;';

                if ($seatsCounted > $seatAmount) {
                    $generalErr = "Pro váš lístek máte objednaných " . $seatAmount . " míst, ale zabral jste " . $seatsCounted . ".
                    <br/> Toto není dovoleno.";
                    break;
                }
            } else {
                $scheduleSeatResult .= $i . ':' . $seatFinalData[$i] . ';';
            }
        }

        if (empty($generalErr) && $seatsCounted < $seatAmount) {
            $generalErr = "Pro váš lístek máte objednaných " . $seatAmount . " míst, ale zabral jste pouze" . $seatsCounted . ".
                    <br/> Toto není dovoleno.";
        }

        /*echo '<br/>';
        var_dump($scheduleSeatResult);
        echo '<br/>';
        var_dump($ticketSeatResult);*/

        //var_dump($_SESSION);
        if (empty($generalErr)) {
            $updateTicket = $db->prepare("UPDATE ticket SET seats=:seats WHERE id=:id;");
            $updateTicket->execute([
                ':seats' => trim($ticketSeatResult),
                ':id' => $_SESSION['ticket_purchase_id']
            ]);

            $updateSchedule = $db->prepare("UPDATE time_schedule SET seats=:seats WHERE id=:id");
            $updateSchedule->execute([
                ':seats' => trim($scheduleSeatResult),
                ':id' => $_SESSION['ticket_purchase_schedule_id']
            ]);

            unset($_SESSION['purchase_schedule_id']);
            unset($_SESSION['ticket_purchase_id']);
            unset($_SESSION['ticket_data']);
            unset($_SESSION['ticket_purchase_schedule_id']);
            $_SESSION['general_success'] = "Lístek byl úspěšně zakoupen.";
            header('Location: index.php');
            exit();
        }
    } catch (Exception $exc) {
        $_SESSION['general_error'] = "Nastala neznámá chyba. Prosím zkuste to znovu.";
        header('Location: index.php');
        exit();
    }
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

    <div class="text-center">
        <h3 class="mt-5">Vyberte sedadla</h3>
     <?php
     if ($generalErr !== null) {
         echo '<div class="alert alert-danger">';
         echo $generalErr;
         echo '</div>';
     }
     ?>

    <div class="form_input container">
    <form method="post">
        <button type="submit" id="finishTicket" class="btn btn-primary" disabled>Dokončit</button>
        <a href="index.php" class="btn btn-danger">Zrušit</a>
        <br>
        <span class="h6" id="seatText">Vyberte počet sedadel: <?php echo $seatAmount; ?></span>
        <table class="table">
        <?php
        $perRow = 4;
        $rowCount = 3;
        for ($i = 1; $i <= $seatCount; $i++) {
            $rowCount++;
            if ($rowCount === $perRow) {
                echo '<tr>';
            }
            echo '<td>';
            echo '<div class="form-check">';
            if ($seatFinalData[$i] == 1) {
                echo '<input class="form-check-input seat-checkbox" type="checkbox" name="' . $i . '" id="' . $i . '" checked disabled>';
                echo '<label class="form-check-label" for="' . $i . '"><s>' . $i . '</s></label>';
            } else {
                echo '<input class="form-check-input seat-checkbox" type="checkbox" name="' . $i . '" id="' . $i . '" 
                ' . (@$_POST[$i] === "on" ? 'checked' : '') . '>';
                echo '<label class="form-check-label" for="' . $i . '">' . $i . '</label>';
            }

            if (($i % 4) !== 0 && ($i % 2) === 0) {
                echo '<td>--ulička--</td>';
            }
            echo '</div>';
            echo '</td>';
            if ($rowCount === $perRow) {
                $rowCount = 0;
                if (($i % $perRow) == 0) {
                    echo '</tr>';
                }
            }
        }
        ?>
        </table>
    </form>
    </div>
    </div>
    <script type="text/javascript">
        var maxSeats = <?php echo $seatAmount; ?>;

        window.onload = checkSeats;

        $("input[type=checkbox]").on("change", checkSeats);

        var disabledInitially = $("input[type=checkbox]:checked").not(":enabled").length;

        function checkSeats() {
            var count = $("input[type=checkbox]:checked").length;

            count = count - disabledInitially;

            if ((maxSeats - count) !== 0) {
                document.getElementById("seatText").innerHTML = "Vyberte počet sedadel: " + (maxSeats - count);
            } else {
                document.getElementById("seatText").innerHTML = "Všechna sedadla vybrána!";
                document.getElementById("finishTicket").removeAttribute("disabled");
            }

            if (!(count < maxSeats)) {
                //$("input[type=checkbox]:not(:checked)").removeAttr("disabled");
                $("input[type=checkbox]:not(:checked)").not(":disabled").prop("disabled", true);
            } else {
                $("input[type=checkbox]:not(:checked)").removeAttr("disabled");
            }
        }
    </script>