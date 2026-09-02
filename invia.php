<?php
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['data_ferie']) && !empty($_POST['jwt_token'])) {

    $data_inserita = $_POST['data_ferie']; // Es: "2026-09-26"
    $jwt_token = $_POST['jwt_token'];      // Incollato manualmente nel form, mai salvato nel codice

    // Conversione data in ora UTC (mezzanotte italiana = 22:00/23:00 del giorno prima UTC)
    $dateObj = new DateTime($data_inserita . ' 00:00:00', new DateTimeZone('Europe/Rome'));
    $dateObj->setTimezone(new DateTimeZone('UTC'));
    $data_iso = $dateObj->format('Y-m-d\TH:i:s.000\Z');

    $payload = json_encode([
        "IdCausal" => 2,
        "PunctualDate" => $data_iso,
        "NewPunctualDate" => $data_iso,
        "RangeDateFrom" => null,
        "RangeDateTo" => null,
        "NewRangeDateFrom" => null,
        "NewRangeDateTo" => null,
        "ChoiceSelected" => []
    ], JSON_UNESCAPED_SLASHES);

    $url = 'https://frontendmyspriss.avmspa.it/api/RequestApi/SaveRequest';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_FOLLOWLOCATION => true,   // Segue eventuali redirect del server
        CURLOPT_POSTREDIR => 3,           // Mantiene il metodo POST anche su redirect (risolve il 405)
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $jwt_token,
            'Origin: https://spriss.avmspa.it',
            'Referer: https://spriss.avmspa.it/',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/148.0.0.0 Safari/537.36'
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curl_error = curl_error($ch);
    curl_close($ch);

    echo "<!DOCTYPE html><html lang='it'><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><style>body{font-family:sans-serif;padding:20px;text-align:center;} .box{background:#f1f5f9;padding:20px;border-radius:8px;} pre{text-align:left;background:#e2e8f0;padding:10px;border-radius:4px;overflow-x:auto;}</style></head><body><div class='box'>";

    if ($http_code === 200) {
        echo "<h3 style='color:#16a34a;'>✅ Richiesta Inviata con Successo!</h3>";
        echo "<p>Data richiesta: <strong>$data_inserita</strong></p>";
        echo "<p>Risposta server: <code>$response</code></p>";
    } else {
        echo "<h3 style='color:#dc2626;'>❌ Errore HTTP: $http_code</h3>";
        echo "<p>URL contattato: <code>$effective_url</code></p>";
        if ($curl_error) {
            echo "<p><strong>Errore cURL:</strong> $curl_error</p>";
        }
        echo "<p><strong>Risposta dal server:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }

    echo "<br><a href='index.html'>← Torna indietro</a>";
    echo "</div></body></html>";

} else {
    header("Location: index.html");
    exit();
}
?>
