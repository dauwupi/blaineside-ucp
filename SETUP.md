# Block direct web access to config and internal include files.
<FilesMatch "^(config\.php|config\.example\.php|_.*\.php)$">
  Require all denied
</FilesMatch>

# Never serve the raw config even if rules change.
<Files "config.php">
  Require all denied
</Files>
