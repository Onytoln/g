<?php

class CoreTrainInfo{
    public $totalSeats;
    public $takenSeats;
    public $trainName;
}

const ScheduleStartDelay = '-15 minutes';
const ScheduleEndDelay = '+15 minutes';
const ScheduleAddDelay = '+30 minutes';

function GetCoreTrainInfo($schedule, $showName = true) : CoreTrainInfo{
    global $db;

    $seatsQ = $db->prepare("SELECT name from trains WHERE id=:id ;");
    $seatsQ->execute([
        ':id' => $schedule['train_id']
    ]);

    $trainName = $seatsQ->fetchColumn();

    if($showName) {
        echo '<td>' . htmlspecialchars($trainName) . '</td>';
    }

    $seatsStatus = explode(";", $schedule['seats']);

    unset($seatsStatus[count($seatsStatus) - 1]);

    $taken = 0;

    foreach ($seatsStatus as $seat) {
        if (substr($seat, strpos($seat, ":") + 1) == "1") {
            $taken++;
        }
    }
    $coreTrainInfo = new CoreTrainInfo();
    $coreTrainInfo->takenSeats = $taken;
    $coreTrainInfo->totalSeats = count($seatsStatus);
    $coreTrainInfo->trainName = $trainName;

    return $coreTrainInfo;
}
