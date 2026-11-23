<?php /** @var array $a  @var string $when */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border-top:1px solid #EADAD5;">
    <?php foreach ([
        'Service' => $a['service'],
        'Stylist' => $a['stylist'],
        'When'    => $when . ' – ' . fmt_time($a['end_time']),
        'Price'   => peso($a['price']),
    ] as $label => $value): ?>
        <tr>
            <td style="padding:10px 0;border-bottom:1px solid #EADAD5;color:#76625E;font-size:14px;"><?= e($label) ?></td>
            <td align="right" style="padding:10px 0;border-bottom:1px solid #EADAD5;font-weight:bold;font-size:14px;"><?= e($value) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
