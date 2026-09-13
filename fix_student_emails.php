<?php
$dbPath = __DIR__ . '/database/database.sqlite';
if (!file_exists($dbPath)) {
    fwrite(STDERR, "Database not found: {$dbPath}\n");
    exit(1);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->query("SELECT id, first_name, last_name, email FROM students ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $first = strtolower(trim((string)($row['first_name'] ?? '')));
    $last = strtolower(trim((string)($row['last_name'] ?? '')));
    $newEmail = ($first !== '' && $last !== '') ? $first . '.' . $last . '@ssct.edu.ph' : ($row['email'] ?? '');
    if ($newEmail === '') {
        continue;
    }

    $update = $db->prepare('UPDATE students SET email = :email WHERE id = :id');
    $update->execute([
        'email' => $newEmail,
        'id' => (int)$row['id'],
    ]);

    echo $row['id'] . ' | ' . $newEmail . PHP_EOL;
}
