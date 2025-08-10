<?php
/** @var array $result */
use yii\helpers\Html;

$this->title = 'Перегляд новин';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php if (empty($result)): ?>
  <p>Немає даних для перегляду.</p>
<?php else: ?>
  <?php foreach ($result as $dbName => $newsList): ?>
    <h3><?= Html::encode($dbName) ?></h3>
    <?php if (empty($newsList)): ?>
      <p><em>Порожньо</em></p>
    <?php else: ?>
      <ul>
        <?php foreach ($newsList as $item): ?>
          <li style="margin-bottom:10px;">
            <strong><?= Html::encode($item['title'] ?? '') ?></strong><br>
            <div><?= $item['text'] ?? '' ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <hr>
  <?php endforeach; ?>
<?php endif; ?>

<p><?= Html::a('← Назад', ['parser/index'], ['class' => 'btn btn-default']) ?></p>
