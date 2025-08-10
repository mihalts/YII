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

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success">
        <?= Yii::$app->session->getFlash('success') ?>
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
            <div class="d-flex align-items-center mb-2">
                <label style="margin-right: 10px;">
                    <?= Html::checkbox('selectedDatabases[]', false, ['value' => $file]) ?>
                    <?= Html::encode($file) ?>
                </label>

                <?= Html::a('❌ Видалити', ['parser/delete', 'file' => $file], [
                    'class' => 'btn btn-danger btn-sm',
                    'data-confirm' => 'Ви впевнені, що хочете видалити цей файл?',
                    'data-method' => 'post',
                    'style' => 'margin-left: 10px;',
                ]) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Перегляд', ['class' => 'btn btn-info', 'name' => 'action', 'value' => 'view']) ?>
        <?= Html::submitButton('Експортувати у CSV', ['class' => 'btn btn-success', 'name' => 'action', 'value' => 'csv']) ?>
        <?= Html::submitButton('Експортувати у TXT', ['class' => 'btn btn-primary', 'name' => 'action', 'value' => 'txt']) ?>
        <?= Html::submitButton('Експортувати у XML', ['class' => 'btn btn-outline-secondary', 'name' => 'action', 'value' => 'xml']) ?>
        <?= Html::submitButton('Експортувати у XML (об’єднати)', ['class' => 'btn btn-outline-warning', 'name' => 'action', 'value' => 'xml-merge']) ?>
    </div>

    <?php ActiveForm::end(); ?>
<?php endif; ?>

<hr>
<h3>Додати новий SQL-файл</h3>

<?php $uploadForm = ActiveForm::begin([
    'action' => ['parser/upload'],
    'method' => 'post',
    'options' => ['enctype' => 'multipart/form-data'],
]); ?>

<div class="form-group">
    <?= Html::fileInput('sqlFile', null, ['accept' => '.sql']) ?>
</div>

<div class="form-group">
    <?= Html::submitButton('Завантажити', ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>

<hr>
<h3>Завантаження готових архівів</h3>
<?php
$exportsDir = Yii::getAlias('@webroot/exports');
\yii\helpers\FileHelper::createDirectory($exportsDir);
$exportFiles = glob($exportsDir . '/*.zip');
if ($exportFiles):
    echo '<ul>';
    foreach ($exportFiles as $file) {
        $base = basename($file);
        echo '<li>' . \yii\helpers\Html::a($base, '@web/exports/' . $base, ['target' => '_blank']) . '</li>';
    }
    echo '</ul>';
else:
    echo '<p>Поки що немає збережених архівів.</p>';
endif;
?>
