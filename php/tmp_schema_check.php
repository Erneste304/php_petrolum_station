<?php
require_once 'config/database.php';
$tables = ['employee','users','partner_share_transaction','share_market','share_price','bulk_product'];
foreach($tables as $t){
    echo "\n--- TABLE: $t ---\n";
    try {
        $r = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC);
        foreach($r as $row) echo $row['Field'].' ('.$row['Type'].")\n";
    } catch(Exception $e){ echo "NOT FOUND\n"; }
}
echo "\n--- ALL TABLES ---\n";
$r=$pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach($r as $t) echo "$t\n";
