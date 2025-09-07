<?php 
    $host = "localhost";
    $port = 8889;
    $dbname = "texnologies_diadiktiou_db";
    $db_username = "root";
    $db_password = "root";
    $socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

    $conn = mysqli_connect($host, 
                        $db_username, 
                        $db_password, 
                        $dbname, 
                        $port, 
                        $socket);

    if (mysqli_connect_errno()) {
        die("Connection error: " . mysqli_connect_error());
    }