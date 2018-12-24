
<?php
$config = parse_ini_file('conf/config.ini');
date_default_timezone_set("America/Los Angeles");
$time = time();

$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

$comment = $_POST["comment"];
$username = $_POST['username'];
$ip = $_SERVER['REMOTE_ADDR'];

$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

if (!isset($_POST['username'])){
    $username = "Anonymous";
}
if ($conn->connect_error) {
    die('Database connection failed: '  . $conn->connect_error);
}


if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}
if ($_FILES["image"]["size"] > 5000000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
    echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    $uploadOk = 0;
}
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
}
else {
    $temp = explode(".", $_FILES["image"]["name"]);
    $newfilename = round(microtime(true)) . '.' . end($temp);
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir ."/". $newfilename)) {
        $oldfilename = $_FILES["image"]["name"];
        $sql = $conn->prepare("INSERT INTO POSTS (time, name, filename, oldfilename, comment, ip) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$ip')");
        $sql->execute() or die(mysqli_error($conn));
        $conn->close();
        echo "The file ". basename( $_FILES["image"]["name"]). " has been uploaded.";
        sleep(1);
        echo "<script type='text/javascript'>window.location.href = 'index.php';</script>";
    } else {
        echo "Sorry, there was an error uploading your file.";
        $conn->close();
    }
}
?>