<?php /** @var array $a  @var string $when  @var string $viewUrl  @var string $bookUrl  @var bool $hasAccount */ ?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
    <tr>
        <td style="font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.25;color:#3B2B29;padding-right:14px;vertical-align:middle;">Appointment cancelled</td>
        <td style="vertical-align:middle;"><?= email_img('hd-cancelled', 38) ?></td>
    </tr>
</table>
<p style="margin:0;">Hi <strong><?= e($a['customer']) ?></strong>,</p>
<p style="margin:8px 0 0;color:#5B4A48;">The appointment below has been cancelled. We hope to see you another time!</p>
<?php require __DIR__ . '/_details.php'; ?>
<?php $btnUrl = $bookUrl ?? ''; $btnLabel = ($hasAccount ?? false) ? 'Book a new appointment' : 'Create an account to book online'; require __DIR__ . '/_button.php'; ?>
<?php if (($viewUrl ?? '') !== ''): ?>
    <p style="margin:12px 0 0;text-align:center;font-size:13.5px;"><a href="<?= e($viewUrl) ?>" style="color:#A85C5E;">View my appointments</a></p>
<?php endif; ?>
