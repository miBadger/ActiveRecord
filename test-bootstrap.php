<?php

require('vendor/autoload.php');

$dbname = DB_NAME;
$dbuser = DB_USER;
$dbpass = DB_PASS;

// Cleanup SQL
$user_delete = "DROP USER IF EXISTS $dbuser@'localhost'; ";
$database_delete = "DROP DATABASE IF EXISTS $dbname; ";
$sql_cleanup = $user_delete . $database_delete;

// Setup SQL
$db_create = "CREATE DATABASE $dbname; ";
$user_create = "CREATE USER '$dbuser'@'localhost' IDENTIFIED BY '$dbpass'; ";
$user_alter = "ALTER USER 'phpunit'@'localhost' IDENTIFIED with mysql_native_password BY 'phpunit'; ";
$user_grant = "GRANT ALL PRIVILEGES ON *.* TO '$dbuser'@'localhost';";

$sql_setup = $sql_cleanup . $db_create . $user_create . $user_alter . $user_grant;
echo "Please enter mysql root password to set up a database environment for testing\n";
exec("echo \"$sql_setup\" | mysql -u root -p");
