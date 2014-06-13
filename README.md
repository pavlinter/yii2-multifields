Yii2 Multifields
================

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pavlinter/yii2-multifields "master"
```

or add

```
"pavlinter/yii2-multifields": "master"
```

to the require section of your `composer.json` file.

Example
------------
Controller
```
use pavlinter\multifields\ModelHelper;

...

public function actionProduct()
{

    $models = Product::find()->indexBy('id')->all();
    if (empty($models)) {
        $models = [new Product];
    }

    if(Yii::$app->request->isPost) {

        if (ModelHelper::load($models)) {

            if (ModelHelper::validate([$models])) {

                $newId = [];
                foreach ($models as $oldId => $model) {
                    $model->save(false);
                    ModelHelper::ajaxChangeField($newId,$model,'name',$oldId);
                    ModelHelper::ajaxChangeField($newId,$model,'amount',$oldId);
                }
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['r' => 1 ,'newId' => $newId];
                } else {
                    return $this->refresh();
                }

            } else {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    $errors = ModelHelper::ajaxErrors([$models]);
                    return ['r' => 0,'errors' => $errors];
                }
            }
        }
    }
    return $this->render('product',[
        'models'=>$models,
    ]);
}
public function actionDelete()
{
    if (Yii::$app->request->isPost) {
        Product::deleteAll("id=:id",[':id'=>Yii::$app->request->post('id')]);
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['r'=>1];
    }
}
```

View
```
use pavlinter\multifields\MultiFields;

...

<?php $form = ActiveForm::begin([
    'id' => 'product-form',
    'beforeSubmit' => 'function(form) {
        jQuery.ajax({
            url: form.attr("action"),
            type: "POST",
            dataType: "json",
            data: form.serialize(),
            success: function(d) {
                if(d.r) {
                    form.trigger("updateRows",[d.newId]).trigger("scrollToTop");
                } else {
                    form.trigger("updateErrors",[d.errors]).trigger("scrollToError");
                }
            },
        });
        return false;
    }',
]); ?>

    <?= MultiFields::widget([
        'models' => $models,
        'form' => $form,
        'attributes' => [
            [
                'attribute' => 'name',
                'options'=> [],
                'field' => function ($activeField,$options,$parentClass,$closeButtonClass) {
                        return $activeField->textArea($options);
                },
            ],
            'amount',
        ],
        //default
        'parentClassPrefix' => '-mf-row',
        'closeButtonClass' => 'mf-btn-close pull-right',
        'clientOptions' => [
            'btn' => '.cloneBtn',
            'appendTo' => '',
            'confirmMessage' => Yii::t('yii' , 'Are you sure you want to delete this item?'),
            'deleteRouter' => Url::to([Yii::$app->controller->getUniqueId().'/delete']),
        ],
        'template' => function($parentClass,$closeButtonClass,$templateFields){ //default
            $closeBtn = Html::tag('a','&times;',['class'=>$closeButtonClass,'href'=>'javascript:void(0)']);
            return Html::tag('div',$closeBtn.$templateFields,['class'=>$parentClass]);
        }
    ]);?>

    <?= Button::widget([
        'label'=>'Add Product',
        'options'=>[
            'class'=>'cloneBtn'
        ]
    ]);?>

    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary pull-right', 'name' => 'contact-button']) ?>
    </div>
<?php ActiveForm::end(); ?>
```
