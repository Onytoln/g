<?php
require_once 'inc/user.php';
require_once 'utils/trains_utils.php';

$deleteOldSchedulesQ = $db->prepare("DELETE FROM time_schedule WHERE start_time <= :now;");
$deleteOldSchedulesQ->execute([
    ':now' => date("Y-m-d H:i:s", time())
]);

try {
    if (UserAdmin()) {
        if (!empty($_POST['edit_schedule'])) {
            $_SESSION['schedule_edit_id'] = $_POST['edit_schedule'];

            header('Location: ' . $_SERVER['PHP_SELF']);
            //exit();
        }

        if (!empty($_POST['delete_val_schedule'])) {
            $checkQ = $db->prepare("SELECT * FROM time_schedule WHERE id=:id LIMIT 1");
            $checkQ->execute([
                ':id' => $_POST['delete_val_schedule']
            ]);

            $result = $checkQ->fetchAll(PDO::FETCH_ASSOC);

            $info = GetCoreTrainInfo($result[0]);

            if ($info->takenSeats == 0) {
                $deleteQuery = $db->prepare('DELETE FROM time_schedule WHERE id=:id;');
                $deleteQuery->execute([
                    ':id' => $_POST['delete_val_schedule']
                ]);

                $_SESSION['general_success'] = "Jízdní řád ID : " . $_POST['delete_val_schedule'] . ' byl úspěšně smazán.';
            } else {
                $_SESSION['general_error'] = 'Řád - ID: ' . $_POST['delete_val_schedule'] . ' nejde smazat, jelikož už na něj jsou objednané lístky.';
            }
            ?>
            <script type="text/javascript">
                window.location.href = 'timeTable_edit.php';
            </script>
            <?php
        }

        if (!empty($_POST['edit_schedule'])) {
            $_SESSION['schedule_edit_id'] = $_POST['edit_schedule'];
            ?>
            <script type="text/javascript">
                window.location.href = 'edit_time_schedule.php';
            </script>
            <?php
            exit();
        }
    }

    if (LoggedIn()) {
        if (!empty($_POST['purchase_ticket'])) {
            $schQ = $db->prepare("SELECT * FROM time_schedule WHERE id=:id LIMIT 1;");
            $schQ->execute([
                ':id' => $_POST['purchase_ticket']
            ]);

            $schRes = $schQ->fetchAll(PDO::FETCH_ASSOC);

            $coreInfo = GetCoreTrainInfo($schRes[0]);

            var_dump($coreInfo);

            if (($coreInfo->totalSeats - $coreInfo->takenSeats) > 0) {
                $_SESSION['purchase_schedule_id'] = $_POST['purchase_ticket'];
                ?>
                <script type="text/javascript">
                    window.location.href = 'purchase_ticket.php';
                </script>
                <?php
                //exit();
            } else {
                $_SESSION['general_error'] = "V tomto vlaku již nejsou žádná volná místa.";
            }
        }
    }
} catch (Exception $exc){
    $_SESSION['general_error'] = "Nastala neočekávaná chyba. Akci prosím opakujte.";
    header('Location: index.php');
    exit();
}

?>
<h2 class="mt-5">Jízdní řád</h2>

<?php
if (LoggedIn()) { ?>
    <form method="get">
        <div class="form-group">
            <label for="location_lookup_start">Vyhledat dle startovní lokace: </label>
            <input type="text" name="location_lookup_start" id="location_lookup_start"
                   class="form-control" value="<?php echo @$_GET['location_lookup_start'] ?>"/>
        </div>
        <div class="form-group">
            <label for="location_lookup_end">Vyhledat dle konečné lokace: </label>
            <input type="text" name="location_lookup_end" id="location_lookup_end"
                   class="form-control" value="<?php echo @$_GET['location_lookup_end'] ?>"/>
        </div>
        <lable><b>Vyhledat dle času odjezdu</b></lable>
        <div class="form-group">
            <label for="location_lookup_date1">Od:</label>
            <input type="datetime-local" name="location_lookup_date1" id="location_lookup_date1"
                   class="form-control" value="<?php echo @$_GET['location_lookup_date1'] ?>"/>
        </div>
        <div class="form-group">
            <label for="location_lookup_date2">Do:</label>
            <input type="datetime-local" name="location_lookup_date2" id="location_lookup_date2"
                   class="form-control" value="<?php echo @$_GET['location_lookup_date2'] ?>"/>
        </div>
        <button type="submit" class="btn btn-primary mb-3">Vyhledat</button>
    </form>
<?php } ?>

