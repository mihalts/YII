<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var array $databases */

$this->title = 'Експорт новин із баз даних';
?>

<h1><?= Html::encode($this->title) ?></h1>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger">
        <?= Yii::$app->session->getFlash('error') ?>
    </div>
<?php endif; ?>

<?php if (empty($databases)): ?>
    <p><em>Бази даних не знайдено.</em></p>
<?php else: ?>
    <?php $form = ActiveForm::begin([
        'action' => ['parser/export'],
        'method' => 'post',
    ]); ?>

    <div class="form-group">
        <label>Оберіть бази даних:</label><br>
        <?php foreach ($databases as $file): ?>
            <label>
                <?= Html::checkbox('selectedDatabases[]', false, ['value' => $file]) ?>
                <?= Html::encode($file) ?>
            </label><br>
        <?php endforeach; ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Перегляд', ['class' => 'btn btn-info', 'name' => 'action', 'value' => 'view']) ?>
        <?= Html::submitButton('Експортувати у CSV', ['class' => 'btn btn-success', 'name' => 'action', 'value' => 'csv']) ?>
        <?= Html::submitButton('Експортувати у TXT', ['class' => 'btn btn-primary', 'name' => 'action', 'value' => 'txt']) ?>
        <?= Html::submitButton('Export to XML', ['name' => 'action', 'value' => 'xml', 'class' => 'btn btn-outline-secondary']) ?>
        <?= Html::submitButton('Export to XML (merge)', ['name' => 'action', 'value' => 'xml-merge', 'class' => 'btn btn-outline-warning']) ?>

    </div>

    <?php ActiveForm::end(); ?>
<?php endif; ?>
