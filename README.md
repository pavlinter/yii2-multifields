Yii2 Multifields
================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pavlinter/yii2-multifields "*"
```

or add

```
"pavlinter/yii2-multifields": "*"
```

to the require section of your `composer.json` file.

Example
------------
Controller
```

use pavlinter\multifields\ModelHelper;
use yii\web\Response;

...

public function actionCreate()
{
    $model = new Product();
    $options = [new ProductOption(['scenario' => 'multiFields'])];

    if(Yii::$app->request->isPost) {

        $loaded = $model->load(Yii::$app->request->post());
        $loaded = ModelHelper::load($options) && $loaded;

        if ($loaded) {

            if (ModelHelper::validate([$model, $options])) {
                $model->save(false);
                $newId = [];
                foreach ($options as $oldId => $option) {
                    $option->id_product = $model->id;
                    $option->save(false);
                    ModelHelper::ajaxChangeField($newId, $option, 'name', $oldId);
                    ModelHelper::ajaxChangeField($newId, $option, 'value', $oldId);
                }
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['r' => 1, 'newId' => $newId];
                } else {
                    return $this->redirect(['index']);
                }
            } else {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    $errors = ModelHelper::ajaxErrors([$model, $options]);
                    return ['r' => 0, 'errors' => $errors];
                }
            }
        }
    }
    return $this->render('create', [
        'model' => $model,
        'options' => $options,
    ]);
}
public function actionUpdate($id)
{
    $model = Product::findOne($id);
    $options = ProductOption::find()->where(['id_product' => $id])->indexBy('id')->all();
    if (empty($options)) {
        $options[] = new ProductOption(['scenario' => 'multiFields']);
    } else {
        foreach ($options as $option) {
            $option->scenario = 'multiFields';
        }
    }

    if(Yii::$app->request->isPost) {

        $loaded = $model->load(Yii::$app->request->post());
        $loaded = ModelHelper::load($options) && $loaded;

        if ($loaded) {

            if (ModelHelper::validate([$model, $options])) {
                $model->save(false);
                $newId = [];
                foreach ($options as $oldId => $option) {
                    $option->id_product = $model->id;
                    $option->save(false);
                    ModelHelper::ajaxChangeField($newId, $option, 'name', $oldId);
                    ModelHelper::ajaxChangeField($newId, $option, 'value', $oldId);
                }

                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['r' => 1, 'newId' => $newId];
                } else {
                    return $this->redirect(['index']);
                }
            } else {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    $errors = ModelHelper::ajaxErrors([$model, $options]);
                    return ['r' => 0, 'errors' => $errors];
                }
            }
        }
    }

    return $this->render('update', [
        'model' => $model,
        'options' => $options,
    ]);

}
public function actionDeleteOption()
{
    $id = Yii::$app->request->post('id');
    $model = ProductOption::findOne($id);
    if ($model !== null) {
        $model->delete();
    }
    Yii::$app->response->format = Response::FORMAT_JSON;
    return ['r' => 1];
}
```

View
```
use pavlinter\multifields\MultiFields;

...
<?php
$this->registerJs('
    $("#product-form").on("beforeSubmit",function(e){
        var $form = $(this);
        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            dataType: "json",
            data: $form.serialize(),
        }).done(function(d){
            if(d.r) {
                $form.trigger("updateRows",[d.newId]).trigger("scrollToTop");
            } else {
                $form.trigger("updateErrors",[d.errors]).trigger("scrollToError");
            }
        });
        return false;
    });
');
?>

<?php $form = ActiveForm::begin(['id' => 'product-form']); ?>

    <?= $form->errorSummary([$model,reset($options)],['class' => 'alert alert-danger']); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 200]) ?>

    <?= $form->field($model, 'amount')->textInput() ?>

    <?= MultiFields::widget([
        'models' => $options,
        'form' => $form,
        'attributes' => [
            'name',
            [
                'attribute' => 'value',
                'options'=> [],
                'field' => function ($activeField, $options, $parentClass, $closeButtonClass) {
                    return $activeField->textArea($options);
                },
            ],
        ],
        //default
        'parentClassPrefix' => '-mf-row',
        'closeButtonClass' => 'mf-btn-close pull-right',
        'clientOptions' => [
            'btn' => '.cloneBtn',
            'appendTo' => '',
            'confirmMessage' => Yii::t('yii' , 'Are you sure you want to delete this item?'),
            'deleteRouter' => Url::to(['delete-option']), //Url::to(['delete'])
        ],
        'template' => function($parentOptions, $closeButtonClass, $templateFields){ //default
            $closeBtn = Html::tag('a', '&times;', ['class' => $closeButtonClass, 'href' => 'javascript:void(0)']);
            return Html::tag('div', $closeBtn . $templateFields, $parentOptions);
        },
    ]);?>

    <?= \yii\bootstrap\Button::widget([
        'label' => 'Add Product',
        'options' => [
            'class' => 'cloneBtn'
        ]
    ]);?>

    <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>

<?php ActiveForm::end(); ?>
```
