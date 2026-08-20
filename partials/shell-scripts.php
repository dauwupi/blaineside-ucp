<?php
/**
 * The UCP's shared script tag, and the only place its cache token lives.
 *
 * It is an include rather than something shell-top.php prints, because
 * WHERE it runs matters: every page has its own inline <script> right after
 * this one, and those call UCP.* while the document is still parsing. A
 * deferred tag in the head would run after them and break every page, so
 * the tag has to stay exactly where it was — at the foot of the body,
 * synchronous, immediately before the page's own script.
 *
 * Pages therefore carry one line:
 *
 *     <?php require __DIR__ . '/../partials/shell-scripts.php'; ?>
 *
 * and a new version of assets/js/ucp.js is a one-line edit here.
 */
if (!defined('UCP_JS_VERSION')) define('UCP_JS_VERSION', '3.1.1');
?>
<script src="/assets/js/ucp.js?v=<?= UCP_JS_VERSION ?>"></script>
