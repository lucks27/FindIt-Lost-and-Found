<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->query("SELECT id, first_name, last_name, email FROM students ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "NO_STUDENT_RECORDS\n";
    exit(0);
}

foreach ($rows as $row) {
    echo $row['id'] . ' | ' . trim((string)($row['first_name'] ?? '')) . ' ' . trim((string)($row['last_name'] ?? '')) . ' | ' . ($row['email'] ?? '') . PHP_EOL;
}
