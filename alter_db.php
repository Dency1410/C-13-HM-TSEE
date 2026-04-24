<?php
require 'includes/db.php';
$res = mysqli_query($conn, "DESCRIBE products");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ", ";
}
?>
