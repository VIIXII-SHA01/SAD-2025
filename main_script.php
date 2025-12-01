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
        header('Content-Type: application/json');

        $full_name = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm'] ?? '';

        // Keep email in session for verification step
        $_SESSION['email'] = $email;

        // Basic validation
        if ($password !== $confirm_password) {
            echo json_encode(['type' => 'error', 'message' => 'Passwords do not match.']);
            exit;
        }
        if (empty($full_name) || empty($email) || empty($password)) {
            echo json_encode(['type' => 'error', 'message' => 'Please fill in all fields.']);
            exit;
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
                throw new Exception('Database prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('ssssss', $full_name, $email, $hashed_password, $role, $created_at, $status);

            try {
                $stmt->execute();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) { // duplicate email
                    $conn->rollback();
                    echo json_encode(['type' => 'error', 'message' => 'That email already exists.']);
                    exit;
                }
                throw $e;
            }

            $user_id = $conn->insert_id;
            $stmt->close();

            // Generate OTP for verification
            $otp = mt_rand(100000, 999999);

            // Send verification email
            $mail = new PHPMailer(true);
            try {
                $senderEmail = 'marketingj786@gmail.com';
                $senderAppPassword = 'orxk bcjn eqdf nzsb'; // store securely in production

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
                $mail->Body = "<p>Your verification code is:</p><h2 style='font-size:32px;letter-spacing:3px;margin:0;'>$otp</h2>";
                $mail->AltBody = "Your verification code is: $otp";

                if (!$mail->send()) {
                    $conn->rollback();
                    echo json_encode(['type' => 'error', 'message' => 'Failed to send verification email. Please try again later.']);
                    exit;
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['type' => 'error', 'message' => 'Failed to send verification email. Please try again later.']);
                exit;
            }

            // Cleanup old verification codes
            $conn->query("DELETE FROM verification WHERE created_at < (NOW() - INTERVAL 3 MINUTE)");

            // Insert verification record
            $status = 'unused';
            $query2 = "INSERT INTO verification (user_id, verification_code, ver_status, created_at) VALUES (?, ?, ?, NOW())";
            $stmt2 = $conn->prepare($query2);
            if (!$stmt2) {
                throw new Exception('Database prepare failed: ' . $conn->error);
            }
            $stmt2->bind_param('iss', $user_id, $otp, $status);
            $stmt2->execute();
            $stmt2->close();

            // Commit transaction
            $conn->commit();

            echo json_encode([
                'type' => 'success',
                'message' => 'Registration successful! Verification code sent to your email.'
            ]);
            exit;
        } catch (Exception $e) {
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            echo json_encode(['type' => 'error', 'message' => 'Registration failed. Please try again.']);
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
                SELECT v.id, v.verification_code, v.ver_status
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

            if ($row['ver_status'] === 'used') {
                $conn->rollback();
                fail_and_redirect('This verification code has already been used. Please request a new code.', 'verification');
            }

            if (!$row) {
                $conn->rollback();
                fail_and_redirect('Verification record not found. Please request a new code.', 'verification');
            }

            $otp = (string)$row['verification_code'];

            // Compare numeric/string values loosely (user input is string)
            if ($code == $otp) {
                // 1. Update user status
                $update = "UPDATE users SET status = 'verified' WHERE email = ?";
                $stmt2 = $conn->prepare($update);
                if (!$stmt2) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $stmt2->bind_param('s', $user_email);
                $stmt2->execute();
                $stmt2->close();

                // 2. Mark the specific OTP as used
                $updateOTP = "UPDATE verification SET ver_status = 'used' WHERE id = ?";
                $stmt3 = $conn->prepare($updateOTP);
                if (!$stmt3) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                $stmt3->bind_param('i', $row['id']);  // from earlier SELECT
                $stmt3->execute();
                $stmt3->close();

                $conn->commit();

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

    if ($uri === 'forgot_password') {
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? '');
        $_SESSION['email'] = $email;

        if (empty($email)) {
            echo json_encode(['type' => 'error', 'message' => 'Please enter your email.']);
            exit;
        }

        // Check if email exists
        $query = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo json_encode(['type' => 'error', 'message' => 'Server error. Try again later.']);
            exit;
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            echo json_encode(['type' => 'error', 'message' => 'Email not found.']);
            exit;
        }

        $otp = mt_rand(100000, 999999);

        $mail = new PHPMailer(true);

        try {
            $senderEmail = 'marketingj786@gmail.com';
            $senderAppPassword = 'orxk bcjn eqdf nzsb';

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
            $mail->Body = "<p>Your verification code is:</p><h2 style='font-size:32px;letter-spacing:3px;margin:0;'>$otp</h2>";
            $mail->AltBody = "Your verification code is: $otp";

            // Actually send email
            if ($mail->send()) {
                // Insert OTP into database (optional)
                $cleanup = "DELETE FROM verification WHERE created_at < (NOW() - INTERVAL 3 MINUTE)";
                $conn->query($cleanup);

                $status = 'unused';
                $query2 = "INSERT INTO verification (user_id, verification_code, ver_status, created_at) VALUES (?, ?, ?, NOW())";
                $stmt2 = $conn->prepare($query2);
                $stmt2->bind_param('iss', $user['user_id'], $otp, $status);
                $stmt2->execute();
                $stmt2->close();

                echo json_encode(['type' => 'success', 'message' => 'Verification code sent to your email.']);
            }
        } catch (Exception $e) {
            echo json_encode(['type' => 'error', 'message' => 'Email error: ' . $mail->ErrorInfo]);
            exit;
        }

        exit;
    }

    if ($uri === 'getCode') {
        header('Content-Type: application/json');
        $email = $_SESSION['email'] ?? '';
        $code = trim($_POST['code'] ?? '');
        $_SESSION['verified_for_reset'] = false;

        if (empty($email)) {
            echo json_encode(['type' => 'error', 'message' => 'Session expired. Please try again.']);
            exit;
        }

        if (empty($code)) {
            echo json_encode(['type' => 'error', 'message' => 'Please enter the verification code.']);
            exit;
        }

        $query = "
            SELECT v.verification_code, v.ver_status
            FROM verification v
            INNER JOIN users u ON v.user_id = u.user_id
            WHERE u.email = ?
            ORDER BY v.created_at DESC
            LIMIT 1
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode(['type' => 'error', 'message' => 'Verification record not found. Please request a new code.']);
            exit;
        }
        if ($row['ver_status'] === 'used') {
            echo json_encode(['type' => 'error', 'message' => 'This verification code has already been used. Please request a new code.']);
            exit;
        }

        $otp = (string)$row['verification_code'];


        if ($code == $otp) {
            $_SESSION['verified_for_reset'] = true;
            $query = "UPDATE verification v
                      INNER JOIN users u ON v.user_id = u.user_id
                      SET v.ver_status = 'used'
                      WHERE u.email = ? AND v.verification_code = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $email, $otp);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['type' => 'success', 'message' => 'Code verified successfully.']);
        } else {
            echo json_encode(['type' => 'error', 'message' => 'Wrong code. Please try again.']);
        }

        exit;
    }

    if ($uri == 'reset') {
        header('Content-Type: application/json');

        if (!($_SESSION['verified_for_reset'] ?? false)) {
            echo json_encode([
                'type' => 'error',
                'message' => 'Unauthorized access. Please verify first.'
            ]);
            exit;
        }

        $email = $_SESSION['email'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($email)) {
            echo json_encode(['type' => 'error', 'message' => 'Session expired. Please try again.']);
            exit;
        }

        if (empty($new_password) || empty($confirm_password)) {
            echo json_encode(['type' => 'error', 'message' => 'Please complete all fields.']);
            exit;
        }

        if ($new_password !== $confirm_password) {
            echo json_encode(['type' => 'error', 'message' => 'Passwords do not match.']);
            exit;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $hashed_password, $email);

        if ($stmt->execute()) {
            $_SESSION['verified_for_reset'] = false;  // FIXED
            echo json_encode(['type' => 'success', 'message' => 'Password updated successfully. Redirecting to login...']);
        } else {
            echo json_encode(['type' => 'error', 'message' => 'Failed to update password. Try again.']);
        }

        $stmt->close();
        exit;
    }

    if ($uri == 'login') {
        header('Content-Type: application/json');

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($email) || empty($password)) {
            echo json_encode(['type' => 'error', 'message' => 'Please enter your email and password.']);
            exit;
        }

        $query = "SELECT user_id, full_name, email, password, role, status FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['type' => 'error', 'message' => 'Invalid email or password.']);
            exit;
        }

        if ($user['status'] !== 'verified') {
            echo json_encode(['type' => 'error', 'message' => 'Account not verified. Please check your email.']);
            exit;
        }

        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Set cookie if "Remember Me" is checked
        if ($remember) {
            setcookie('remember_me', $user['user_id'], time() + (30 * 24 * 60 * 60), "/");
        }

        echo json_encode([
            'type' => 'success',
            'message' => 'Login successful! Redirecting...',
            'role' => $user['role'],
            'login_user_id' => $user['user_id']
        ]);
        exit;
    }
    if ($uri === 'user') {
        // STUDENT DASHBOARD FORM SUBMISSION HANDLER
        header('Content-Type: application/json');

        // Require a logged-in user
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            echo json_encode(['type' => 'error', 'message' => 'User not logged in.']);
            exit;
        }

        // Read & sanitize inputs
        $lrn       = trim($_POST['lrn'] ?? '');
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName  = trim($_POST['lastName'] ?? '');
        $age       = trim($_POST['age'] ?? '');
        $gender    = trim($_POST['gender'] ?? '');
        $contact   = trim($_POST['contact'] ?? '');
        $course    = trim($_POST['course'] ?? '');

        // Validate required fields
        if (
            $lrn === '' || $firstName === '' || $lastName === '' || $age === '' ||
            $gender === '' || $contact === '' || $course === ''
        ) {

            echo json_encode(['type' => 'error', 'message' => 'Please fill in all fields.']);
            exit;
        }

        // Validate numeric age
        if (!filter_var($age, FILTER_VALIDATE_INT)) {
            echo json_encode(['type' => 'error', 'message' => 'Invalid age.']);
            exit;
        }

        // Insert - adjust table/column names to match your DB
        try{
            $query = "INSERT INTO registered 
            (user_id, lrn, first_name, last_name, age, gender, contact_num, course, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                echo json_encode([
                    'type' => 'error',
                    'message' => 'Database prepare error: ' . $conn->error
                ]);
                exit;
            }

            // Bind fields
            $stmt->bind_param(
                'isssisss',
                $user_id,
                $lrn,
                $firstName,
                $lastName,
                $age,
                $gender,
                $contact,
                $course
            );

            // Execute insert
            if ($stmt->execute()) {
                echo json_encode([
                    'type' => 'success',
                    'message' => 'Student information submitted successfully.',
                    'id' => $stmt->insert_id
                ]);
            } else {
                echo json_encode([
                    'type' => 'error',
                    'message' => 'Failed to submit student information: ' . $stmt->error
                ]);
            }

            // Close DB resources
            $stmt->close();
            $conn->close();

            exit;  // VERY IMPORTANT! Prevents HTML from being printed
        } catch (Exception $e) {
            echo json_encode(['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    if($uri === 'addUser'){
        // ADD USER HANDLER
        header('Content-Type: application/json');

        $full_name = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'student');
        $password = password_hash('Pacific2019', PASSWORD_DEFAULT); // default password
        $created_at = date('Y-m-d H:i:s');
        $status = 'unverified';

        if (empty($full_name) || empty($email) || empty($role)) {
            echo json_encode(['type' => 'error', 'message' => 'Please fill in all fields.']);
            exit;
        }

        $query = "INSERT INTO users (full_name, email, password, role, created_at, status)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo json_encode(['type' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('ssssss', $full_name, $email, $password, $role, $created_at, $status);

        try {
          if($stmt->execute()) {
            echo json_encode(['type' => 'success', 'message' => 'User Invited successfully.']);
            $mail = new PHPMailer(true);
            try {
                $query = "SELECT user_id FROM users WHERE email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                $user_id = $user['user_id'];

                $senderEmail = 'marketingj786@gmail.com';
                $senderAppPassword = 'orxk bcjn eqdf nzsb'; // store securely in production

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

                $link = "http://localhost/SAD-2025/accept-inv?user_id=" . urlencode($user_id);
                $mail->setFrom($senderEmail, 'Pacific Southbay College');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Verification';
                $mail->Body = "<p>Click this link to verified your account:</p><p>$link</p>";
                $mail->AltBody = "Click this link to verified your account: $link";

                if (!$mail->send()) {
                    $conn->rollback();
                    echo json_encode(['type' => 'error', 'message' => 'Failed to send verification email. Please try again later.']);
                    echo "<script>window.location.href='admin';</script>";
                    exit;
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['type' => 'error', 'message' => 'Failed to send verification email. Please try again later.']);
                echo "<script>window.location.href='admin';</script>";
                exit;
            }
          }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) { // duplicate email
                echo json_encode(['type' => 'error', 'message' => 'That email already exists.']);
                echo "<script>window.location.href='admin';</script>";
                exit;
            }
            echo json_encode(['type' => 'error', 'message' => 'Failed to add user: ' . $e->getMessage()]);
            echo "<script>window.location.href='admin';</script>";
            exit;
        }

        $stmt->close();
        header("Location: admin");
        exit;
    }

        if($uri == 'scan') {
              header('Content-Type: application/json');
            $lrn = trim($_POST['lrn'] ?? '');
            if (!$lrn) {
                echo json_encode(['type'=>'error','message'=>'Please provide an LRN.']);
                exit;
            }

            // --- Check if LRN exists ---
            $query = "SELECT * FROM registered WHERE lrn = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $lrn);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                echo json_encode(['type'=>'error','message'=>'LRN not found']);
                exit;
            }

            // --- Insert attendance ---
            $query2 = "INSERT INTO attendance_logs(att_id, time_in) VALUES (?, NOW())";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bind_param('i', $user['reg_id']);
            $stmt2->execute();
            $stmt2->close();

            // --- Return success JSON ---
            echo json_encode([
                'type' => 'success',
                'message' => "LRN {$lrn} recorded",
                'data' => $user  // can include fullname or other info
            ]);
            exit;
        }

}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($uri === 'admin-users') {

        header('Content-Type: application/json');

        $query = "SELECT * FROM users ORDER BY full_name ASC, user_id ASC";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            echo json_encode(['error' => $conn->error]);
            exit;
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
            exit;
        } else {
            echo json_encode(['error' => 'Failed to fetch users.']);
            exit;
        }
    }

    if($uri === 'accept-inv') {
        $user_id = $_GET['user_id'] ?? '';

        if (empty($user_id)) {
           echo json_encode(['type' => 'error', 'message' => 'Invalid user ID.']);
           echo "<script>window.location.href='admin';</script>";
        }

        $query = "UPDATE users SET status = 'verified' WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo json_encode(['type' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
        }
        $stmt->bind_param('i', $user_id);

        if ($stmt->execute()) {
            header('Location: login');
            exit;
        } else {
            echo json_encode(['type' => 'error', 'message' => 'Failed to verify account. Please try again.']);
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
       if(!empty($_SESSION['logged_in'])){
            $target = ($_SESSION['role'] === 'admin') ? 'admin' : 'user';
            header("Location: $target");
            exit;
        }

        require 'login.php';
        break;

    case 'admin':
        // Admin dashboard access control
        /*if( $_SESSION['logged_in'] == false || $_SESSION['role'] != 'admin' ){
            header('Location: login');
            exit;
        }*/
        require 'admin.php';
        break;

    case 'register':
      if(!empty($_SESSION['logged_in'])){
            $target = ($_SESSION['role'] === 'admin') ? 'admin' : 'user';
            header("Location: $target");
            exit;
        }

        // register.php should show any $_SESSION['exception'] or $_SESSION['invalid_code']
        require 'register.php';
        break;

    case 'forgot_password':
        if(!empty($_SESSION['logged_in'])){
            $target = ($_SESSION['role'] === 'admin') ? 'admin' : 'user';
            header("Location: $target");
            exit;
        }

        // forgotPass.php should also show messages from session
        require 'forgotPass.php';
        break;

    case 'verification':
     if(!empty($_SESSION['logged_in'])){
        $target = ($_SESSION['role'] === 'admin') ? 'admin' : 'user';
        header("Location: $target");
        exit;
    }

        // verification_page.php should also show messages from session
        require 'verification_page.php';
        break;

    case 'reset':
       if(!empty($_SESSION['logged_in'])){
            $target = ($_SESSION['role'] === 'admin') ? 'admin' : 'user';
            header("Location: $target");
            exit;
        }

        // verification_page.php should also show messages from session
        require 'reset_password.php';
        break;

    case 'user':
        // verification_page.php should also show messages from 
        if( $_SESSION['logged_in'] == false || $_SESSION['role'] != 'student' ){
            header('Location: login');
            exit;
        }
        require 'student-dashboard.php';
        break;

    case 'scan':
        require 'scanner.php';
        break;

    case 'logout':
        // Clear session and cookies
        session_start();
        $_SESSION = [];
        session_destroy();
        setcookie('remember_me', '', time() - 3600, "/");
        header('Location: login');
        break;
        
    default:
        http_response_code(404);
        echo "404 - Page not found";
        break;
}
