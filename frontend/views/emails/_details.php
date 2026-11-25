<?php /** @var array $a  @var string $when */
$statusStyle = [
    'pending'   => ['Waiting for confirmation', '#FFF1DC', '#A3661B'],
    'confirmed' => ['Confirmed', '#E4F4EA', '#2F7A4C'],
    'completed' => ['Completed', '#E5EEFB', '#3460A0'],
    'cancelled' => ['Cancelled', '#FBE6E6', '#B33A3A'],
    'no_show'   => ['No-show', '#EFEAE9', '#6F5F5C'],
][$a['status']] ?? null;
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 4px;background:#FDF7F5;border:1px solid #F0E2DE;border-radius:14px;">
    <tr>
        <td style="padding:16px 20px;border-bottom:1px solid #F0E2DE;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="font-size:11px;letter-spacing:1.5px;color:#9A8582;text-transform:uppercase;">Booking number</td>
                    <?php if ($statusStyle): ?>
                        <td align="right" rowspan="2">
                            <span style="display:inline-block;padding:5px 12px;border-radius:999px;background:<?= $statusStyle[1] ?>;color:<?= $statusStyle[2] ?>;font-size:12px;font-weight:bold;"><?= e($statusStyle[0]) ?></span>
                        </td>
                    <?php endif; ?>
                </tr>
                <tr>
                    <td style="font-family:'Courier New',Courier,monospace;font-size:20px;font-weight:bold;color:#8F4A50;letter-spacing:1px;padding-top:2px;"><?= e(appt_ref($a['id'])) ?></td>
                </tr>
            </table>
        </td>
    </tr>
    <?php foreach ([
        ['ic-scissors', 'Service', $a['service']],
        ['ic-user', 'Stylist', $a['stylist']],
        ['ic-calendar', 'Date', fmt_date($a['appt_date'])],
        ['ic-clock', 'Time', fmt_time($a['start_time']) . ' – ' . fmt_time($a['end_time'])],
        ['ic-card', 'Price', peso($a['price'])],
    ] as $i => [$ic, $label, $value]): ?>
        <tr>
            <td style="padding:12px 20px;<?= $i < 4 ? 'border-bottom:1px solid #F0E2DE;' : '' ?>">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="30" style="vertical-align:middle;"><?= email_img($ic, 20, '') ?></td>
                        <td style="font-size:14px;color:#76625E;vertical-align:middle;"><?= e($label) ?></td>
                        <td align="right" style="font-size:14px;font-weight:bold;color:#3B2B29;"><?= e($value) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
