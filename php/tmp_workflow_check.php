<?php
require_once 'config/database.php';
$tables = ['payroll_submissions', 'bulk_fuel_order', 'fuel_request', 'share_config', 'share_requests', 'partner_shares'];
foreach($tables as $t){
    echo "\n--- TABLE: $t ---\n";
    try {
        $r = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
        foreach($r as $row) echo $row['Field'].' ('.$row['Type'].")\n";
    } catch(Exception $e){ echo "NOT FOUND\n"; }
}
