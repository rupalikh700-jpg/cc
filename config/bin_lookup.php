<?php
function fetchBinDetails(string $bin): array {
    $bin = preg_replace('/\D/', '', $bin);
    if (strlen($bin) < 6) return ['error' => 'BIN must be at least 6 digits'];

    $api = getSetting('bin_lookup_api') ?: 'binlist.net';
    $url = "https://lookup.binlist.net/{$bin}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept-Version: 3'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return ['error' => 'BIN not found'];
    }

    $data = json_decode($response, true);
    if (!$data) return ['error' => 'Invalid response'];

    return [
        'bank_name'   => $data['bank']['name'] ?? null,
        'bank_url'    => $data['bank']['url'] ?? null,
        'bank_phone'  => $data['bank']['phone'] ?? null,
        'brand'       => $data['scheme'] ?? null,
        'type'        => $data['type'] ?? null,
        'country'     => $data['country']['name'] ?? null,
        'country_code'=> $data['country']['alpha2'] ?? null,
        'currency'    => $data['country']['currency'] ?? null,
        'prepaid'     => $data['prepaid'] ?? false,
    ];
}

function fetchBankName(string $bin): ?string {
    $result = fetchBinDetails($bin);
    return $result['bank_name'] ?? null;
}

function luhnCheck(string $number): bool {
    $number = preg_replace('/\D/', '', $number);
    $len = strlen($number);
    $sum = 0;
    $alternate = false;
    for ($i = $len - 1; $i >= 0; $i--) {
        $n = (int)$number[$i];
        if ($alternate) {
            $n *= 2;
            if ($n > 9) $n -= 9;
        }
        $sum += $n;
        $alternate = !$alternate;
    }
    return ($sum % 10) === 0;
}
