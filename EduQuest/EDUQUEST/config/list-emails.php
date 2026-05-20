<?php
require_once 'database.php';
$result = $db->query('SELECT email FROM users LIMIT 10');
while($row = $result->fetch_assoc()) {
    echo $row['email'] . PHP_EOL;
}
