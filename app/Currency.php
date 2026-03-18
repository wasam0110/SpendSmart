<?php
/**
 * SpendSmart - Currency Service
 * - Holds the supported currencies ("denominations") configured by the system (data/currencies.json)
 * - Fetches live FX rates (with a small file cache)
 * - Converts amounts between any supported currencies
 */

class Currency {
    private const CACHE_FILE = 'exchange_rates_cache.json';
    private const DEFAULT_TTL_SECONDS = 600; // 10 minutes

    /**
     * Return supported currencies from data/currencies.json.
     * Each item: code, name, symbol, (optional) denominations.
     */
    public static function listSupported(): array {
        $currencies = readJsonFile('currencies.json');
        if (!is_array($currencies)) return [];

        $out = [];
        foreach ($currencies as $c) {
            if (!is_array($c)) continue;
            $code = strtoupper(trim((string)($c['code'] ?? '')));
            if ($code === '') continue;

            $out[] = [
                'code' => $code,
                'name' => (string)($c['name'] ?? $code),
                'symbol' => (string)($c['symbol'] ?? $code),
                // Optional: list of supported denominations/units the system wants to expose.
                // If not provided, UI will just use the currency itself.
                'denominations' => isset($c['denominations']) && is_array($c['denominations']) ? $c['denominations'] : []
            ];
        }
        return $out;
    }

    public static function isSupported(string $code): bool {
        $code = strtoupper(trim($code));
        if ($code === '') return false;

        foreach (self::listSupported() as $c) {
            if (($c['code'] ?? '') === $code) return true;
        }
        return false;
    }

    public static function getSymbol(string $code): string {
        $code = strtoupper(trim($code));
        foreach (self::listSupported() as $c) {
            if (($c['code'] ?? '') === $code) {
                $sym = (string)($c['symbol'] ?? '');
                return $sym !== '' ? $sym : $code;
            }
        }
        return $code !== '' ? $code : '$';
    }

    /**
     * Convert amount from one currency to another.
     * Returns null if rate lookup fails.
     */
    public static function convert(float $amount, string $fromCode, string $toCode, int $ttlSeconds = self::DEFAULT_TTL_SECONDS): ?float {
        $fromCode = strtoupper(trim($fromCode));
        $toCode = strtoupper(trim($toCode));

        if ($fromCode === '' || $toCode === '') return null;
        if ($fromCode === $toCode) return $amount;

        // Prefer live rates (cached)
        $rates = self::getLiveRates($fromCode, $ttlSeconds);
        if (is_array($rates) && isset($rates[$toCode]) && is_numeric($rates[$toCode])) {
            return $amount * floatval($rates[$toCode]);
        }

        // Fallback: use rateToUSD values stored in currencies.json if available
        $fallback = self::convertUsingRateToUSD($amount, $fromCode, $toCode);
        if ($fallback !== null) return $fallback;

        return null;
    }

    /**
     * Returns associative array of rates for base currency: ["EUR" => 0.91, ...].
     */
    public static function getLiveRates(string $baseCode, int $ttlSeconds = self::DEFAULT_TTL_SECONDS): ?array {
        $baseCode = strtoupper(trim($baseCode));
        if ($baseCode === '') return null;

        $cache = self::readCache();
        $now = time();

        if (isset($cache[$baseCode]) && is_array($cache[$baseCode])) {
            $entry = $cache[$baseCode];
            $ts = isset($entry['timestamp']) ? intval($entry['timestamp']) : 0;
            $rates = $entry['rates'] ?? null;

            if ($ts > 0 && ($now - $ts) < max(60, $ttlSeconds) && is_array($rates) && count($rates) > 0) {
                return $rates;
            }
        }

        $rates = self::fetchRatesFromProvider($baseCode);
        if (!is_array($rates) || count($rates) === 0) {
            // Keep any existing cached value if it exists (stale is better than nothing)
            if (isset($cache[$baseCode]['rates']) && is_array($cache[$baseCode]['rates'])) {
                return $cache[$baseCode]['rates'];
            }
            return null;
        }

        $cache[$baseCode] = [
            'timestamp' => $now,
            'rates' => $rates
        ];
        self::writeCache($cache);

        return $rates;
    }

    private static function fetchRatesFromProvider(string $baseCode): ?array {
        // Provider 1: open.er-api.com (no key, simple JSON)
        $url1 = 'https://open.er-api.com/v6/latest/' . rawurlencode($baseCode);
        $json1 = self::httpGetJson($url1);
        if (is_array($json1) && isset($json1['rates']) && is_array($json1['rates'])) {
            return self::normalizeRates($json1['rates']);
        }

        // Provider 2: exchangerate.host (may require a key in some environments, but try)
        $url2 = 'https://api.exchangerate.host/latest?base=' . rawurlencode($baseCode);
        $json2 = self::httpGetJson($url2);
        if (is_array($json2) && isset($json2['rates']) && is_array($json2['rates'])) {
            return self::normalizeRates($json2['rates']);
        }

        return null;
    }

    private static function httpGetJson(string $url): ?array {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'header' => "Accept: application/json\r\nUser-Agent: SpendSmart/1.0\r\n"
            ]
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false || $raw === '') {
            // Fallback to cURL for environments where allow_url_fopen is disabled
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                if ($ch !== false) {
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'User-Agent: SpendSmart/1.0']);
                    $raw = curl_exec($ch);
                    curl_close($ch);
                }
            }
        }

        if ($raw === false || $raw === null || $raw === '') return null;

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function normalizeRates(array $rates): array {
        $out = [];
        foreach ($rates as $code => $rate) {
            $code = strtoupper(trim((string)$code));
            if ($code === '') continue;
            if (!is_numeric($rate)) continue;
            $val = floatval($rate);
            if ($val <= 0) continue;
            $out[$code] = $val;
        }
        return $out;
    }

    private static function readCache(): array {
        $cache = readJsonFile(self::CACHE_FILE);
        return is_array($cache) ? $cache : [];
    }

    private static function writeCache(array $cache): void {
        // Best-effort write; conversion still works without cache.
        @writeJsonFile(self::CACHE_FILE, $cache);
    }

    private static function convertUsingRateToUSD(float $amount, string $fromCode, string $toCode): ?float {
        $fromCode = strtoupper(trim($fromCode));
        $toCode = strtoupper(trim($toCode));

        $currencies = readJsonFile('currencies.json');
        if (!is_array($currencies)) return null;

        $fromRate = null;
        $toRate = null;
        foreach ($currencies as $c) {
            if (!is_array($c)) continue;
            $code = strtoupper(trim((string)($c['code'] ?? '')));
            if ($code === $fromCode && isset($c['rateToUSD']) && is_numeric($c['rateToUSD'])) {
                $fromRate = floatval($c['rateToUSD']);
            }
            if ($code === $toCode && isset($c['rateToUSD']) && is_numeric($c['rateToUSD'])) {
                $toRate = floatval($c['rateToUSD']);
            }
        }

        if ($fromRate === null || $toRate === null || $fromRate <= 0 || $toRate <= 0) return null;
        return ($amount * $fromRate) / $toRate;
    }
}
