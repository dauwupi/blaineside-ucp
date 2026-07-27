<?php
/**
 * One-time fix: ensure ucp_oauth_clients has the correct IPS forum entry.
 * DELETE THIS FILE after running it.
 */
$CONFIG = require __DIR__ . '/config.php';
$c   = $CONFIG['db'];
$dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
$pdo = new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$correct = [
    'client_id'     => 'ips.forum',
    'client_secret' => '99f653aae41cee6080bae3e56200fcf9617e1da3958dadb7',
    'redirect_uri'  => 'https://forum.blaineside.com/oauth/callback/',
    'name'          => 'BlaineSide Forum',
];

// Show current state
$rows = $pdo->query('SELECT client_id, redirect_uri, name FROM ucp_oauth_clients')->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>Current rows:\n" . print_r($rows, true) . "</pre>";

// Remove any old ips_forum entry
$pdo->prepare('DELETE FROM ucp_oauth_clients WHERE client_id = ?')->execute(['ips_forum']);

// Upsert correct entry
$pdo->prepare(
    'INSERT INTO ucp_oauth_clients (client_id, client_secret, redirect_uri, name)
     VALUES (:client_id, :client_secret, :redirect_uri, :name)
     ON DUPLICATE KEY UPDATE
       client_secret = VALUES(client_secret),
       redirect_uri  = VALUES(redirect_uri),
       name          = VALUES(name)'
)->execute($correct);

// Show new state
$rows = $pdo->query('SELECT client_id, redirect_uri, name FROM ucp_oauth_clients')->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>After fix:\n" . print_r($rows, true) . "</pre>";
echo "<b>Done. Delete this file now.</b>";