<?php
$timeScheduleQuery = null;
if (!empty($_GET)) {
    try {
        $term1 = trim(@$_GET['location_lookup_start']);
        $term2 = trim(@$_GET['location_lookup_end']);
        $date1 = @$_GET['location_lookup_date1'];
        $date2 = @$_GET['location_lookup_date2'];

        $date1 = date("Y-m-d H:i:s", strtotime($date1));
        $date2 = date("Y-m-d H:i:s", strtotime($date2));

        $timeScheduleQuery = $db->prepare('SELECT time_schedule.id, time_schedule.train_id, time_schedule.seats,
       time_schedule.from_location, time_schedule.to_location, time_schedule.start_time, time_schedule.end_time FROM time_schedule
LEFT JOIN location e ON time_schedule.from_location = e.id
LEFT JOIN location f ON time_schedule.to_location = f.id
WHERE (e.name IN (SELECT name FROM location WHERE name LIKE :term1) AND f.name IN (SELECT name FROM location WHERE name LIKE :term2)) 
  AND time_schedule.start_time >= :date1 AND time_schedule.start_time <= :date2
ORDER BY start_time LIMIT 10;');

        if (empty($date1) || !validateDate($date1)) {
            $date1 = date("Y-m-d H:i:s", strtotime(time()));
        }

        if (empty($date2) || !validateDate($date2) || $date2 < date("Y-m-d H:i:s")) {
            $date2 = date("Y-m-d H:i:s", strtotime("+1 year"));
        }

        $timeScheduleQuery->execute([
            ':term1' => '%' . $term1 . '%',
            ':term2' => '%' . $term2 . '%',
            ':date1' => $date1,
            ':date2' => $date2
        ]);

        echo '<div class="alert alert-success mt-4">';
        echo "Vyhledání proběhlo úspěšně!";
        echo '</div>';
    } catch (Exception $exc) {
        echo '<div class="alert alert-danger mt-4">';
        echo "Vyhledání selhalo. Zkuste to prosím znova.";
        echo '</div>';
    }
    ?>
    <script type="text/javascript">
        location.href = "#search"
    </script>
    <?php
} else {
    $timeScheduleQuery = $db->prepare('SELECT * FROM time_schedule ORDER BY start_time;');
    $timeScheduleQuery->execute();
}

$schedules = $timeScheduleQuery->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="scroll_div mb-5" id="search" style="height: 30em;">';
echo '<table class="table">';

if (!empty($schedules)) {

    echo '<thead class="thead-dark"><tr>';
    if (UserAdmin()) {
        echo '<th>ID</th>';
    }

    echo '<th>Název vlaku</th>
<th>Počet volných sedadel</th>
<th>Z</th>
<th>Do</th>
<th>Odjezd</th>
<th>Příjezd</th>';
    if (LoggedIn()) {
        if (UserAdmin()) {
            echo '<th class="text-center">Úpravy</th>';
        } else {
            echo '<th class="text-center">Možnosti</th>';
        }
    }
    echo '</tr></thead>';

    foreach ($schedules as $schedule) {
        echo '<tr>';
        if (UserAdmin()) {
            echo '<td>' . $schedule['id'] . '</td>';
        }

        $coreTrainInfo = GetCoreTrainInfo($schedule);

        echo '<td>' . ($coreTrainInfo->totalSeats - $coreTrainInfo->takenSeats) . '/' . $coreTrainInfo->totalSeats . '</td>';

        $locFromQ = $db->prepare("SELECT location.name FROM time_schedule JOIN location ON time_schedule.from_location = location.id
                                        WHERE time_schedule.id=:id LIMIT 1");
        $locFromQ->execute([
            ':id' => $schedule['id']
        ]);

        $locFromQres = $locFromQ->fetchObject();

        $locToQ = $db->prepare("SELECT location.name FROM time_schedule JOIN location ON time_schedule.to_location = location.id
                                      WHERE time_schedule.id=:id LIMIT 1");
        $locToQ->execute([
            ':id' => $schedule['id']
        ]);

        $locToQres = $locToQ->fetchObject();

        echo '<td>' . htmlspecialchars($locFromQres->name) . '</td>';
        echo '<td>' . htmlspecialchars($locToQres->name) . '</td>';

        $dateFrom = date("d.m.Y H:i:s", strtotime($schedule['start_time']));
        $dateTo = date("d.m.Y H:i:s", strtotime($schedule['end_time']));

        echo '<td>' . $dateFrom . '</td>';
        echo '<td>' . $dateTo . '</td>';

        echo '<td style="white-space: nowrap; display: flex; align-items: center; justify-content: space-evenly;">';
        echo '<form method="post">';
        if (LoggedIn()) {
            if (UserAdmin()) {
                ?>
                <button type="submit" class="btn btn-primary" name="edit_schedule"
                        value="<?php echo $schedule['id']; ?>"
                        title="Upravit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="bi bi-pen-fill" viewBox="0 0 16 16">
                        <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001z"/>
                    </svg>
                </button>
                <button type="submit" class="btn btn-danger" name="delete_val_schedule"
                        value="<?php echo $schedule['id']; ?>"
                        title="Odstranit"
                        onclick="return confirm('Opravdu chcete smazat jízdní řád ( <?php
                        echo 'ID: ' . $schedule['id'] . ' ,Vlak: ' . $coreTrainInfo->trainName
                            . '\r\nOdjezd: ' . $dateFrom . ' ,Příjezd: ' . $dateTo;
                        ?>)?')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                    </svg>
                </button>
            <?php }
            if (@$_SESSION['home']) {
                ?>
                <button type="submit" class="btn btn-success" name="purchase_ticket"
                        <?php echo (($coreTrainInfo->totalSeats - $coreTrainInfo->takenSeats) == 0 ? "disabled" : '') ?>
                        value="<?php echo $schedule['id']; ?>"
                        title="Koupit lístek">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="bi bi-bag" viewBox="0 0 16 16">
                        <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z"/>
                    </svg>
                </button>
            <?php }
        }
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="100%"><div class="alert alert-primary">Není evidován žádný jízdní řád!</div></td></tr>';
}

echo '</table>';
echo '</div>';
?>
<!--<script type="text/javascript">

    document.querySelector("#lookup").addEventListener("input", search(document.querySelectorAll("#lookup")))

    function search(val) {
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function () {
            document.getElementById("search").innerHTML = this.responseText;
        }
        xhttp.open("GET", "schedule.php?term=" + val);
        xhttp.send();
    }
</script>-->
