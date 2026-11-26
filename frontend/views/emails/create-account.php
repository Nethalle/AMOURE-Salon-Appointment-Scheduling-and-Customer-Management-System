<?php /** @var string $name  @var string $btnUrl  @var string $btnLabel */ ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
    <tr>
        <td style="font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.25;color:#3B2B29;padding-right:14px;vertical-align:middle;">Create your account</td>
        <td style="vertical-align:middle;"><?= email_img('hd-lock', 38) ?></td>
    </tr>
</table>
<p style="margin:0;">Hi <strong><?= e($name) ?></strong>,</p>
<p style="margin:8px 0 0;color:#5B4A48;">
    You asked to reset your password, but you don't have an online account yet.
    The salon saved you as a walk-in customer, so there's no password to reset.
</p>
<p style="margin:10px 0 0;color:#5B4A48;">
    Create your account with <strong>this same email</strong>. Your past bookings will show up
    automatically, and you can book online and get reminders.
</p>
<?php require __DIR__ . '/_button.php'; ?>
<?php if (($btnUrl ?? '') === ''): ?>
<p style="margin:18px 0 0;color:#5B4A48;text-align:center;">Open our website and click <strong>Create an account</strong>.</p>
<?php endif; ?>
<p style="margin:18px 0 0;color:#76625E;font-size:13.5px;text-align:center;">Didn't ask for this? You can ignore this email.</p>
