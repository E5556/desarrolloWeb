<?php
/* Sistema de retos y recompensas (N2)
   Llama a ps_challenge_tick($con, $uid, 'orders'|'spend'|'referrals'|'reviews', $amount)
   Se debe incluir points.php primero.
*/

function ps_challenge_tick($con, int $uid, string $type, int $amount = 1): void {
    $challenges_q = mysqli_query($con, "SELECT * FROM challenges WHERE type='" . mysqli_real_escape_string($con, $type) . "' AND active=1");
    if (!$challenges_q) return;

    while ($ch = mysqli_fetch_assoc($challenges_q)) {
        $cid = intval($ch['id']);
        // Calcular clave de período
        switch ($ch['period']) {
            case 'weekly':  $pkey = date('Y-W'); break;
            case 'once':    $pkey = 'once';      break;
            default:        $pkey = date('Y-m'); break;
        }

        // Upsert progreso
        mysqli_query($con, "INSERT INTO challenge_progress (challenge_id, user_id, period_key, progress)
            VALUES ($cid, $uid, '" . mysqli_real_escape_string($con, $pkey) . "', $amount)
            ON DUPLICATE KEY UPDATE progress = progress + $amount");

        // Verificar si se completó y no fue recompensado
        $prog_q = mysqli_query($con, "SELECT * FROM challenge_progress WHERE challenge_id=$cid AND user_id=$uid AND period_key='" . mysqli_real_escape_string($con, $pkey) . "' LIMIT 1");
        if (!$prog_q) continue;
        $prog = mysqli_fetch_assoc($prog_q);
        if (!$prog) continue;
        if ($prog['completed']) continue; // ya completado en este período
        if ($prog['progress'] >= $ch['target_value']) {
            // Marcar como completado y recompensar
            mysqli_query($con, "UPDATE challenge_progress SET completed=1, rewarded=1 WHERE challenge_id=$cid AND user_id=$uid AND period_key='" . mysqli_real_escape_string($con, $pkey) . "'");
            ps_points_add($con, $uid, intval($ch['reward_points']), 'Reto completado: ' . $ch['title']);
        }
    }
}
