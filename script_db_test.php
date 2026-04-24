<?php
require 'includes/db.php';
$tables = ['users', 'cart', 'products'];
foreach($tables as $t) {
    $res = $conn->query("SHOW CREATE TABLE `$t`");
    $row = $res->fetch_array();
    echo $row[1] . "\n\n";
}
?>
