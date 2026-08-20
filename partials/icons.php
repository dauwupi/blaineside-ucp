<?php
/**
 * The UCP's icon set.
 *
 * One filled glyph per area, all drawn on the same 24 grid, all rendered
 * with fill-rule="evenodd" so the detail inside a glyph — the ruled lines on
 * a document, the tick on a shield — cuts out of the fill instead of merging
 * into it and vanishing at 21px.
 *
 * Filled rather than stroked on purpose: a 2px outline at this size loses
 * its detail, and a row of outlined glyphs reads as a row of identical
 * grey rings. That is what the page headers looked like before.
 *
 * Add a page: add a key here, then set $PAGE_ICON to it. Nothing else.
 */

const UCP_ICONS = [
  'dashboard'    => '<path d="M3 3h8v7H3Zm10 0h8v11h-8ZM3 12h8v9H3Zm10 4h8v5h-8Z"/>',
  'application'  => '<path d="M6 2h8.2L20 7.8V22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7.6 1.9v4.4H18ZM8 11.4h8V13H8Zm0 3.6h5.6v1.6H8Z"/>',
  'app-panel'    => '<path d="M9.4 2h5.2a1 1 0 0 1 1 1v.6H18a1 1 0 0 1 1 1V21a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.6a1 1 0 0 1 1-1h2.4V3a1 1 0 0 1 1-1Zm.6 2.6v1.2h4V4.6ZM11 17.9l-3-3L9.2 13.7 11 15.5l4-4 1.2 1.2Z"/>',
  'questions'    => '<path d="M4 2.6h16a1.4 1.4 0 0 1 1.4 1.4v16a1.4 1.4 0 0 1-1.4 1.4H4a1.4 1.4 0 0 1-1.4-1.4V4A1.4 1.4 0 0 1 4 2.6Zm7.1 13.9h2v2h-2Zm.1-1.3h1.8c0-2.4 2.5-2.5 2.5-4.9a3.8 3.8 0 0 0-7.5-.5l1.9.4a1.9 1.9 0 1 1 3.7.4c0 1.6-2.4 1.8-2.4 4.6Z"/>',
  'templates'    => '<path d="M8.6 1.8h6L19 6.2V17H8.6A1.4 1.4 0 0 1 7.2 15.6V3.2a1.4 1.4 0 0 1 1.4-1.4Zm5.4 1.6v3.4h3.4ZM5 5.4v13.8h11.4v1.4a1.4 1.4 0 0 1-1.4 1.4H5a1.4 1.4 0 0 1-1.4-1.4V6.8A1.4 1.4 0 0 1 5 5.4Z"/>',
  'appeals'      => '<path d="M13.7 2.3 21 9.6l-2.5 2.5-7.3-7.3ZM10 6.4l7.3 7.3-2.5 2.5-7.3-7.3ZM8.9 11.6l3 3-6.3 6.3-3-3ZM12.6 19.9h9.1V22h-9.1Z"/>',
  'reports'      => '<path d="M4.4 2h2.3v20H4.4Zm4 1.4h11.1a.8.8 0 0 1 .63 1.29L17.4 8.4l2.73 3.71a.8.8 0 0 1-.63 1.29H8.4Z"/>',
  'transfers'    => '<path d="M8.6 2.6 10.3 4.3 7.5 7.1H21v2.4H7.5l2.8 2.8-1.7 1.7L2.9 8.3ZM15.4 21.4l-1.7-1.7 2.8-2.8H3v-2.4h13.5l-2.8-2.8 1.7-1.7 5.7 5.7Z"/>',
  'refunds'      => '<path d="M12.6 3.4a8.6 8.6 0 1 1-8.4 10.3l2.3-.5A6.2 6.2 0 1 0 12.6 5.8a6.2 6.2 0 0 0-4.5 1.9h2.6v2.4H4V5.5l2.4-.4v1a8.6 8.6 0 0 1 6.2-2.7Zm.6 4.4v1.1a2.9 2.9 0 0 1 1.9.9l-1 1.3a2 2 0 0 0-1.4-.6c-.6 0-1 .3-1 .7s.5.7 1.5 1c1.4.5 2 1.2 2 2.3a2.4 2.4 0 0 1-2 2.4v1.1h-1.4v-1.1a3.4 3.4 0 0 1-2.3-1.1l1-1.2a2.4 2.4 0 0 0 1.7.8c.7 0 1.1-.3 1.1-.8s-.4-.7-1.4-1.1c-1.3-.4-2.1-1-2.1-2.2a2.3 2.3 0 0 1 2-2.3V7.8Z"/>',
  'store'        => '<path d="M8.2 6.4a3.8 3.8 0 0 1 7.6 0h2.6a1 1 0 0 1 1 .95l.6 13.6a1 1 0 0 1-1 1.05H5a1 1 0 0 1-1-1.05l.6-13.6a1 1 0 0 1 1-.95Zm2.2 0h3.2a1.6 1.6 0 0 0-3.2 0ZM8.4 9.8a1.3 1.3 0 1 0 1.3 1.3 1.3 1.3 0 0 0-1.3-1.3Zm7.2 0a1.3 1.3 0 1 0 1.3 1.3 1.3 1.3 0 0 0-1.3-1.3Z"/>',
  'management'   => '<path d="M12 1.8 3.6 5v6.1c0 5.2 3.6 9.4 8.4 11.1 4.8-1.7 8.4-5.9 8.4-11.1V5Zm-.9 13.9-3.3-3.3 1.5-1.5 1.8 1.8 4.5-4.5 1.5 1.5Z"/>',
  'announcements'=> '<path d="M18.6 3.2a1.2 1.2 0 0 1 1.9 1v15.6a1.2 1.2 0 0 1-1.9 1L12 15.9v3.7a2.6 2.6 0 0 1-5.2 0v-3.7H5.2A3.2 3.2 0 0 1 2 12.7v-2.4a3.2 3.2 0 0 1 3.2-3.2h6.4ZM8.8 15.9v3.7a.6.6 0 0 0 1.2 0v-3.7Z"/>',
  'bulletin'     => '<path d="M5.4 2h13.2a1.4 1.4 0 0 1 1.4 1.4v13.2a1.4 1.4 0 0 1-1.4 1.4h-4.1L12 22l-2.5-4H5.4A1.4 1.4 0 0 1 4 16.6V3.4A1.4 1.4 0 0 1 5.4 2Zm2 4v1.9h9.2V6Zm0 4v1.9h5.9V10Z"/>',
  'search'       => '<path d="M10.4 2.2a8.2 8.2 0 0 1 6.44 13.28l4.84 4.84-1.96 1.96-4.84-4.84A8.2 8.2 0 1 1 10.4 2.2Zm0 2.8a5.4 5.4 0 1 0 5.4 5.4 5.4 5.4 0 0 0-5.4-5.4Z"/>',
  'groups'       => '<path d="M9.2 3.4a3.9 3.9 0 1 1 0 7.8 3.9 3.9 0 0 1 0-7.8ZM2.4 20.6a6.8 6.8 0 0 1 13.6 0v1H2.4Zm14.9-14a3.2 3.2 0 1 1 0 6.4 3.2 3.2 0 0 1 0-6.4Zm.6 7.9a5.4 5.4 0 0 1 3.7 5.1v1.9h-3.5v-1a9.1 9.1 0 0 0-1.9-5.6 5.4 5.4 0 0 1 1.7-.4Z"/>',
  'notifications'=> '<path d="M12 1.8a6.4 6.4 0 0 1 6.4 6.4v3.9l1.9 3.3a1 1 0 0 1-.87 1.5H4.57a1 1 0 0 1-.87-1.5l1.9-3.3V8.2A6.4 6.4 0 0 1 12 1.8Zm-3 17.1h6a3 3 0 0 1-6 0Z"/>',
  'profile'      => '<path d="M3.4 4.4h17.2a1.6 1.6 0 0 1 1.6 1.6v12a1.6 1.6 0 0 1-1.6 1.6H3.4A1.6 1.6 0 0 1 1.8 18V6a1.6 1.6 0 0 1 1.6-1.6ZM8.4 7.4a2.6 2.6 0 1 0 2.6 2.6 2.6 2.6 0 0 0-2.6-2.6ZM4.2 16.9h8.4v-.5a4.2 4.2 0 0 0-8.4 0Zm10.2-8.1h5.4v1.7h-5.4Zm0 3.6h5.4v1.7h-5.4Z"/>',
  'characters'   => '<path d="M12 2.6a4.6 4.6 0 1 1 0 9.2 4.6 4.6 0 0 1 0-9.2Zm7.4 18.8H4.6v-1.2a7.4 7.4 0 0 1 7.4-7.4 7.4 7.4 0 0 1 7.4 7.4Z"/>',
];

/** One icon, ready to drop in. An unknown key draws nothing rather than a box. */
function ucp_icon(string $key): string
{
    $d = UCP_ICONS[$key] ?? '';
    if ($d === '') return '';
    return '<svg viewBox="0 0 24 24" fill-rule="evenodd" aria-hidden="true">' . $d . '</svg>';
}
