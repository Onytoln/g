<?php
require_once '../inc/user.php';

$term = trim($_GET['term']);

if (is_numeric($term)) {
    $acQuery = $db->prepare("SELECT * FROM trains WHERE id LIKE  :term LIMIT 10;");
} else {
    $acQuery = $db->prepare("SELECT * FROM trains WHERE name LIKE  :term LIMIT 10;");
}
$acQuery->execute([
    ':term' => '%' . $term . '%'
]);

$results = [];

while ($result = $acQuery->fetchObject()) {
    $results[] = $result->id . ' - ' . $result->name;
}

echo json_encode($results);