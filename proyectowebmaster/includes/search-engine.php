<?php
/* Motor de búsqueda mejorado (S1)
   - Tokeniza la consulta en palabras
   - Busca por nombre (peso 3), marca (peso 2), descripción (peso 1)
   - Tolera typos con SOUNDEX y LIKE por token
   - Soporta sinónimos configurables en admin/settings → search_synonyms
*/

function ps_search_synonyms($con): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key='search_synonyms' LIMIT 1");
    $raw = $q ? (mysqli_fetch_assoc($q)['setting_value'] ?? '') : '';
    $cache = [];
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $parts = array_map('trim', explode('=', $line, 2));
        if (count($parts) === 2 && $parts[0] !== '') {
            $cache[mb_strtolower($parts[0])] = array_map('trim', explode(',', $parts[1]));
        }
    }
    return $cache;
}

function ps_search_tokens($con, string $query): array {
    $query   = mb_strtolower(trim($query));
    $tokens  = array_filter(array_unique(preg_split('/\s+/', $query)));
    $synonyms = ps_search_synonyms($con);
    $expanded = [];
    foreach ($tokens as $tok) {
        $expanded[] = $tok;
        if (isset($synonyms[$tok])) {
            foreach ($synonyms[$tok] as $syn) $expanded[] = $syn;
        }
    }
    return array_values(array_unique($expanded));
}

/**
 * Búsqueda mejorada. Devuelve rows con campo `relevance_score`.
 * @param int $limit
 * @param int $offset
 * @param string $order_by 'relevance'|'price_asc'|'price_desc'
 */
function ps_search($con, string $query, int $limit = 12, int $offset = 0, string $order_by = 'relevance'): array {
    if (trim($query) === '') return ['rows' => [], 'total' => 0];

    $tokens = ps_search_tokens($con, $query);
    if (empty($tokens)) return ['rows' => [], 'total' => 0];

    // Construir score con CASE WHEN para cada token
    $score_parts = [];
    $where_parts = [];

    foreach ($tokens as $tok) {
        $safe = mysqli_real_escape_string($con, $tok);
        $like = '%' . $safe . '%';
        $sdx  = soundex($tok);

        // Peso por campo
        $score_parts[] = "(CASE WHEN LOWER(p.productName) LIKE '$like' THEN 4 ELSE 0 END)";
        $score_parts[] = "(CASE WHEN LOWER(p.productName) = '$safe' THEN 10 ELSE 0 END)"; // match exacto bonus
        $score_parts[] = "(CASE WHEN LOWER(p.productCompany) LIKE '$like' THEN 2 ELSE 0 END)";
        $score_parts[] = "(CASE WHEN LOWER(p.productDescription) LIKE '$like' THEN 1 ELSE 0 END)";
        // Tolerancia a typos via SOUNDEX
        $score_parts[] = "(CASE WHEN SOUNDEX(p.productName) = '$sdx' THEN 2 ELSE 0 END)";

        // WHERE: incluir si algún campo coincide (LIKE o SOUNDEX)
        $where_parts[] = "(LOWER(p.productName) LIKE '$like'
                         OR LOWER(p.productCompany) LIKE '$like'
                         OR LOWER(p.productDescription) LIKE '$like'
                         OR SOUNDEX(p.productName) = '$sdx')";
    }

    $score_expr = '(' . implode(' + ', $score_parts) . ')';
    // Debe coincidir con AL MENOS UN token
    $where_expr = '(' . implode(' OR ', $where_parts) . ')';

    $order_sql = match($order_by) {
        'price_asc'  => 'p.productPrice ASC',
        'price_desc' => 'p.productPrice DESC',
        default      => "$score_expr DESC, p.productName ASC",
    };

    // Total
    $count_sql = "SELECT COUNT(DISTINCT p.id) as total
                  FROM products p
                  WHERE $where_expr";
    $count_q = mysqli_query($con, $count_sql);
    $total   = $count_q ? intval(mysqli_fetch_assoc($count_q)['total']) : 0;

    // Rows
    $rows_sql = "SELECT DISTINCT p.id, p.productName, p.productImage1 AS productImage, p.productPrice,
                        p.productAvailability, p.productCompany, p.shippingCharge,
                        $score_expr AS relevance_score
                 FROM products p
                 WHERE $where_expr
                 ORDER BY $order_sql
                 LIMIT $limit OFFSET $offset";

    $res  = mysqli_query($con, $rows_sql);
    $rows = [];
    while ($res && $r = mysqli_fetch_assoc($res)) $rows[] = $r;

    return ['rows' => $rows, 'total' => $total];
}

/**
 * Sugerencias "¿Quisiste decir…?" cuando hay 0 resultados.
 * Usa SOUNDEX para encontrar productos fonéticamente similares.
 */
function ps_search_did_you_mean($con, string $query): ?string {
    $tokens = ps_search_tokens($con, $query);
    if (empty($tokens)) return null;
    $sdx = soundex($tokens[0]);
    $q = mysqli_query($con, "SELECT productName FROM products WHERE SOUNDEX(productName) = '$sdx' LIMIT 1");
    if ($q && $r = mysqli_fetch_assoc($q)) return $r['productName'];
    return null;
}
