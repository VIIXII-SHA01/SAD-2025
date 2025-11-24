<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Allow CORS for local dev (adjust for production)
header('Access-Control-Allow-Origin: http://localhost');

// Parse and normalize URI (remove base folder)
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uri = preg_replace('#^SAD-2025/?#', '', $uri);

// Helper: set a friendly exception and redirect to a path
function fail_and_redirect(string $message, string $redirectPath = 'register')
{
    $_SESSION['exception'] = $message;
    header("Location: {$redirectPath}");
    exit;
}

// ---------------------------
// POST HANDLERS (run BEFORE routing/includes)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Use the normalized $uri to decide handler
    if ($uri === 'register') {
        // REGISTER HANDLER
        $full_name = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm'] ?? '';

        // Keep email in session for verification step
        $_SESSION['email'] = $email;

        // Basic validation
        if ($password !== $confirm_password) {
            fail_and_redirect('Passwords do not match.', 'register');
        }
        if (empty($full_name) || empty($email) || empty($password)) {
            fail_and_redirect('Please fill in all fields.', 'register');
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student';
        $created_at = date('Y-m-d H:i:s');
        $status = 'unverified';

        // Start DB transaction
        $conn->begin_transaction();
        try {
            // Insert user
            $query = "INSERT INTO users (full_name, email, password, role, created_at, status)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('ssssss', $full_name, $email, $hashed_password, $role, $created_at, $status);

            try {
                $stmt->execute();
            } catch (mysqli_sql_exception $e) {
                // Duplicate entry? (MySQL error code 1062)
                if ($e->getCode() === 1062) {
                    $conn->rollback();
                    fail_and_redirect('That email already exists.', 'register');
                }
                throw $e;
            }

            $user_id = $conn->insert_id;
            $stmt->close();

            // Generate OTP and insert into verification after email send
            $otp = mt_rand(100000, 999999);

            // Send email
            $mail = new PHPMailer(true);
            try {
                // Configure these with your actual sender creds
                $senderEmail = 'marketingj786@gmail.com';
                $senderAppPassword = 'orxk bcjn eqdf nzsb'; // use app password stored securely in production

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $senderEmail;
                $mail->Password = $senderAppPassword;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                $mail->CharSet = 'UTF-8';
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->setFrom($senderEmail, 'Pacific Southbay College');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Verification Code';
                $mail->Body = "
                    <p>Your verification code is:</p>
                    <h2 style='font-size:32px;letter-spacing:3px;margin:0;'>$otp</h2>
                ";
                $mail->AltBody = "Your verification code is: $otp";

                if (!$mail->send()) {
                    // If mail sending fails, rollback and inform user
                    $conn->rollback();
                    fail_and_redirect('Failed to send verification email. Please try again later.', 'register');
                }
            } catch (Exception $e) {
                $conn->rollback();
                fail_and_redirect('Failed to send verification email. Please try again later.', 'register');
            }

            // Insert verification record (only after email was successfully sent)
            $query2 = "INSERT INTO verification (user_id, verification_code, created_at) VALUES (?, ?, NOW())";
            $stmt2 = $conn->prepare($query2);
            if (!$stmt2) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            // user_id is integer, otp as string
            $stmt2->bind_param('is', $user_id, $otp);
            $stmt2->execute();
            $stmt2->close();

            // Commit transaction
            $conn->commit();

            // Redirect to verification page (no output should have been sent yet)
            header('Location: verification');
            exit;

        } catch (Exception $e) {
            // Roll back on any exception, set friendly message and redirect
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            // Log $e->getMessage() to a file in production instead of exposing
            $_SESSION['exception'] = 'Registration failed. Please try again.';
            header('Location: register');
            exit;
        }
    }

    if ($uri === 'verification') {
        // VERIFICATION HANDLER
        $user_email = $_SESSION['email'] ?? '';
        $code = trim($_POST['verification_code'] ?? '');

        if (empty($user_email)) {
            fail_and_redirect('Session expired. Please register or login again.', 'register');
        }

        if (empty($code)) {
            fail_and_redirect('Please enter the verification code.', 'verification');
        }

        $conn->begin_transaction();
        try {
            $query = "
                SELECT v.verification_code
                FROM verification v
                INNER JOIN users u ON v.user_id = u.user_id
                WHERE u.email = ?
                ORDER BY v.created_at DESC
                LIMIT 1
            ";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('s', $user_email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $conn->rollback();
                fail_and_redirect('Verification record not found. Please request a new code.', 'verification');
            }

            $otp = (string)$row['verification_code'];

            // Compare numeric/string values loosely (user input is string)
            if ($code == $otp) {
                $update = "UPDATE users SET status = 'granted' WHERE email = ?";
                $stmt2 = $conn->prepare($update);
                if (!$stmt2) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $stmt2->bind_param('s', $user_email);
                $stmt2->execute();
                $stmt2->close();

                $conn->commit();

                // Optionally: delete used verification row or mark it consumed
                header('Location: login');
                exit;
            } else {
                $conn->rollback();
                $_SESSION['invalid_code'] = 'Wrong code. Please try again.';
                header('Location: verification');
                exit;
            }
        } catch (Exception $e) {
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            $_SESSION['failed'] = 'Verification failed. Please try again.';
            header('Location: verification');
            exit;
        }
    }
}

// ---------------------------
// ROUTING — include pages (no output has been sent by this script above)
// ---------------------------
switch ($uri) {
    case 'library':
        require 'main-page.php';
        break;

    case 'login':
        require 'login.php';
        break;

    case 'register':
        // register.php should show any $_SESSION['exception'] or $_SESSION['invalid_code']
        require 'register.php';
        break;

    case 'verification':
        // verification_page.php should also show messages from session
        require 'verification_page.php';
        break;

    default:
        http_response_code(404);
        echo "404 - Page not found";
        break;
}
