<?php

// 🔐 CORS (viktig hvis du tester fra annet sted)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// ❌ Ikke vis errors i production
error_reporting(0);
ini_set('display_errors', 0);

// 🔑 Hent API key fra environment (Render)
$apiKey = getenv("OPENAI_API_KEY");

if (!$apiKey) {
    echo json_encode(["error" => "API key not set"]);
    exit;
}

// 📁 Sjekk om fil finnes
if (!isset($_FILES['audio'])) {
    echo json_encode(["error" => "No audio file uploaded"]);
    exit;
}

// 📁 Lag uploads mappe hvis ikke finnes
$uploadDir = __DIR__ . "/uploads/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 📁 Lagre fil
$fileName = uniqid() . ".webm";
$filePath = $uploadDir . $fileName;

if (!move_uploaded_file($_FILES['audio']['tmp_name'], $filePath)) {
    echo json_encode(["error" => "Failed to save file"]);
    exit;
}

// 🎤 Send til OpenAI Whisper
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.openai.com/v1/audio/transcriptions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $apiKey
    ],
    CURLOPT_POSTFIELDS => [
        "file" => "file" => new CURLFile($filePath, "audio/webm", "audio.webm"),
        "model" => "whisper-1",
        "language" => "no"
    ]
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(["error" => curl_error($curl)]);
    curl_close($curl);
    exit;
}

curl_close($curl);

$result = json_decode($response, true);

// 🔥 DEBUG: se hele response
if (!$result || isset($result["error"])) {
    echo json_encode([
        "error" => $result["error"]["message"] ?? "Unknown API error",
        "raw" => $response
    ]);
    exit;
}

$text = $result["text"] ?? null;

if (!$text) {
    echo json_encode([
        "error" => "No text returned",
        "raw" => $response
    ]);
    exit;
}