<?php

use yii\helpers\Html;

/** @var array $result */

$this->title = 'Попередній перегляд новин';
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php if (empty($result)): ?>
    <p><em>Дані відсутні.</em></p>
<?php else: ?>
    <?php foreach ($result as $dbName => $items): ?>
        <h2><?= Html::encode($dbName) ?></h2>

        <?php if (empty($items)): ?>
            <p><em>Новини не знайдено.</em></p>
        <?php else: ?>
            <ul>
                <?php foreach ($items as $news): ?>
                    <li>
                        <strong><?= Html::encode($news['title'] ?? '') ?></strong><br>
                        <div><?= nl2br(Html::encode($news['text'] ?? '')) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<p>
    <?= Html::a('← Назад', ['index'], ['class' => 'btn btn-secondary']) ?>
</p>
