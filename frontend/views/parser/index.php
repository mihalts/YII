<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var array $availableDbs */
/** @var string $selectedDb */
?>

<h2>Експорт новин з бази даних</h2>

<?= Html::a('Export XML', ['parser/export', 'db' => $selectedDb, 'format' => 'xml']) ?>
<?= Html::a('Export CSV', ['parser/export', 'db' => $selectedDb, 'format' => 'csv']) ?>
<?= Html::a('Export TXT', ['parser/export', 'db' => $selectedDb, 'format' => 'txt']) ?>

<?php $form = ActiveForm::begin([
    'method' => 'get',
    'action' => Url::to(['parser/index']),
]); ?>

<div class="form-group">
    <label for="db-select">Оберіть базу даних:</label>
    <select id="db-select" name="db" class="form-control">
        <option value="all" <?= $selectedDb === 'all' ? 'selected' : '' ?>>Всі бази</option>
        <?php foreach ($availableDbs as $db): ?>
            <option value="<?= $db ?>" <?= $selectedDb === $db ? 'selected' : '' ?>>
                <?= strtoupper($db) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <?= Html::submitButton('Показати', ['class' => 'btn btn-primary']) ?>
    <?php if ($selectedDb): ?>
        <?= Html::a('Експортувати в XML', ['parser/export-xml', 'db' => $selectedDb], ['class' => 'btn btn-success']) ?>
    <?php endif; ?>
<?php ActiveForm::end(); ?>
</div>
