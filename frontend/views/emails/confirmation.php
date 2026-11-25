<?php /** @var array $a  @var string $when  @var bool $rescheduled  @var string $viewUrl  @var string $bookUrl  @var bool $hasAccount */ ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
    <tr>
        <td style="font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.25;color:#3B2B29;padding-right:14px;vertical-align:middle;"><?= ($rescheduled ?? false) ? 'Your appointment was moved' : 'You\'re booked!' ?></td>
        <td style="vertical-align:middle;"><?= email_img(($rescheduled ?? false) ? 'hd-moved' : 'hd-booked', 38) ?></td>
    </tr>
</table>
<p style="margin:0;">Hi <strong><?= e($a['customer']) ?></strong>,</p>
<p style="margin:8px 0 0;color:#5B4A48;">
    <?= ($rescheduled ?? false)
        ? 'Your appointment has been moved to a new time. Here are the updated details:'
        : 'Your appointment is confirmed. We can\'t wait to see you!' ?>
</p>
<?php require __DIR__ . '/_details.php'; ?>
<?php $btnUrl = $viewUrl ?? ''; $btnLabel = 'View my appointment'; require __DIR__ . '/_button.php'; ?>
<p style="margin:18px 0 0;color:#76625E;font-size:13.5px;text-align:center;">
    <?= ($hasAccount ?? false)
        ? 'Need to change or cancel? Open your appointment above, or contact the salon.'
        : 'Need to change or cancel? Please contact the salon.' ?>
</p>
