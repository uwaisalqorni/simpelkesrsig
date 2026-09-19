<?php
$c = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');
$res = $c->query("SHOW TABLES");
$tables = [];
while ($r = $res->fetch_row()) {
    $tables[] = $r[0];
}
echo "Tables in simpelkesrsig:\n";
foreach ($tables as $t) {
    $res2 = $c->query("SHOW COLUMNS FROM `$t`");
    $cols = [];
    while ($c2 = $res2->fetch_assoc()) $cols[] = $c2['Field'];
    echo "- $t (" . implode(', ', array_slice($cols, 0, 6)) . (count($cols) > 6 ? '...' : '') . ")\n";
}
