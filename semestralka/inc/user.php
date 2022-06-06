<?php

session_start();

require_once 'db.php';

const ForbiddenCharsLight = '}{@<>,|=_+';
const ForbiddenCharsHeavy = '\'^$&*()}{@?><>,|=_+-';

if (!empty($_SESSION['user_id'])) {
    $userQuery = $db->prepare('SELECT id FROM users WHERE id=:id AND active=1 LIMIT 1;');
    $userQuery->execute([
        ':id' => $_SESSION['user_id']
    ]);

    if ($userQuery->rowCount() != 1) {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        header('Location: index.php');
        exit();
    }
}

function LoggedIn(): bool
{
    if (!empty($_SESSION['user_id'])) return true;
    return false;
}

function LogUserOff()
{
    if (!empty($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
    }
    header('Location: login.php');
    exit();
}

function UserAdmin(): bool
{
    if (!LoggedIn()) return false;

    global $db;

    $checkForAdminQuery = $db->prepare('SELECT role FROM users WHERE id=:id LIMIT 1;');
    $checkForAdminQuery->execute([
        ':id' => $_SESSION['user_id']
    ]);

    if ($role = $checkForAdminQuery->fetch(PDO::FETCH_ASSOC)) {
        if ($role['role'] == 'admin') return true;
    }

    return false;
}

function ForbiddenCharUsageLight(array &$errors, string $string)
{
    if (preg_match('/[' . ForbiddenCharsLight . ']/', $string)) {
        $errors['name'][] = 'Zakázané použití nepovolených znaků';
        $splitString = str_split(ForbiddenCharsLight);
        $finalForm = ' ( ';

        $arrayLength = count($splitString);

        for ($i = 0; $i < $arrayLength; $i++) {
            if (strpos($string, $splitString[$i]) !== false) {
                $finalForm .= '<b style="font-size: 150%;">' . $splitString[$i] . '</b>';
            } else {
                $finalForm .= $splitString[$i];
            }

            if ($i != $arrayLength - 1) {
                $finalForm .= ' ';
            }
        }
        $finalForm .= ' )';

        $errors['name'][array_key_last($errors['name'])] .= $finalForm;
    }
}

function ForbiddenCharUsageHeavy(array &$errors, string $string)
{
    if (preg_match('/[' . ForbiddenCharsLight . ']/', $string)) {
        $errors['name'][] = 'Zakázané použití nepovolených znaků';
        $splitString = str_split(ForbiddenCharsHeavy);
        $finalForm = ' ( ';

        $arrayLength = count($splitString);

        for ($i = 0; $i < $arrayLength; $i++) {
            if (strpos($string, $splitString[$i]) !== false) {
                $finalForm .= '<b style="font-size: 150%;">' . $splitString[$i] . '</b>';
            } else {
                $finalForm .= $splitString[$i];
            }

            if ($i != $arrayLength - 1) {
                $finalForm .= ' ';
            }
        }
        $finalForm .= ' )';

        $errors['name'][array_key_last($errors['name'])] .= $finalForm;
    }
}

function isInt($string): bool
{
    return $string == (string)((int)$string);
}

function validateDate($date, $format = 'Y-m-d H:i:s'): bool
{
    return (DateTime::createFromFormat($format, $date) !== false);
}

function generateCode($length = 10): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function isHome(): bool
{
    global $home;
    return $home;
}
