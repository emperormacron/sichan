
<?php
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

$config = parse_ini_file('conf/config.ini');
$boardCFG = parse_ini_file('conf/config-boards.ini');
date_default_timezone_set("America/Los Angeles");
$time = time();

$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$isVideo = 0;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
$comment0 = nl2br(str_replace("'", "&#39;", $_POST['comment']), false);
$comment1 = nl2br(str_replace("\"", "&#34;", $_POST['comment']), false);
$username = $_POST['username'];
$board = $_POST['board'];
$ip = $_SERVER['REMOTE_ADDR'];
$userID = $_POST="userID";
$reader = new Reader($config['IPGeo']);
$record = $reader->country($ip);

$conn = new mysqli($boardCFG['dbhost-'.$board], $boardCFG['dbuser-'.$board], $boardCFG['dbpassword-'.$board], $boardCFG['db-'.$board]);
$connGlobal = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
$comment = $conn->real_escape_string($comment1);
$check = $connGlobal->query("SELECT * FROM BANS WHERE ip = '$ip'");
$banned = $check->num_rows;

if ($connGlobal->connect_error) {
    die('Database connection failed: '  . $connGlobal->connect_error);
}
if ($conn->connect_error) {
    die('Database connection failed: '  . $conn->connect_error);
}
if ($banned == 1){
    setcookie("isBanned", "True", time() + ((86400 * 30)), "/");
    echo "Sorry, you are banned!";
    $uploadOk = 0;
    $conn->close();
    $connGlobal->close();
    exit();
}

if (isset($_COOKIE["isBanned"])){
    if ($banned == 0){
        $connGlobal->query("INSERT INTO BANS (ip, reason, isRangeban) VALUES ('$ip', 'AUTOMATIC: BAN EVASION', 0)") or die(mysqli_error($conn));
        $conn->close();
        $connGlobal->close();
        echo "Nice Try";
    }
    exit();
}

if(isset($_POST["submit"])) {
    if($imageFileType == "mp4" || $imageFileType == "webm" || $imageFileType == "ogg" || $imageFileType == "mov"){
        echo "File is a video.";
        $uploadOk = 1;
        $isVideo = 1;
    }
    if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif" ) {
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
            $isVideo = 0;
        }    
        else {
            echo "File is not an image.";
            $uploadOk = 0;
        }
    }
    
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
        exit();
    }

    if ($_FILES["image"]["size"] > 40000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
        exit();
    }
}
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
    exit();
}
else {
    $temp = explode(".", $_FILES["image"]["name"]);
    $newfilename = round(microtime(true)) . '.' . end($temp);
    $countryCode = $record->country->isoCode;
    $country = strtolower($countryCode);
    $userID = substr(str_shuffle(str_repeat($x="0123456789abcdefghijklmnopqurstuvwxyzçşəıöüABCDEFGHIJKLMNOPQURSTUVWXYZÇŞƏİÖÜ", ceil(8/strlen($x)) )),1,8);
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir ."/". $newfilename)) { 
        $oldfilename = $_FILES["image"]["name"];
        echo "<script type='text/javascript'> localStorage.setItem('userID', '".$userID."'); </script>";
        $sql = $conn->query("INSERT INTO POSTS (time, name, filename, oldfilename, comment, ip, country, isVideo, userID) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$ip', '$country', '$isVideo', '$userID')") or die(mysqli_error($conn));
        echo "The file ". basename( $_FILES["image"]["name"]). " has been uploaded.";
        $sql2 = "SELECT id FROM POSTS WHERE time = '$time' AND name = '$username' AND ip = '$ip'";
        $getID = $conn->query($sql2) or die(mysqli_error($conn));
        while ($row = $getID->fetch_assoc()){
            $id = $row['id'];
            $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$id&action=new&board=$board");
            print "<p>".$bump."<p><br>";
        }

        $conn->close();
        $connGlobal->close();
        sleep(1);
        echo "<script type='text/javascript'>";
        echo "window.location.href = 'https://".$config['url']."/".$board."/thread.php?id=".$id."'";          
        echo "</script>";
    } else {
        echo "Sorry, there was an error uploading your file.";
        $conn->close();
        $connGlobal->close();
    }
}
?>
