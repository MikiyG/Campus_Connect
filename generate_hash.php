<?php
$new_password = 'admin123'; // Replace with your desired password
$hash = password_hash($new_password, PASSWORD_DEFAULT);
echo "New hash: " . $hash . "<br>";
echo "Use this hash to update the database.";
?>