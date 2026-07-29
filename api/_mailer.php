<?php
/**
 * Sends mail through the OVH mailbox via SMTP using PHPMailer.
 * Requires the PHPMailer files in api/lib/PHPMailer/ (see README).
 */

require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * @return array{ok:bool,error?:string}
 */
function send_mail(string $toEmail, string $toName, string $subject, string $html, string $text = ''): array {
    global $CONFIG;
    $s = $CONFIG['smtp'];

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $s['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $s['user'];
        $mail->Password   = $s['pass'];
        $mail->SMTPSecure = $s['secure'];   // 'ssl' or 'tls'
        $mail->Port       = (int)$s['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($s['from_email'], $s['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text !== '' ? $text : strip_tags($html);

        $mail->send();
        return ['ok' => true];
    } catch (MailException $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}

/** Builds the branded verification email HTML. */
function verification_email_html(string $username, string $link): string {
    $u = htmlspecialchars($username, ENT_QUOTES);
    $l = htmlspecialchars($link, ENT_QUOTES);
    return <<<HTML
<div style="background:#100f0e;padding:32px 0;font-family:Inter,Arial,sans-serif">
  <div style="max-width:520px;margin:0 auto;background:#1a1815;border:1px solid #26221e;border-radius:14px;overflow:hidden">
    <div style="padding:22px 28px;border-bottom:1px solid #26221e">
      <span style="font-family:Oswald,Arial,sans-serif;font-weight:700;font-size:22px;letter-spacing:2px;color:#f1efe9">BLAINE<span style="color:#e2b65c">SIDE</span></span>
    </div>
    <div style="padding:28px">
      <h1 style="margin:0 0 12px;font-size:20px;color:#f1efe9">Confirm your email</h1>
      <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#c9bea9">
        Hi <b style="color:#f1efe9">{$u}</b>, thanks for creating a BlaineSide UCP account.
        Click the button below to verify your email and activate your account.
      </p>
      <a href="{$l}" style="display:inline-block;background:linear-gradient(145deg,#e2b65c,#d4923a);color:#1a1206;font-weight:700;font-size:14px;text-decoration:none;padding:13px 22px;border-radius:10px">Verify my email</a>
      <p style="margin:20px 0 0;font-size:12px;line-height:1.6;color:#8a7f70">
        Or paste this link into your browser:<br>
        <span style="color:#e2b65c;word-break:break-all">{$l}</span>
      </p>
      <p style="margin:20px 0 0;font-size:12px;color:#655e51">
        If you didn't create this account, you can ignore this email.
      </p>
    </div>
  </div>
</div>
HTML;
}

/** Builds the branded password-reset email HTML. */
function password_reset_email_html(string $username, string $link): string {
    $u = htmlspecialchars($username, ENT_QUOTES);
    $l = htmlspecialchars($link, ENT_QUOTES);
    return <<<HTML
<div style="background:#100f0e;padding:32px 0;font-family:Inter,Arial,sans-serif">
  <div style="max-width:520px;margin:0 auto;background:#1a1815;border:1px solid #26221e;border-radius:14px;overflow:hidden">
    <div style="padding:22px 28px;border-bottom:1px solid #26221e">
      <span style="font-family:Oswald,Arial,sans-serif;font-weight:700;font-size:22px;letter-spacing:2px;color:#f1efe9">BLAINE<span style="color:#e2b65c">SIDE</span></span>
    </div>
    <div style="padding:28px">
      <h1 style="margin:0 0 12px;font-size:20px;color:#f1efe9">Reset your password</h1>
      <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#c9bea9">
        Hi <b style="color:#f1efe9">{$u}</b>, we received a request to reset the password on your
        BlaineSide UCP. Click below to choose a new one. This link is valid for
        <b style="color:#f1efe9">30 minutes</b> and can only be used once.
      </p>
      <a href="{$l}" style="display:inline-block;background:linear-gradient(145deg,#e2b65c,#d4923a);color:#1a1206;font-weight:700;font-size:14px;text-decoration:none;padding:13px 22px;border-radius:10px">Choose a new password</a>
      <p style="margin:20px 0 0;font-size:12px;line-height:1.6;color:#8a7f70">
        Or paste this link into your browser:<br>
        <span style="color:#e2b65c;word-break:break-all">{$l}</span>
      </p>
      <p style="margin:20px 0 0;font-size:12px;color:#655e51">
        If you didn't ask for this, you can ignore this email — your password stays as it is.
      </p>
    </div>
  </div>
</div>
HTML;
}
