<?php
header('Content-Type: application/json');

define("a328763fe27bba", true);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app_init.php';

class OtpRequester {
    private $mysqli;
    private $username;
    private $apiKey;

    public function __construct($mysqli, $username) {
        $this->mysqli = $mysqli;
        $this->username = trim($username);
        $this->apiKey = getenv('BREVO_API_KEY') ?? null;
    }

    public function requestOtp() {
        if (empty($this->username)) {
            $this->sendJsonResponse(403, 'Username is required.');
        }

        if (!function_exists('mysql_connect')) {
            $this->sendJsonResponse(500, 'mysql_connect function not found.');
        }

        if (!$this->mysqli) {
            $this->sendJsonResponse(500, 'Database connection failed.');
        }

        if (!$this->checkUserExists()) {
            $this->sendJsonResponse(500, 'Username not found.');
        }

        $this->callBrevoApiOrFallback();
    }

    private function checkUserExists() {
        $stmt = $this->mysqli->prepare("SELECT id FROM users WHERE username = ?");
        if ($stmt === false) {
            $this->sendJsonResponse(500, 'Prepare failed: ' . $this->mysqli->error);
        }
        $stmt->bind_param("s", $this->username);
        $stmt->execute();
        $stmt->store_result();

        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function callBrevoApiOrFallback() {
        $url = "https://api.brevo.com/v3/senders/{$this->username}/validate";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/json",
            "api-key: {$this->apiKey}",
            "content-type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "type" => "email"
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201 || empty($response)) {
            // For testing purposes only
            $this->generateFallbackOtp();
        } else {
            $this->sendJsonResponse(200, 'OTP sent via Brevo API.');
        }
    }

    private function generateFallbackOtp() {
        $otp = random_int(100000, 999999);

        $stmt = $this->mysqli->prepare("
            INSERT INTO user_otps (username, otp, expires_at, created_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())
        ");

        if ($stmt === false) {
            $this->sendJsonResponse(500, 'Prepare failed: ' . $this->mysqli->error);
        }

        $stmt->bind_param("ss", $this->username, $otp);
        $stmt->execute();
        $stmt->close();

        $this->sendJsonResponse(200, 'Fallback OTP generated (testing only)', ['otp' => $otp]);
    }

    private function sendJsonResponse(int $status, string $message, array $extra = []) {
        echo json_encode(array_merge([
            'status' => $status,
            'message' => $message,
        ], $extra));
        exit;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$mysqli = mysql_connect();
$otpRequester = new OtpRequester($mysqli, $username);
$otpRequester->requestOtp();
