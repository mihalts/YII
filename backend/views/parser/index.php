<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var array $databases */

$this->title = 'Експорт новин із баз даних';
?>
<h1><?= Html::encode($this->title) ?></h1>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger"><?= Yii::$app->session->getFlash('error') ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('warning')): ?>
    <div class="alert alert-warning"><?= Yii::$app->session->getFlash('warning') ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success"><?= Yii::$app->session->getFlash('success') ?></div>
<?php endif; ?>

<?php if (empty($databases)): ?>
    <p>Немає жодного <code>.sql</code> у <code>@storage/databases</code>. Завантажте файл нижче.</p>
<?php else: ?>

<?php $form = ActiveForm::begin(['action' => ['parser/export'], 'method' => 'post']); ?>

<div class="form-group">
    <label>Оберіть бази даних:</label>

    <?php foreach ($databases as $file): ?>
    <div class="row db-row" data-db="<?= Html::encode($file) ?>" style="margin-bottom:10px;">
        <div class="col-sm-3">
            <label class="checkbox-inline" style="font-weight:600;">
                <input type="checkbox" name="selectedDatabases[]" value="<?= Html::encode($file) ?>">
                <?= Html::encode($file) ?>
            </label>
        </div>

        <div class="col-sm-2">
            <?= Html::a('✖ Видалити', ['parser/delete', 'file' => $file], [
                'class' => 'btn btn-danger btn-sm',
                'data-confirm' => 'Видалити файл?',
                'data-method' => 'post',
            ]) ?>
        </div>

        <!-- Режим із селектами -->
        <div class="col-sm-7 schema-controls" data-state="select">
            <?= Html::dropDownList("table[$file]", null, [], [
                'class' => 'form-control input-sm tbl-select',
                'prompt' => '— таблиця —',
                'style' => 'display:inline-block; width:32%;'
            ]) ?>
            <?= Html::dropDownList("titleField[$file]", null, [], [
                'class' => 'form-control input-sm title-select',
                'prompt' => '— поле назви —',
                'style' => 'display:inline-block; width:32%;'
            ]) ?>
            <?= Html::dropDownList("contentField[$file]", null, [], [
                'class' => 'form-control input-sm text-select',
                'prompt' => '— поле тексту —',
                'style' => 'display:inline-block; width:32%;'
            ]) ?>
            <a href="#" class="toggle-manual" style="font-size:0.9em; margin-left:6px;">✎ Ручний ввід</a>
        </div>

        <!-- Режим ручного вводу -->
        <div class="col-sm-7 schema-inputs" data-state="manual" style="display:none;">
            <?= Html::textInput("table[$file]", '', [
                'class' => 'form-control input-sm tbl-input',
                'placeholder' => 'таблиця',
                'style' => 'display:inline-block; width:32%;'
            ]) ?>
            <?= Html::textInput("titleField[$file]", '', [
                'class' => 'form-control input-sm title-input',
                'placeholder' => 'поле назви',
                'style' => 'display:inline-block; width:32%;'
            ]) ?>
            <?= Html::textInput("contentField[$file]", '', [
                'class' => 'form-control input-sm text-input',
                'placeholder' => 'поле тексту',
                'style' => 'display:inline-block; width:32%;'
            ]) ?>
            <a href="#" class="toggle-selects" style="font-size:0.9em; margin-left:6px;">↩︎ Повернути селекти</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="form-group">
    <?= Html::submitButton('Перегляд', ['class' => 'btn btn-info', 'name' => 'action', 'value' => 'view']) ?>
    <?= Html::submitButton('Експортувати у CSV', ['class' => 'btn btn-success', 'name' => 'action', 'value' => 'csv']) ?>
    <?= Html::submitButton('Експортувати у TXT', ['class' => 'btn btn-primary', 'name' => 'action', 'value' => 'txt']) ?>
    <?= Html::submitButton('Експортувати у XML', ['class' => 'btn btn-default', 'name' => 'action', 'value' => 'xml']) ?>
    <?= Html::submitButton('Експортувати у XML (об’єднати)', ['class' => 'btn btn-warning', 'name' => 'action', 'value' => 'xml-merge']) ?>
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
        echo '<li>' . Html::a($base, '@web/exports/' . $base, ['target' => '_blank']) . '</li>';
    }
    echo '</ul>';
