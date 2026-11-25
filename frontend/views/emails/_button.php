<?php
/** Big rose button that works in every email app. @var string $btnUrl  @var string $btnLabel */
if (($btnUrl ?? '') === '') { return; }
?>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px auto 8px;">
    <tr>
        <td align="center" style="border-radius:12px;background:#A85C5E;">
            <a href="<?= e($btnUrl) ?>" target="_blank"
               style="display:inline-block;padding:15px 34px;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;border-radius:12px;letter-spacing:0.3px;">
                <?= e($btnLabel) ?> &rarr;
            </a>
        </td>
    </tr>
</table>
