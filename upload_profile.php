<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$userId = $_SESSION['user_id'];

if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error']===UPLOAD_ERR_OK){
    $fileTmp = $_FILES['profile_pic']['tmp_name'];
    $fileName = $_FILES['profile_pic']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif'];

    if(in_array($ext,$allowed)){
        $newName = 'profile_'.$userId.'.'.$ext;
        $uploadDir = 'images/profiles/';
        if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
        $dest = $uploadDir.$newName;

        if(move_uploaded_file($fileTmp,$dest)){
            $conn = new mysqli("localhost","u739446465_empowerher_db","u739446465_Empowerher_db@","u739446465_empowerher_db");
            if($conn->connect_error) die("Connection failed: ".$conn->connect_error);
            $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
            $stmt->bind_param("si",$dest,$userId);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
    }
}
header("Location: dashboard.php"); exit();