else:
    echo '<p>Поки що немає збережених архівів.</p>';
endif;
?>

<?php
$schemaUrl = Url::to(['parser/schema']); // будемо ходити GET’ом
$js = <<<JS
function fillSelects(row, schema){
  var tblSel = row.find('.tbl-select');
  var titleSel = row.find('.title-select');
  var textSel  = row.find('.text-select');

  tblSel.empty().append(new Option('— таблиця —', ''));
  titleSel.empty().append(new Option('— поле назви —', ''));
  textSel.empty().append(new Option('— поле тексту —', ''));

  var columnsByTable = {};

  // Підтримка двох форматів:
  // 1) [{table:'posts', columns:['title','content']}]
  // 2) {tables:['posts',...], columns:{posts:['title','content']}}
  if (Array.isArray(schema)) {
    schema.forEach(function(t) {
      if (!t || !t.table) return;
      tblSel.append(new Option(t.table, t.table));
      columnsByTable[t.table] = t.columns || [];
    });
  } else if (schema && typeof schema === 'object') {
    (schema.tables || []).forEach(function(t) {
      tblSel.append(new Option(t, t));
    });
    columnsByTable = schema.columns || schema.cols || {};
  }

  tblSel.off('change').on('change', function(){
    var t = $(this).val();
    var cols = columnsByTable[t] || [];
    titleSel.empty().append(new Option('— поле назви —', ''));
    textSel.empty().append(new Option('— поле тексту —', ''));
    cols.forEach(function(c){
      titleSel.append(new Option(c, c));
      textSel.append(new Option(c, c));
    });
  });
}

function loadSchemaForRow(row){
  var db = row.closest('.db-row').data('db');
  $.get('{$schemaUrl}', { db: db })
    .done(function(resp){
      if(resp && resp.ok){
        fillSelects(row, resp.schema || []);
      }else{
        // якщо схему не отримали — показуємо ручний ввід
        row.find('.toggle-manual').trigger('click');
      }
    })
    .fail(function(){
      row.find('.toggle-manual').trigger('click');
    });
}

// Перемикачі режимів
$(document).on('click', '.toggle-manual', function(e){
  e.preventDefault();
  var wrap = $(this).closest('.db-row');
  wrap.find('.schema-controls').hide();
  wrap.find('.schema-inputs').show();
});
$(document).on('click', '.toggle-selects', function(e){
  e.preventDefault();
  var wrap = $(this).closest('.db-row');
  wrap.find('.schema-inputs').hide();
  wrap.find('.schema-controls').show();
});

// коли перемикаємо режими — вимикаємо поля прихованого блоку
function syncEnabled(wrap) {
    var controls = wrap.find('.schema-controls');
    var manual   = wrap.find('.schema-inputs');
    if (controls.is(':visible')) {
      manual.find('input').prop('disabled', true);
      controls.find('select').prop('disabled', false);
    } else {
      controls.find('select').prop('disabled', true);
      manual.find('input').prop('disabled', false);
    }
  }
  
  $(document).on('click', '.toggle-manual', function (e) {
    e.preventDefault();
    var wrap = $(this).closest('.db-row');
    wrap.find('.schema-controls').hide();
    wrap.find('.schema-inputs').show();
    syncEnabled(wrap);
  });
  
  $(document).on('click', '.toggle-selects', function (e) {
    e.preventDefault();
    var wrap = $(this).closest('.db-row');
    wrap.find('.schema-inputs').hide();
    wrap.find('.schema-controls').show();
    syncEnabled(wrap);
  });
  
  // при завантаженні сторінки та ПЕРЕД сабмітом форми — вимкнути приховані інпути
  $('.db-row').each(function(){ syncEnabled($(this)); });
  
  $('form').on('submit', function () {
    $('.db-row').each(function(){ syncEnabled($(this)); });
  });

// Автозавантаження схеми для кожного блоку
$('.schema-controls').each(function(){ loadSchemaForRow($(this)); });
JS;

$this->registerJs($js);

