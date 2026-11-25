<?php
/** @var string $content */
$phone   = setting('salon_phone');
$address = setting('salon_address');
$site    = function_exists('abs_url') ? abs_url('home') : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light only">
<title>AMOURÉ SALON</title>
</head>
<body style="margin:0;padding:0;background:#F7ECE9;font-family:'Helvetica Neue',Arial,Helvetica,sans-serif;color:#3B2B29;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7ECE9;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#FFFFFF;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px rgba(120,70,66,0.10);">
                <!-- Header band -->
                <tr>
                    <td align="center" style="background:#A85C5E;background-image:linear-gradient(160deg,#B66D70 0%,#A15A5D 55%,#8F4A50 100%);padding:26px 24px 22px;">
                        <?= email_img('logo', 210, 'AMOURÉ SALON', 'margin:0 auto;') ?>
                    </td>
                </tr>
                <!-- Body -->
                <tr>
                    <td style="padding:30px 36px 32px;font-size:15px;line-height:1.6;">
                        <?= $content ?>
                    </td>
                </tr>
                <!-- Salon info -->
                <?php if ($phone !== '' || $address !== ''): ?>
                <tr>
                    <td style="background:#FDF7F5;border-top:1px solid #F0E2DE;padding:18px 36px;font-size:13px;color:#76625E;line-height:1.6;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <?php if ($address !== ''): ?>
                                <tr><td style="padding:3px 10px 3px 0;vertical-align:middle;"><?= email_img('ic-pin', 16) ?></td><td style="font-size:13px;color:#76625E;"><?= e($address) ?></td></tr>
                            <?php endif; ?>
                            <?php if ($phone !== ''): ?>
                                <tr><td style="padding:3px 10px 3px 0;vertical-align:middle;"><?= email_img('ic-phone', 16) ?></td><td style="font-size:13px;color:#76625E;"><?= e($phone) ?></td></tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            <p style="font-size:12px;color:#9A8582;margin:18px 0 0;line-height:1.6;">
                This is an automatic message from AMOURÉ SALON's booking system.
                <?php if ($site !== ''): ?><br><a href="<?= e($site) ?>" style="color:#A85C5E;text-decoration:none;">Visit our website</a><?php endif; ?>
            </p>
        </td>
    </tr>
</table>
</body>
</html>
