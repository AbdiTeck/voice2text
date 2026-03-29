<?php
require __DIR__ . '/config.php';

// alltid send JSON
header('Content-Type: application/json');

// slå på feilrapportering for debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Sjekk DB-tilkobling
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // sjekk om audio filen er sendt
    if (!isset($_FILES['audio'])) {
        throw new Exception("Ingen fil sendt.");
    }

    // Lag uploads-mappe hvis den ikke finnes
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    $filePath = $uploadDir . uniqid() . '.webm';
    if (!move_uploaded_file($_FILES['audio']['tmp_name'], $filePath)) {
        throw new Exception("Kunne ikke lagre filen.");
    }

    // Send til OpenAI Whisper API
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
    if(curl_errno($curl)){
        throw new Exception("cURL error: " . curl_error($curl));
    }
    curl_close($curl);

    $result = json_decode($response, true);
    if(!$result || !isset($result['text'])) {
        throw new Exception("Transcription failed: " . $response);
    }

    $text = $result['text'];

    // Lagre i database
    $stmt = $conn->prepare("INSERT INTO transcriptions (filename, text, user_id) VALUES (?, ?, ?)");
    $user_id = 1;
    $stmt->bind_param("ssi", $filePath, $text, $user_id);
    $stmt->execute();

    // send JSON tilbake
    echo json_encode(["text" => $text]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>