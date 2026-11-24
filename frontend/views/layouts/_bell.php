<?php /** @var array $notif */ $back = $_SERVER['QUERY_STRING'] ?? ''; ?>
<details class="notif" data-notif data-read-url="<?= e(url('notifications/read')) ?>" data-csrf="<?= e(csrf_token()) ?>">
    <summary class="app-bell" aria-label="Notifications<?= $notif['unread'] ? ', ' . $notif['unread'] . ' unread' : '' ?>">
        <?= icon('bell') ?>
        <?php if ($notif['unread'] > 0): ?><span class="app-bell-count"><?= $notif['unread'] > 9 ? '9+' : $notif['unread'] ?></span><?php endif; ?>
    </summary>
    <div class="notif-panel">
        <div class="notif-head">
            <strong>Notifications</strong>
            <?php if ($notif['unread'] > 0): ?>
                <form method="post" action="<?= e(url('notifications/read')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="back" value="<?= e($back) ?>">
                    <button type="submit">Mark all as read</button>
                </form>
            <?php endif; ?>
        </div>
        <?php if (!$notif['items']): ?>
            <p class="notif-empty"><?= icon('bell') ?><span>You're all caught up.</span></p>
        <?php else: ?>
            <ul class="notif-list">
                <?php foreach ($notif['items'] as $n): ?>
                    <li class="<?= $n['unread'] ? 'is-unread' : '' ?>">
                        <a href="<?= e($n['link']) ?>">
                            <span class="notif-icon notif-<?= e($n['icon']) ?>"><?= icon($n['icon']) ?></span>
                            <span class="notif-body">
                                <span class="notif-text"><?= $n['text'] /* built with e() in NotificationFeed */ ?></span>
                                <small><?= e(NotificationFeed::ago($n['time'])) ?></small>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</details>
