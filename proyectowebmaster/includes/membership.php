<?php
/* V2 — Membresia Premium
   Funciones para verificar y aplicar beneficios de membresia premium.
*/

function ps_membership_init($con): void {
    mysqli_query($con, "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS is_premium TINYINT(1) DEFAULT 0");
    mysqli_query($con, "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS free_shipping TINYINT(1) DEFAULT 0");
}

function ps_membership_active($con, int $uid): bool {
    ps_membership_init($con);
    $q = mysqli_query($con, "SELECT s.id FROM subscriptions s
        JOIN subscription_plans sp ON sp.id=s.plan_id
        WHERE s.user_id=$uid AND s.status='active' AND sp.is_premium=1 LIMIT 1");
    return $q && mysqli_num_rows($q) > 0;
}

function ps_membership_plan($con, int $uid): ?array {
    ps_membership_init($con);
    $q = mysqli_query($con, "SELECT sp.*, s.next_billing, s.created_at as sub_start FROM subscriptions s
        JOIN subscription_plans sp ON sp.id=s.plan_id
        WHERE s.user_id=$uid AND s.status='active' AND sp.is_premium=1 LIMIT 1");
    return ($q && $r = mysqli_fetch_assoc($q)) ? $r : null;
}

function ps_membership_badge(): string {
    return '<span style="background:linear-gradient(90deg,#f39c12,#e74c3c);color:#fff;border-radius:10px;padding:1px 8px;font-size:11px;font-weight:700;letter-spacing:.5px">PREMIUM</span>';
}
