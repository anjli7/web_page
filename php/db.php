<?php
$host = "localhost:3306";    
$user = "root";
$pass = "12345678";
$dbname = "application_system";    

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else{
  // echo "connected;";

}
?>
