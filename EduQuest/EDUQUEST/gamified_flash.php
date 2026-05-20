<?php
/**
 * gamified_flash.php — PHP-side popup flash helper for EduQuest.
 *
 * Stores a popup config in $_SESSION and renders it as a one-shot
 * inline <script> on the next page load (Post/Redirect/Get pattern).
 *
 * USAGE — in form-handling PHP (before redirect):
 *   require_once __DIR__ . '/gamified_flash.php';
 *   set_popup_flash('success', 'Activity Published!',
 *       'Your students can now see this activity.', '✅', true, 3000);
 *   header('Location: dashboard.php'); exit;
 *
 * USAGE — in any PHP template (once per page, in <body>):
 *   require_once __DIR__ . '/../gamified_flash.php';
 *   echo render_popup_flash();
 *
 * The popup CSS and JS must already be loaded on the page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Queue a popup flash message to be shown on the next page load.
 *
 * @param string $type       One of: success|levelup|badge|streak|reminder|welcome|encouragement
 * @param string $title      Short heading shown in bold inside the popup.
 * @param string $message    Longer descriptive sentence.
 * @param string $icon       Emoji icon (e.g. '✅', '🚀').
 * @param bool   $confetti   Whether to launch confetti particles.
 * @param int    $autoClose  Auto-dismiss after this many milliseconds (0 = manual only).
 * @param string $buttonText Label for the dismiss button.
 */
function set_popup_flash(
    string $type,
    string $title,
    string $message,
    string $icon,
    bool   $confetti   = false,
    int    $autoClose  = 0,
    string $buttonText = 'Awesome!'
): void {
    $_SESSION['popup_flash'] = json_encode([
        'type'       => $type,
        'title'      => $title,
        'message'    => $message,
        'icon'       => $icon,
        'confetti'   => $confetti,
        'autoClose'  => $autoClose > 0 ? $autoClose : null,
        'buttonText' => $buttonText,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Emit the one-shot inline <script> for the queued flash popup, then
 * clear it from the session.  Safe to call even when no flash is set.
 *
 * @return string  HTML <script> block, or empty string.
 */
function render_popup_flash(): string {
    if (empty($_SESSION['popup_flash'])) {
        return '';
    }

    $raw = $_SESSION['popup_flash'];
    unset($_SESSION['popup_flash']);

    /*
     * Re-encode with JSON_HEX_TAG so that a string value containing
     * "</script>" cannot break out of the script context.
     */
    $config = json_decode($raw, true);
    if (!is_array($config)) {
        return '';
    }

    $safe = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

    return '<script>' .
               'document.addEventListener("DOMContentLoaded",function(){' .
                   'if(typeof showGamePopup==="function"){showGamePopup(' . $safe . ');}' .
               '});' .
           '</script>';
}
