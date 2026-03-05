<?php
$host = getenv('DATABASE_URL');
echo "DATABASE_URL: " . ($host ?: 'not set') . "\n";

try {
    $pdo = new PDO('mysql:host=database;port=3306;dbname=pidev_db', 'app', '!ChangeMe!');
    echo "Connection successful!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
