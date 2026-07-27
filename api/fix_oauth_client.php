<?php
/**
 * One-time fix: restore client_id to ips_forum (what IPS actually sends).
 * DELETE THIS FILE after running it.
 */
$CONFIG = require __DIR__ . '/config.php';
$c   = $CONFIG['db'];
$dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
$pdo = new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Remove wrong entry
$pdo->prepare('DELETE FROM ucp_oauth_clients WHERE client_id = ?')->execute(['ips.forum']);

// Upsert correct entry — IPS actually sends client_id=ips_forum
$pdo->prepare(
    'INSERT INTO ucp_oauth_clients (client_id, client_secret, redirect_uri, name)
     VALUES (:client_id, :client_secret, :redirect_uri, :name)
     ON DUPLICATE KEY UPDATE
       client_secret = VALUES(client_secret),
       redirect_uri  = VALUES(redirect_uri),
       name          = VALUES(name)'
)->execute([
    'client_id'     => 'ips_forum',
    'client_secret' => '99f653aae41cee6080bae3e56200fcf9617e1da3958dadb7',
    'redirect_uri'  => 'https://forum.blaineside.com/oauth/callback/',
    'name'          => 'BlaineSide Forum',
]);

$rows = $pdo->query('SELECT client_id, redirect_uri FROM ucp_oauth_clients')->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($rows, true) . "</pre><b>Done. Delete this file.</b>";
