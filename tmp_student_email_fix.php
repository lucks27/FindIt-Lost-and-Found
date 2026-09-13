<?php
$db = new PDO('sqlite:database/database.sqlite');
$rows = $db->query("SELECT id, first_name, last_name, email FROM students ORDER BY id");
$updated = [];
foreach ($rows as $row) {
    $first = strtolower(trim((string)($row['first_name'] ?? '')));
    $last = strtolower(trim((string)($row['last_name'] ?? '')));
    $newEmail = ($first !== '' && $last !== '') ? $first . '.' . $last . '@ssct.edu.ph' : ($row['email'] ?? '');
    if ($newEmail !== '') {
        $db->exec("UPDATE students SET email = '" . str_replace("'", "''", $newEmail) . "' WHERE id = " . (int)$row['id']);
        $updated[] = [(int)$row['id'], $row['first_name'], $row['last_name'], $newEmail];
    }
}
foreach ($updated as $record) {
    echo implode(' | ', $record) . PHP_EOL;
}
