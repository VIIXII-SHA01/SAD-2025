<?php
    /* main
    $host = 'sql100.infinityfree.com';
    $user = 'if0_40453362';
    $db_name = 'if0_40453362_host01';
    $db_password = 'Jma850pJnPbgp5';
    */

    //replacables
    $host = 'localhost';
    $user = 'root';
    $db_name = 'SAD_2025';
    $db_password = '';
    $conn;

    try{
        $conn = mysqli_connect($host, $user, $db_password, $db_name);
       // echo "connected";
    } catch(Exception $e) {
        echo $e;
    }
?>