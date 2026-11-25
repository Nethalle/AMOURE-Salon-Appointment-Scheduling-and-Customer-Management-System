<?php /** @var string $name */ ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
    <tr>
        <td style="font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.25;color:#3B2B29;padding-right:14px;vertical-align:middle;">Your emails are working!</td>
        <td style="vertical-align:middle;"><?= email_img('hd-check', 38) ?></td>
    </tr>
</table>
<p style="margin:0;">Hi <strong><?= e($name) ?></strong>,</p>
<p style="margin:8px 0 0;color:#5B4A48;">This is a test email from <strong>AMOURÉ SALON</strong>. If you can read this, booking confirmations, cancellations and reminders will reach your customers too.</p>
<?php $btnUrl = abs_url('admin/settings', ['tab' => 'notifications']); $btnLabel = 'Back to Settings'; require __DIR__ . '/_button.php'; ?>
<p style="margin:14px 0 0;color:#9A8582;font-size:13px;text-align:center;">Sent on <?= e(date('M j, Y g:i A')) ?></p>
