<?php
/* Cálculo de tarifa de envío por zona (U1) */

function ps_shipping_zone($con, string $city, string $department = ''): ?array {
    $city = mb_strtolower(trim($city));
    $dept = mb_strtolower(trim($department));
    $zones = mysqli_query($con, "SELECT * FROM shipping_zones WHERE active=1");
    if (!$zones) return null;
    while ($z = mysqli_fetch_assoc($zones)) {
        $terms = array_map('mb_strtolower', array_map('trim', explode(',', $z['departments'])));
        foreach ($terms as $t) {
            if ($t !== '' && (str_contains($city, $t) || str_contains($dept, $t) || str_contains($t, $city))) {
                return $z;
            }
        }
    }
    // Fallback: zona con nombre "Nacional" o "Default"
    $def = mysqli_query($con, "SELECT * FROM shipping_zones WHERE active=1 AND (LOWER(zone_name) LIKE '%nacional%' OR LOWER(zone_name) LIKE '%default%') LIMIT 1");
    return ($def && $r = mysqli_fetch_assoc($def)) ? $r : null;
}

function ps_shipping_cost($con, string $city, float $order_total, string $department = ''): array {
    $zone = ps_shipping_zone($con, $city, $department);
    if (!$zone) return ['cost' => 0, 'zone' => null, 'days_min' => 1, 'days_max' => 5, 'free' => false];
    $free = $zone['free_from'] !== null && $order_total >= floatval($zone['free_from']);
    $cost = $free ? 0 : floatval($zone['base_price']);
    return [
        'cost'     => $cost,
        'zone'     => $zone['zone_name'],
        'days_min' => intval($zone['delivery_days_min']),
        'days_max' => intval($zone['delivery_days_max']),
        'free'     => $free,
        'free_from'=> $zone['free_from'],
    ];
}
