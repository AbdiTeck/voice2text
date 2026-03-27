<?php
require 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koble til database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (isset($_FILES['audio'])) {

    // Lagre fil
    //$filePath = '../uploads/' . uniqid() . '.webm';
    $filePath = 'uploads/' . uniqid() . '.webm';
    move_uploaded_file($_FILES['audio']['tmp_name'], $filePath);

    // Send til Whisper API
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/audio/transcriptions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . OPENAI_API_KEY
        ],
       CURLOPT_POSTFIELDS => [
    "file" => new CURLFile($filePath),
    "model" => "whisper-1",
    "language" => "no",
    "response_format" => "json"
]
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($response, true);
    $text = $result["text"] ?? "Feil ved transkribering";

    // Lagre i database
    $stmt = $conn->prepare("INSERT INTO transcriptions (filename, text, user_id) VALUES (?, ?, ?)");
    $user_id = 1;

    $stmt->bind_param("ssi", $filePath, $text, $user_id);
    $stmt->execute();

    echo json_encode([
        "text" => $text
    ]);
}
?>