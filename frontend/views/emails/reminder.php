<?php /** @var array $a  @var string $when  @var string $viewUrl  @var bool $hasAccount */ ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
    <tr>
        <td style="font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.25;color:#3B2B29;padding-right:14px;vertical-align:middle;">See you soon!</td>
        <td style="vertical-align:middle;"><?= email_img('hd-reminder', 38) ?></td>
    </tr>
</table>
<p style="margin:0;">Hi <strong><?= e($a['customer']) ?></strong>,</p>
<p style="margin:8px 0 0;color:#5B4A48;">A friendly reminder of your upcoming appointment on <strong><?= e($when) ?></strong>.</p>
<?php require __DIR__ . '/_details.php'; ?>
<?php $btnUrl = $viewUrl ?? ''; $btnLabel = 'View my appointment'; require __DIR__ . '/_button.php'; ?>
<p style="margin:18px 0 0;color:#76625E;font-size:13.5px;text-align:center;">
    Can't make it? <?= ($hasAccount ?? false) ? 'Please cancel from your account' : 'Please let the salon know' ?> so we can offer the time to someone else.
</p>
