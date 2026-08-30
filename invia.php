<?php
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['data_ferie'])) {
    
    $data_inserita = $_POST['data_ferie']; // Formato YYYY-MM-DD
    $data_iso = $data_inserita . "T22:00:00.000Z";

    // Token JWT (Valido fino al 06/09/2026)
    $jwt_token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJGdWxsVXNlck5hbWUiOiJMQVpaQU5PIFNFVkVSSU5PIiwiRW1haWwiOiJyaW5vbGF6ekBnbWFpbC5jb20iLCJSZWdpc3RyYXRpb25OdW1iZXIiOiIxODgxIiwiRmlzY2FsQ29kZSI6IkxaWlNSTjg1TTE4RzI3M1ciLCJWaXNpYmlsaXR5Ijoie1wiSWRDb21wYW55XCI6XCIxMFwiLFwiSWREZXBhcnRtZW50XCI6XCIyXCIsXCJJZFNlY3RvclwiOlwiM1wiLFwiSWRPcmdhbml6YXRpb25hbFVuaXRcIjpcIkFWX0VYVERPTFwiLFwiSWRSZXNpZGVuY2VcIjpcIjgyNzAxXCJ9IiwibmJmIjoxNzg4MTEyMTQ5LCJleHAiOjE3ODg3MTIxNDksImlhdCI6MTc4ODExMjE0OX0.tW9v0u-O5xtkkAfmDCWjy77BAYidxmgoY-zUqCG4GhE";

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

    $ch = curl_init('https://frontendmyspriss.avmspa.it/api/RequestApi/SaveRequest');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'accept: application/json, text/plain, */*',
            'content-type: application/json',
            'authorization: Bearer ' . $jwt_token,
            'origin: https://spriss.avmspa.it',
            'referer: https://spriss.avmspa.it/',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/148.0.0.0 Safari/537.36'
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Visualizzazione esito
    echo "<!DOCTYPE html><html lang='it'><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><style>body{font-family:sans-serif;padding:20px;text-align:center;} .box{background:#f1f5f9;padding:20px;border-radius:8px;} a{display:inline-block;margin-top:15px;color:#0284c7;text-decoration:none;font-weight:bold;}</style></head><body><div class='box'>";

    if ($http_code === 200) {
        echo "<h3 style='color:#16a34a;'>✅ Richiesta Inviata!</h3>";
        echo "<p>La ferie per il giorno <strong>$data_inserita</strong> è stata registrata su MySpriss.</p>";
    } else {
        echo "<h3 style='color:#dc2626;'>❌ Errore ($http_code)</h3>";
        echo "<p>Impossibile completare la richiesta. Il token potrebbe essere scaduto.</p>";
    }

    echo "<a href='index.html'>← Torna indietro</a>";
    echo "</div></body></html>";
} else {
    header("Location: index.html");
    exit();
}
?>
