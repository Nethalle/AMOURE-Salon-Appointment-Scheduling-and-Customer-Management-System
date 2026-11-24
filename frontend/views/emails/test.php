<?php /** @var string $name */ ?>
<h1 style="margin:0 0 12px;font-family:Georgia,serif;font-size:24px;color:#3A2A2A;">Your emails are working!</h1>
<p style="margin:0 0 12px;">Hi <?= e($name) ?>,</p>
<p style="margin:0 0 12px;">This is a test email from <strong>AMOURÉ SALON</strong>. If you can read this, booking confirmations, cancellations and reminders will reach your customers too.</p>
<p style="margin:0;color:#8A7471;font-size:13px;">Sent on <?= e(date('M j, Y g:i A')) ?></p>
