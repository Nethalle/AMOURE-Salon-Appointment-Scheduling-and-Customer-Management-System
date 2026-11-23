<?php /** @var array $a  @var string $when  @var bool $rescheduled */ ?>
<h1 style="font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:26px;margin:8px 0 12px;">
    <?= $rescheduled ? 'Your appointment was moved' : 'You\'re booked!' ?>
</h1>
<p style="margin:0;">Hi <?= e($a['customer']) ?>,</p>
<p style="margin:8px 0 0;">
    <?= $rescheduled
        ? 'Your appointment has been moved to a new time. Here are the updated details:'
        : 'Your appointment is confirmed. We look forward to seeing you!' ?>
</p>
<?php require __DIR__ . '/_details.php'; ?>
<p style="margin:0;color:#76625E;font-size:14px;">Need to change or cancel? Log in to your account or contact the salon.</p>
