<?php /** @var string $name  @var string $code  @var string $purpose  @var int $minutes */
$isReset = $purpose === 'reset';
?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
    <tr>
        <td style="font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.25;color:#3B2B29;padding-right:14px;vertical-align:middle;"><?= $isReset ? 'Reset your password' : 'Your sign-in code' ?></td>
        <td style="vertical-align:middle;"><?= email_img('hd-lock', 38) ?></td>
    </tr>
</table>
<p style="margin:0;">Hi <strong><?= e($name) ?></strong>,</p>
<p style="margin:8px 0 0;color:#5B4A48;">
    <?= $isReset
        ? 'We got a request to reset your AMOURÉ SALON password. Enter this code on the reset page:'
        : 'Enter this code to finish signing in to AMOURÉ SALON:' ?>
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 6px;">
    <tr>
        <td align="center" style="background:#FDF7F5;border:1px solid #F0E2DE;border-radius:14px;padding:22px 12px;">
            <div style="font-size:11px;letter-spacing:1.5px;color:#9A8582;text-transform:uppercase;margin-bottom:8px;"><?= $isReset ? 'Reset code' : 'Sign-in code' ?></div>
            <div style="font-family:'Courier New',Courier,monospace;font-size:38px;font-weight:bold;letter-spacing:10px;color:#8F4A50;"><?= e($code) ?></div>
            <div style="font-size:13px;color:#76625E;margin-top:10px;">Expires in <?= (int) $minutes ?> minutes · works once</div>
        </td>
    </tr>
</table>
<p style="margin:18px 0 0;color:#76625E;font-size:13.5px;text-align:center;">
    Didn't ask for this? You can ignore this email<?= $isReset ? '. Your password stays the same.' : ', but someone knows your password, so please change it.' ?>
</p>
<p style="margin:10px 0 0;color:#9A8582;font-size:12.5px;text-align:center;">AMOURÉ SALON will never ask you for this code by phone or chat.</p>
