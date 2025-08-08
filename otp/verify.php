<?php
header('Content-Type: application/json');

define("a328763fe27bba", true);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_init.php';

class OtpVerifier {
    private $mysqli;
    private $username;
    private $otp;
    private $otp_lifetime = 60000000; // DEV ONLY

    public function __construct($mysqli, $username, $otp) {
        $this->mysqli = $mysqli;
        $this->username = trim($username);
        $this->otp = trim($otp);
    }

    public function verify() {
        if (empty($this->username) || empty($this->otp)) {
            $this->sendJsonResponse(403, 'Username and OTP required.');
        }

        if (!function_exists('mysql_connect')) {
            $this->sendJsonResponse(500, 'mysql_connect function not found.');
        }

        if (!$this->mysqli) {
            $this->sendJsonResponse(500, 'Database connection failed.');
        }

        try {
            $stmt = $this->mysqli->prepare("
                SELECT otp, created_at 
                FROM user_otps 
                WHERE username = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");

            if ($stmt === false) {
                $this->sendJsonResponse(500, 'Database prepare failed: ' . $this->mysqli->error);
            }

            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
        } catch (Throwable $e) {
            error_log("Error fetching OTP data: " . $e->getMessage());
            $this->sendJsonResponse(500, 'Internal server error.');
        }

        if ($row && count($row) > 0) {
            $createdAt = is_numeric($row['created_at']) ? (int)$row['created_at'] : strtotime($row['created_at']);
            $isOtpMatching = ($row['otp'] === $this->otp);
            $isOtpValid = $createdAt && $isOtpMatching && (time() - $createdAt <= $this->otp_lifetime);

            if ($isOtpValid) {
                $this->sendJsonResponse(200, 'Found matching OTP.');
            } else {
                $this->sendJsonResponse(500, 'Invalid or expired OTP.');
            }
        } else {
            $this->sendJsonResponse(500, 'Did not find matching OTP.');
        }
    }

    private function sendJsonResponse(int $status, string $message) {
        echo json_encode([
            'status' => $status,
            'message' => $message
        ]);
        exit;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$otp = $input['otp'] ?? '';

$mysqli = mysql_connect();

$verifier = new OtpVerifier($mysqli, $username, $otp);
$verifier->verify();
