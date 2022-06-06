<?php
require_once 'inc/user.php';
require_once 'utils/trains_utils.php';

$pageTitle = 'Nová jízdní řád';
include 'inc/header.php';

if (!UserAdmin()) header('Location: index.php');

$errors = [];
$correction = [];

if (!empty($_POST)) {

    $errors['train'] = [];

    $trainID = strtok(trim(@$_POST['train']), " ");
    if (strlen($trainID) > 0 && !is_numeric($trainID)) {
        $trainID = strtok(trim(@$_POST['train']), "-");
    }

    if (empty($trainID) || !isInt($trainID)) {
        $errors['train'][] = "Chybné zadání. Při psaní, vyberte z nabídky, nebo zadejte pouze ID vlaku a nebo zadejte ve formátu [ID - Název vlaku]";
    }

    $errors['destination_from'] = [];
    $errors['destination_to'] = [];

    $destinationFromID = strtok(trim(@$_POST['destination_from']), " ");
    if (empty($destinationFromID) && !is_numeric($destinationFromID)) {
        $destinationFromID = strtok(trim(@$_POST['destination_from']), "-");
    }
    $destinationToID = strtok(trim(@$_POST['destination_to']), " ");
    if (!empty($destinationToID) && !is_numeric($destinationToID)) {
        $destinationToID = strtok(trim(@$_POST['destination_to']), "-");
    }

    if (empty($destinationFromID) || !isInt($destinationFromID)) {
        $errors['destination_from'][] = "Chybné zadání. Při psaní, vyberte z nabídky, nebo zadejte pouze ID lokace a nebo zadejte ve formátu [ID - Název lokace]";
    }

    if (empty($destinationToID) || !isInt($destinationToID)) {
        $errors['destination_to'][] = "Chybné zadání. Při psaní, vyberte z nabídky, nebo zadejte pouze ID lokace a nebo zadejte ve formátu [ID - Název lokace]";
    }

    if (empty($errors['destination_from']) && empty($errors['destination_to']) && $destinationFromID == $destinationToID) {
        $errors['destination_both'] = "Lokace místa odjezdu a místa příjezdu nemůže být stejná!";
    }

    $errors['date_from'] = [];
    $errors['date_to'] = [];

    $finalDateFrom = date("Y-m-d H:i:s", strtotime(@$_POST["date_from"]));
    $finalDateTo = date("Y-m-d H:i:s", strtotime(@$_POST["date_to"]));

    if (empty($finalDateFrom) || !validateDate($finalDateFrom)) {
        $errors['date_from'][] = "Neplatné zadání času. Zobrazte kalendář pro výběr času pomocí ikonky nakonci textového pole.
         Zadávejte ve formátu [den.měsíc.rok hodina:minuta].";
    }

    if (empty($finalDateTo) || !validateDate($finalDateTo)) {
        $errors['date_to'][] = "Neplatné zadání času. Zobrazte kalendář pro výběr času pomocí ikonky nakonci textového pole.
         Zadávejte ve formátu [den.měsíc.rok hodina:minuta].";
    }

    if ($finalDateFrom > $finalDateTo) {
        $errors['date_both'] = "Datum příjezdu nemůže být dříve než datum odjezdu.";
    }

    if($finalDateFrom <= date('Y-m-d H:i:s', strtotime(ScheduleAddDelay))){
        $errors['date_from'][] = "Čas odjezdu nemůže být dříve než momentální čas + 30 minut";
    }

    if (empty($errors['train'])) {
        $checkScheduleQ = $db->prepare("SELECT * FROM time_schedule WHERE train_id=:train_id ORDER BY end_time");
        $checkScheduleQ->execute([
            ':train_id' => $trainID
        ]);

        $schedules = $checkScheduleQ->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($schedules)) {

            $errors['schedule'] = [];
            foreach ($schedules as $schedule) {

                $scheduleStart = date("Y-m-d H:i:s", strtotime($schedule['start_time'] . ScheduleStartDelay));
                $scheduleEnd = date("Y-m-d H:i:s", strtotime($schedule['end_time'] . ScheduleEndDelay));

                /*echo $schedule['start_time'] . ' ';
                echo $scheduleStart . '<br/>';
                echo $schedule['end_time'] . ' ';
                echo $scheduleEnd . '<br/>';*/


                if (($finalDateFrom <= $scheduleEnd && $finalDateFrom >= $scheduleStart)
                    || ($finalDateTo <= $scheduleEnd && $finalDateTo >= $scheduleStart)) {
                    $errors['schedule'][] = "Ve vámi stanovený časový interval má tento vlak již přidělený jízdní řád.<br/>
                     Začátek kolizní jízdy : " . date("d.m.Y H:i:s", strtotime($scheduleStart)) . "<br/>Konec kolizní jízdy: " .
                        date("d.m.Y H:i:s", strtotime($scheduleEnd));
                    break;
                }
            }

            $latestTime = date("Y-m-d H:i:s", strtotime("1970-01-01 00:00:00"));
            $lastDestinationBefore = null;
            $finalDestinationAfter = null;

            foreach ($schedules as $schedule) {
                if ($schedule['end_time'] > $latestTime && $schedule['end_time'] < $finalDateFrom) {
                    $latestTime = $schedule['end_time'];
                    $lastDestinationBefore = $schedule['to_location'];
                } else {
                    break;
                }
            }

            if ($lastDestinationBefore != null && $destinationFromID != $lastDestinationBefore) {
                $locQ = $db->prepare("SELECT * FROM location WHERE id=:id");
                $locQ->execute([
                    ':id' => $lastDestinationBefore
                ]);

                $result = $locQ->fetchObject();

                $errors['destination_from'][] = "Vlak nemůže odjíždět ze stanice ve které předtím neskončil. (Skončil v lokaci ID: "
                    . $result->id . ' ,Název lokace: ' . $result->name . ') <b>ID automaticky nastaveno na jedinou správnou možnou lokaci</b>';

                $correction['destination_from'] = $result->id;
            }
        }
    }

    if (empty($errors['train'])) {
        unset($errors['train']);
    }

    if (empty($errors['destination_from'])) {
        unset($errors['destination_from']);
    }

    if (empty($errors['destination_to'])) {
        unset($errors['destination_to']);
    }

    if (empty($errors['date_from'])) {
        unset($errors['date_from']);
    }

    if (empty($errors['date_to'])) {
        unset($errors['date_to']);
    }

    if (empty($errors['schedule'])) {
        unset($errors['schedule']);
    }

    if (empty($errors)) {
        try{
        $trainQuery = $db->prepare("SELECT seat_count FROM trains WHERE id=:id LIMIT 1;");
        $trainQuery->execute([
            ':id' => $trainID
        ]);

        $seatsCount = ($trainQuery->fetchObject())->seat_count;
        $seatResult = "";

        for ($i = 1; $i <= $seatsCount; $i++) {
            $seatResult .= $i . ':0;';
        }

        $query = $db->prepare('INSERT INTO time_schedule (train_id, seats, from_location, to_location, start_time, end_time)
                            VALUES (:train_id, :seats, :from_location, :to_location, :start_time, :end_time);');
        $query->execute([
            ':train_id' => $trainID,
            ':seats' => $seatResult,
            ':from_location' => $destinationFromID,
            ':to_location' => $destinationToID,
            ':start_time' => $finalDateFrom,
            ':end_time' => $finalDateTo
        ]);

        $setTrainActiveQuery = $db->prepare("UPDATE trains SET active=1 WHERE id=:id;");
        $setTrainActiveQuery->execute([
            ':id' => $trainID
        ]);

        $_SESSION['general_success'] = "Jízdní řád pro ID Vlaku: " . $trainID . ' ,Čas odjezdu: ' .
            date("d.m.Y H:i:s", strtotime($finalDateFrom)) . ' ,Čas příjezdu: ' . date("d.m.Y H:i:s", strtotime($finalDateTo)) .
            ' byl úspěšně vytvořen';
        header('Location: timeTable_edit.php');
        exit();
        } catch (Exception $exc){
            $_SESSION['general_error'] = "Nastala neočekávaná chyba. Byly pravděpodobně zadány chybné hodnoty pro ID vlaku/lokace.<br/>
                                          Prosím vybírejte ze seznamu nabízených vlaků/lokací dle zadaného ID/názvu vlaku/lokace do příslušného pole.";
            header('Location: timeTable_edit.php');
            exit();
        }
    }
}
?>

    <a href="timeTable_edit.php" class="btn btn-primary">Zpět na editaci</a>
    <a href="index.php" class="btn btn-secondary">Zpět na úvodní stránku</a>

    <h2 class="mt-5">Údaje</h2>
    <div class="alert alert-danger mt-3"
         style="<?php echo(!empty($errors['schedule']) ? '' : 'display: none'); ?>">
        <ul>
            <?php
            if (!empty($errors['schedule'])) {
                foreach ($errors['schedule'] as $error) {
                    echo '<li>' . $error . '</li>';
                }
            }
            ?>
        </ul>
    </div>
    <form method="post">
        <div class="form-group">
            <label for="train">Vlak: (výběr dle ID, nebo názvu vlaku)</label>
            <input type="text" name="train" id="train" required
                   class="form-control <?php echo(!empty($errors['train']) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['train']) ?>"/>
            <?php
            if (!empty($errors['train'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['train'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="destination_from">Z: (výběr dle ID, nebo názvu lokace)</label>
            <input type="text" name="destination_from" id="destination_from" required
                   class="form-control <?php echo(!empty($errors['destination_from']) ? 'is-invalid' : ''); ?>"
                   value="<?php
                   if (!empty($correction['destination_from'])) {
                       echo htmlspecialchars($correction['destination_from']);
                   } else {
                       echo htmlspecialchars(@$_POST['destination_from']);
                   }
                   ?>"/>
            <?php
            if (!empty($errors['destination_from'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['destination_from'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="destination_to">Do: (výběr dle ID, nebo názvu lokace)</label>
            <input type="text" name="destination_to" id="destination_to" required
                   class="form-control <?php echo(!empty($errors['destination_to']) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['destination_to']); ?>"/>
            <?php
            if (!empty($errors['destination_to'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['destination_to'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            }
            ?>
        </div>
        <div class="alert alert-danger"
             style="<?php echo(!empty($errors['destination_both']) ? '' : 'display: none'); ?>">
            <?php
            if (!empty($errors['destination_both'])) {
                echo $errors['destination_both'];
            }
            ?>
        </div>
        <div class="form-group">
            <label for="date_from">Odjezd: </label>
            <input type="datetime-local" name="date_from" id="date_from" required
                   class="form-control <?php echo(!empty($errors['date_from']) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['date_from']) ?>"/>
            <?php
            if (!empty($errors['date_from'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['date_from'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            }
            ?>
        </div>
        <div class="form-group">
            <label for="date_to">Příjezd:</label>
            <input type="datetime-local" name="date_to" id="date_to" required
                   class="form-control <?php echo(!empty($errors['date_to']) ? 'is-invalid' : ''); ?>"
                   value="<?php echo htmlspecialchars(@$_POST['date_to']) ?>"/>
            <?php
            if (!empty($errors['date_to'])) {
                echo '<div class="invalid-feedback"><ul>';

                foreach ($errors['date_to'] as $error) {
                    echo '<li>' . $error . '</li>';
                }

                echo '</ul></div>';
            }
            ?>
        </div>
        <div class="alert alert-danger" style="<?php echo(!empty($errors['date_both']) ? '' : 'display: none'); ?>">
            <?php
            if (!empty($errors['date_both'])) {
                echo $errors['date_both'];
            }
            ?>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Přidat záznam jízdy</button>
            <a href="timeTable_edit.php" class="btn btn-secondary">Zrušit</a>
        </div>
    </form>

    <script type="text/javascript">
        $(function () {
            $("#train").autocomplete({
                source: 'utils/trains_autocomplete.php'
            });
        });
        $(function () {
            $("#destination_from").autocomplete({
                source: 'utils/location_autocomplete.php'
            });
        });
        $(function () {
            $("#destination_to").autocomplete({
                source: 'utils/location_autocomplete.php'
            });
        });
    </script>

<?php
include 'inc/footer.php';