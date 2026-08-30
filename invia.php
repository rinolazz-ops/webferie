<?php
header('Content-Type: application/json');

// 1. DATA E PARAMETRI (Ricevuti da form HTML o definiti nello script)
$data_richiesta = isset($_POST['data_ferie']) ? $_POST['data_ferie'] : '2026-09-25'; // Formato YYYY-MM-DD
$data_iso = $data_richiesta . 'T22:00:00.000Z';

// 2. INSERISCI QUI IL TUO TOKEN BEARER (Tratto dall'header Authorization)
$jwt_token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJGdWxsVXNlck5hbWUiOiJMQVpaQU5PIFNFVkVSSU5PIiwiRW1haWwiOiJyaW5vbGF6ekBnbWFpbC5jb20iLCJSZWdpc3RyYXRpb25OdW1iZXIiOiIxODgxIiwiRmlzY2FsQ29kZSI6IkxaWlNSTjg1TTE4RzI3M1ciLCJWaXNpYmlsaXR5Ijoie1wiSWRDb21wYW55XCI6XCIxMFwiLFwiSWREZXBhcnRtZW50XCI6XCIyXCIsXCJJZFNlY3RvclwiOlwiM1wiLFwiSWRPcmdhbml6YXRpb25hbFVuaXRcIjpcIkFWX0VYVERPTFwiLFwiSWRSZXNpZGVuY2VcIjpcIjgyNzAxXCJ9IiwibmJmIjoxNzg4MTEyMTQ5LCJleHAiOjE3ODg3MTIxNDksImlhdCI6MTc4ODExMjE0OX0.tW9v0u-O5xtkkAfmDCWjy77BAYidxmgoY-zUqCG4GhE";

// 3. PREPARAZIONE DEL PAYLOAD JSON
$payload = json_encode([
    "IdCausal" => 2,
    "PunctualDate" => $data_iso,
    "NewPunctualDate" => $data_iso,
    "RangeDateFrom" => null,
    "RangeDateTo" => null,
    "NewRangeDateFrom" => null,
    "NewRangeDateTo" => null,
    "ChoiceSelected" => []
]);

// 4. CONFIGURAZIONE CHIAMATA cURL
$ch = curl_init('https://frontendmyspriss.avmspa.it/api/RequestApi/SaveRequest');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json, text/plain, */*',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $jwt_token,
        'Origin: https://spriss.avmspa.it',
        'Referer: https://spriss.avmspa.it/',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/148.0.0.0 Safari/537.36'
    ],
]);

// 5. ESECUZIONE
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Output del risultato
if ($http_code === 200) {
    echo json_encode(["status" => "success", "message" => "Richiesta ferie inviata con successo per il " . $data_richiesta]);
} else {
    echo json_encode(["status" => "error", "http_code" => $http_code, "response" => json_decode($response)]);
}
?>
