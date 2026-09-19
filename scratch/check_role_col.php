<?php
$c = new mysqli('localhost', 'root', 'bismillah', 'simpelkesrsig');
$r = $c->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch_assoc();
echo "users.role type: " . $r['Type'] . "\n";
