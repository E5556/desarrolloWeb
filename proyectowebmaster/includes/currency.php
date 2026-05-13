<?php
/* Multi-moneda (R2)
   Moneda base: COP. Las tasas se configuran en admin/settings.
   Uso: ps_price($amount_cop) → string formateado en moneda activa de sesión
*/

function ps_currency_rates($con): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $keys = ['currency_usd_rate','currency_eur_rate','currency_brl_rate'];
    $q = mysqli_query($con, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('currency_usd_rate','currency_eur_rate','currency_brl_rate')");
    $rates = ['COP'=>1, 'USD'=>0.00025, 'EUR'=>0.00023, 'BRL'=>0.0013];
    while ($q && $r = mysqli_fetch_assoc($q)) {
        switch ($r['setting_key']) {
            case 'currency_usd_rate': $rates['USD'] = max(0.000001, floatval($r['setting_value'])); break;
            case 'currency_eur_rate': $rates['EUR'] = max(0.000001, floatval($r['setting_value'])); break;
            case 'currency_brl_rate': $rates['BRL'] = max(0.000001, floatval($r['setting_value'])); break;
        }
    }
    $cache = $rates;
    return $rates;
}

function ps_currency_active(): string {
    return $_SESSION['currency'] ?? 'COP';
}

function ps_price($con, float $cop_amount): string {
    $cur = ps_currency_active();
    if ($cur === 'COP') return '$' . number_format($cop_amount, 0, '.', ',');
    $rates = ps_currency_rates($con);
    $rate  = $rates[$cur] ?? 1;
    $converted = $cop_amount * $rate;
    $symbols = ['USD'=>'US$','EUR'=>'€','BRL'=>'R$'];
    $sym = $symbols[$cur] ?? $cur;
    return $sym . number_format($converted, 2, '.', ',');
}
