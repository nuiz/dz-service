<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 19/10/2556
 * Time: 10:07 เธ.
 * To change this template use File | Settings | File Templates.
 */

$mysql_config = array(
    "host"=> "localhost",
    "database"=> "dance_zone",
    "username"=> "root",
    "password"=> "111111",
    "charset"=> "utf-8"
);
$pdo = new PDO("mysql:host={$mysql_config['host']};dbname={$mysql_config['database']};", $mysql_config['username'], $mysql_config['password']);
$pdo ->exec("set names {$mysql_config['charset']}");
if($pdo->query("UPDATE users SET type='normal', member_timeout='0000-00-00'
    WHERE type='member' AND DATE(member_timeout) < CURDATE()")){

    echo "update user type success";
}
else {
    $er = $pdo->errorInfo();
    echo $er[2];
}