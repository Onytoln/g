<?php
require_once 'inc/user.php';
require_once 'utils/trains_utils.php';

$pageTitle = 'Upravit jízdní řád';
include 'inc/header.php';

if (!UserAdmin()) header('Location: index.php');

if (empty($_SESSION['schedule_edit_id'])) {
    $_SESSION['general_error'] = "Editace jízdního řádu selhala.";
    header('Location: timeTable_edit.php');
}

$scheduleQ = $db->prepare("SELECT * FROM time_schedule WHERE id=:id LIMIT 1;");
$scheduleQ->execute([
    ':id' => $_SESSION['schedule_edit_id']
]);
$foundSch = $scheduleQ->fetchObject();

$trainQ = $db->prepare("SELECT name FROM trains WHERE id=:id LIMIT 1;");
$trainQ->execute([
    ':id' => $foundSch->train_id
]);
$trainName = $trainQ->fetchColumn();

$locQ = $db->prepare("(SELECT name FROM location WHERE id=:id1 LIMIT 1) UNION (SELECT name FROM location WHERE id=:id2 LIMIT 1);");
$locQ->execute([
    ':id1' => $foundSch->from_location,
    ':id2' => $foundSch->to_location
]);
$locationNames = $locQ->fetchAll(PDO::FETCH_ASSOC);


$errors = [];
$correction = [];
$canChangeStartingLoc = true;
$rebuiltSeats = false;

$prevSchQ = $db->prepare("SELECT * FROM time_schedule WHERE train_id=:train_id AND end_time < :start_time;");
$prevSchQ->execute([
    ':train_id' => $foundSch->train_id,
    ':start_time' => $foundSch->start_time
]);

if ($prevSchQ->rowCount() > 0) {
    $canChangeStartingLoc = false;
}

