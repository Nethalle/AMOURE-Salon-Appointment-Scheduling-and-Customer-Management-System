<?php
/**
 * Pagination footer. Expects: $pgRoute, $pgQuery (array, without 'p'), $pgPage, $pgPages, $pgTotal, $pgPer, $pgNoun.
 * @var string $pgRoute @var array $pgQuery @var int $pgPage @var int $pgPages @var int $pgTotal @var int $pgPer @var string $pgNoun
 */
$pgFrom = $pgTotal ? ($pgPage - 1) * $pgPer + 1 : 0;
$pgTo   = min($pgTotal, $pgPage * $pgPer);
$pgUrl  = static fn (int $n) => url($pgRoute, $pgQuery + ($n > 1 ? ['p' => $n] : []));
?>
<div class="pager">
    <p>Showing <?= $pgFrom ?>–<?= $pgTo ?> of <?= $pgTotal ?> <?= e($pgNoun) ?></p>
    <?php if ($pgPages > 1): ?>
        <nav aria-label="Pages">
            <a href="<?= e($pgUrl(max(1, $pgPage - 1))) ?>" class="<?= $pgPage <= 1 ? 'off' : '' ?>" aria-label="Previous page"><?= icon('chev-left') ?></a>
            <?php foreach (page_numbers($pgPage, $pgPages) as $n): ?>
                <?php if ($n === '…'): ?>
                    <span class="gap">…</span>
                <?php else: ?>
                    <a href="<?= e($pgUrl($n)) ?>" <?= $n === $pgPage ? 'aria-current="page"' : '' ?>><?= $n ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?= e($pgUrl(min($pgPages, $pgPage + 1))) ?>" class="<?= $pgPage >= $pgPages ? 'off' : '' ?>" aria-label="Next page"><?= icon('chev-right') ?></a>
        </nav>
    <?php endif; ?>
</div>
