<?php /** @var array $a  @var string $when */ ?>
<h1 style="font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:26px;margin:8px 0 12px;">See you soon!</h1>
<p style="margin:0;">Hi <?= e($a['customer']) ?>,</p>
<p style="margin:8px 0 0;">This is a friendly reminder of your upcoming appointment.</p>
<?php require __DIR__ . '/_details.php'; ?>
<p style="margin:0;color:#76625E;font-size:14px;">Can't make it? Please cancel in your account so we can offer the time to someone else.</p>
