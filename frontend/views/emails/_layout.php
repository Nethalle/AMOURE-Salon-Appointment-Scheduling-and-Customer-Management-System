<?php /** @var string $content */ ?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#FBF4F1;font-family:Arial,Helvetica,sans-serif;color:#3B2B29;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF4F1;padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#FFFFFF;border:1px solid #EADAD5;border-radius:14px;">
                <tr>
                    <td align="center" style="padding:28px 24px 8px;">
                        <div style="font-family:Georgia,'Times New Roman',serif;font-size:26px;letter-spacing:4px;color:#3B2B29;">AMOURÉ</div>
                        <div style="font-size:10px;letter-spacing:6px;color:#76625E;margin-top:4px;">SALON</div>
                        <div style="width:80px;height:1px;background:#C9A66B;margin:16px auto 0;"></div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 32px 32px;font-size:15px;line-height:1.6;">
                        <?= $content ?>
                    </td>
                </tr>
            </table>
            <p style="font-size:12px;color:#76625E;margin:16px 0 0;">This is an automatic message from AMOURÉ SALON's booking system.</p>
        </td>
    </tr>
</table>
</body>
</html>
