<?php
/**
 * One-time fix: update client_secret to match what IPS actually sends.
 * DELETE THIS FILE after running it.
 */
$CONFIG = require __DIR__ . '/config.php';
$c   = $CONFIG['db'];
$pdo = new PDO(
    "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}",
    $c['user'], $c['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->prepare(
    'UPDATE ucp_oauth_clients SET client_secret = ? WHERE client_id = ?'
)->execute([
    '99f653aae41cee6080bae3e58200fdf9617e1da3958dadb76052b50b5f0878ed',
    'ips_forum',
]);

$row = $pdo->query('SELECT client_id, client_secret FROM ucp_oauth_clients')->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($row, true) . "</pre><b>Done. Delete this file.</b>";
