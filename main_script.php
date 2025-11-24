<?php
session_start();
include('db.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
header('Access-Control-Allow-Origin: http://localhost');

require 'vendor/autoload.php';

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Remove base folder (SAD-2025 or SAD-2025/)
$uri = preg_replace('#^SAD-2025/?#', '', $uri);

// Routing Rules and navigations
switch ($uri) {
    case 'library':
        require 'main-page.php';
        break;

    case 'login':
        require 'login.php';
        break;

    case 'register':
        require 'register.php';
        break;

    case 'verification':
        require 'verification_page.php';
        break;

    default:
        http_response_code(404);
        echo "404 - Page not found";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['REQUEST_URI'] == '/SAD-2025/register') {
    $full_name = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm'] ?? '';
    //if password not match
    if($password !== $confirm_password) {
        header('location: register');
        exit;
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'student';
    $created_at = date('Y-m-d H:i:s');
    $status = 'unverified';

     $conn->begin_transaction();
    try{
    $query = "INSERT INTO users (full_name, email, password, role, created_at, status) VALUES(?,?,?,?,?,?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssss', $full_name, $email, $hashed_password, $role, $created_at, $status);
    if($stmt->execute()) {
         $user_id = $conn->insert_id;
         $mail = new PHPMailer(true);
        try{
           $sender = 'marketingj786@gmail.com';
            $password = 'orxk bcjn eqdf nzsb';
            $recipient = $email;
            $subject = 'Verification';

            // Generate OTP
            $otp = mt_rand(100000, 900000);

            // Create message with H2 size OTP
            $message = "
                <p>Your verification code is:</p>
                <h2 style='font-size: 32px; letter-spacing: 3px;'>$otp</h2>
            ";

            // Configure PHPMailer
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $sender;
            $mail->Password   = $password; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($sender);
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = "Your verification code is: $otp";

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            if($mail->send()) {
                try{
                    $query2 = "INSERT INTO verification (user_id, verification_code) VALUES (?,?)";
                    $stmt = $conn->prepare($query2);
                    $stmt->bind_param('ss',  $user_id, $otp);
                    $stmt->execute();
                    $conn->commit();
                } catch(mysqli_sql_exception) {
                  $_SESSION['exception'] = "Email is Already Existed!";
                }
            } else {
                echo "<script>alert('Error Sending Email')</script>";
            }
           header('location: verification');
           exit;
        }catch(Exception $e) {
            echo "<script>alert(error sending email)</script>";
            $_SESSION['exception'] = "error sending email";
        }
    }
         }   catch(mysqli_sql_exception) {
            $conn->rollback();
            echo"<script>alert(duplicate entry foe email $email)</script>";
            $_SESSION['exception'] = "Duplicate entry foe email $email";
        }
}
?>
