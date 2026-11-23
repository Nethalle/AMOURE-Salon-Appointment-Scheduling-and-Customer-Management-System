<?php /** @var array $a  @var string $when */ ?>
<h1 style="font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:26px;margin:8px 0 12px;">Appointment cancelled</h1>
<p style="margin:0;">Hi <?= e($a['customer']) ?>,</p>
<p style="margin:8px 0 0;">Your appointment below has been cancelled. We hope to see you another time.</p>
<?php require __DIR__ . '/_details.php'; ?>
<p style="margin:0;color:#76625E;font-size:14px;">Want a new time? Log in to your account to book again.</p>