if (!empty($_POST)) {

    $errors['train'] = [];

    $trainID = strtok(trim(@$_POST['train']), " ");
    if (strlen($trainID) > 0 && !is_numeric($trainID)) {
        $trainID = strtok(trim(@$_POST['train']), "-");
    }

    if (empty($trainID) || !isInt($trainID)) {
        $errors['train'][] = "Chybné zadání. Při psaní, vyberte z nabídky, nebo zadejte pouze ID vlaku a nebo zadejte ve formátu [ID - Název vlaku]";
    }

    $seatsStatus = explode(";", $foundSch->seats);
    unset($seatsStatus[count($seatsStatus) - 1]);
    $taken = 0;
    foreach ($seatsStatus as $seat) {
        if (substr($seat, strpos($seat, ":") + 1) == "1") {
            $taken++;
        }
    }

    $currentTrainSeatCount = count($seatsStatus);
    $newTrainSeatCount = 0;

    if (empty($errors['train'])) {
        $newTrainSeatsQ = $db->prepare("SELECT seat_count FROM trains WHERE id=:id LIMIT 1;");
        $newTrainSeatsQ->execute([
            ':id' => $trainID
        ]);

        $newTrainSeatCount = $newTrainSeatsQ->fetchColumn();
    }

    if ($taken > 0 && $currentTrainSeatCount != (int)$newTrainSeatCount) {
        $errors['train'][] = "Počet míst nového vlaku se nerovná počtu míst starého vlaku.<br/> 
                              Toto není dovoleno jelikož pro daný jizdní řád již jsou objednaná místa.";
    } elseif ($taken == 0 && $currentTrainSeatCount != (int)$newTrainSeatCount) {
        $rebuiltSeats = true;
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

    if (empty($errors['train'])) {
        $checkScheduleQ = $db->prepare("SELECT * FROM time_schedule WHERE train_id=:train_id AND id!=:id ORDER BY end_time;");
        $checkScheduleQ->execute([
            ':train_id' => $trainID,
            ':id' => $foundSch->id
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

            foreach ($schedules as $schedule) {
                if ($schedule['end_time'] > $latestTime && $schedule['end_time'] < $finalDateFrom) {
                    $latestTime = $schedule['end_time'];
                    $lastDestinationBefore = $schedule['to_location'];
                    $canChangeStartingLoc = false;
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
                $canChangeStartingLoc = false;
                $errors['destination_from'][] = "Vlak nemůže odjíždět ze stanice ve které předtím neskončil. (Skončil v lokaci ID: "
                    . $result->id . ' ,Název lokace: ' . $result->name . ') <b>ID automaticky nastaveno na jedinou správnou možnou lokaci.</b>';

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
        try {
            $seatResult = "";
            if ($rebuiltSeats) {
                $trainQuery = $db->prepare("SELECT seat_count FROM trains WHERE id=:id LIMIT 1;");
                $trainQuery->execute([
                    ':id' => $trainID
                ]);

                $seatsCount = ($trainQuery->fetchObject())->seat_count;
                for ($i = 1; $i <= $seatsCount; $i++) {
                    $seatResult .= $i . ':0;';
                }
            } else {
                $seatResult = $foundSch->seats;
            }

            $query = $db->prepare('UPDATE time_schedule SET train_id=:train_id, seats=:seats, 
                                     from_location=:from_location, to_location=:to_location, start_time=:start_time, end_time=:end_time
                                     WHERE id=:id;');
            $query->execute([
                ':train_id' => $trainID,
                ':seats' => $seatResult,
                ':from_location' => $destinationFromID,
                ':to_location' => $destinationToID,
                ':start_time' => $finalDateFrom,
                ':end_time' => $finalDateTo,
                ':id' => $foundSch->id
            ]);

            $setTrainActiveQuery = $db->prepare("UPDATE trains SET active=1 WHERE id=:id;");
            $setTrainActiveQuery->execute([
                ':id' => $trainID
            ]);

            unset($_SESSION['schedule_edit_id']);

            $_SESSION['general_success'] = "Jízdní řád pro ID Vlaku: " . $trainID . ' ,Čas odjezdu: ' .
                date("d.m.Y H:i:s", strtotime($finalDateFrom)) . ' ,Čas příjezdu: ' . date("d.m.Y H:i:s", strtotime($finalDateTo)) .
                ' byl úspěšně změnen.';
            header('Location: timeTable_edit.php');
            exit();
        } catch (Exception $exc){
            $_SESSION['general_error'] = "Nastala neočekávaná chyba. Prosím zkuste to znova.";
            header('Location: timeTable_edit.php');
            exit();
        }
    }
}
?>

    <a href="timeTable_edit.php" class="btn btn-primary">Zpět na editaci</a>
    <a href="index.php" class="btn btn-secondary">Zpět na úvodní stránku</a>

    <h2 class="mt-5">Editace jízdního řádu -
<?php
echo 'ID: ' . $foundSch->id . ' ,Název vlaku: ' . $trainName;
?>
    </h2>
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
                   value="<?php
                   if (!empty($_POST['train'])) {
                       echo htmlspecialchars(@$_POST['train']);
                   } else {
                       if (!empty($trainName)) {
                           echo $foundSch->train_id . ' - ' . htmlspecialchars($trainName);
                       }
                   }
                   ?>"/>
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
        <fieldset>
            <div class="form-group">
                <label for="destination_from">Z: (výběr dle ID, nebo názvu lokace)</label>
                <input type="text" name="destination_from" id="destination_from" required
                       class="form-control <?php echo(!empty($errors['destination_from']) ? 'is-invalid' : '');
                       echo($canChangeStartingLoc ? '' : 'disabled_input'); ?>"
                       value="<?php
                       if (!empty($correction['destination_from'])) {
                           echo htmlspecialchars($correction['destination_from']);
                       } elseif (!empty($_POST['destination_from'])) {
                           echo htmlspecialchars(@$_POST['destination_from']);
                       } else {
                           if (!empty($locationNames)) {
                               echo $foundSch->from_location . ' - ' . htmlspecialchars($locationNames[0]['name']);
                           }
                       }
                       ?>" <?php echo($canChangeStartingLoc ? '' : 'readonly'); ?>/>
                <?php
                if (!empty($errors['destination_from'])) {
                    echo '<div class="text-danger"><ul>';

                    foreach ($errors['destination_from'] as $error) {
                        echo '<li>' . $error . '</li>';
                    }

                    echo '</ul></div>';
                }
                if (!$canChangeStartingLoc) {
                    echo '<div class="alert alert-info mt-1">';
                    echo "Toto pole nemůže být změněno, jelikož vlak v jízdním řádě před tímto datem končí v dané stanici.";
                    echo '</div>';
                }
                ?>
            </div>
        </fieldset>
        <div class="form-group">
            <label for="destination_to">Do: (výběr dle ID, nebo názvu lokace)</label>
            <input type="text" name="destination_to" id="destination_to" required
                   class="form-control <?php echo(!empty($errors['destination_to']) ? 'is-invalid' : ''); ?>"
                   value="<?php
                   if (!empty($_POST['destination_to'])) {
                       echo htmlspecialchars(@$_POST['destination_to']);
                   } else {
                       if (!empty($locationNames)) {
                           echo $foundSch->to_location . ' - ' . htmlspecialchars($locationNames[1]['name']);
                       }
                   }
                   ?>"/>
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
                   value="<?php
                   if (!empty($_POST['date_from'])) {
                       echo htmlspecialchars(@$_POST['date_from']);
                   } else {
                       if (!empty($foundSch)) {
                           echo date("Y-m-d\TH:i", strtotime($foundSch->start_time));
                       }
                   }
                   ?>"/>
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
                   value="<?php
                   if (!empty($_POST['date_to'])) {
                       echo htmlspecialchars(@$_POST['date_to']);
                   } else {
                       if (!empty($foundSch)) {
                           echo date("Y-m-d\TH:i", strtotime($foundSch->end_time));
                       }
                   }
                   ?>"/>
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
            <button type="submit" class="btn btn-primary">Upravit záznam jízdy</button>
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