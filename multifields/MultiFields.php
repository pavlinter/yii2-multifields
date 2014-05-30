<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2013
 * @package yii2-widgets
 * @version 1.0.0
 */

namespace pavlinter\multifields;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use yii\web\JsExpression;
use yii\helpers\Json;
use yii\helpers\Url;



/**
 * The TimePicker widget  allows you to easily select a time for a text input using
 * your mouse or keyboards arrow keys. Thus widget is a wrapper enhancement over the
 * TimePicker JQuery plugin by rendom forked from the plugin by jdewit. Additional
 * enhancements have been done to this input widget for compatibility with Bootstrap 3.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 * @see https://github.com/rendom/bootstrap-3-timepicker
 * @see https://github.com/jdewit/bootstrap-timepicker
 */
class MultiFields extends \yii\base\Widget
{

    public $form;
    public $models;
    public $attributes = [];
    public $appendTo = '';
    /**
     * Index for js template.
     * Js file index will be replaced with unique key
     */
    public $index = 'index';

    public $btn = '.cloneBtn';
    public $createOnlyTemlate = false;
    public $parentClass = '';
    public $parentClassPrefix = 'Row';
    /**
     * example: funsction($parentClass){
     *      '<div class="'.$parentClass.'">{modelName_attribute},{modelName_attribute}</div>';
     * }
     */
    public $template = null;

    public $closeButtonClass = 'mf-btn-close pull-right';

    public $templateFields = ''; //{email},{password}
    public $options = [];
    public $errorOptions = ['class' => 'help-block'];
    public $cssFiles;


    private $uniqId = 0;
    private $jsTemplate;


    public function init()
    {
        parent::init();

        if($this->models===[]){
            throw new InvalidConfigException('Empty model array');
        }

        $Models = [];



        $modelName = reset($this->models);



        $modelName = $modelName->formName();
        foreach($this->models as $id =>$model) {
            if($model->isNewRecord) {
                $uniqId = --$this->uniqId;
            } else {
                $uniqId = $model->id;
            }
            $Models[$uniqId] = $model;
        }
        $this->models = $Models;

        if($this->template===null){
            $this->template = function($parentClass,$closeButtonClass,$templateFields){
                $closeBtn = Html::tag('a','&times;',['class'=>$closeButtonClass,'href'=>'javascript:void(0)']);
                return Html::tag('div',$closeBtn.$templateFields,['class'=>$parentClass]);
            };
        }

        $this->parentClass = $this->parentClass?$modelName.$this->parentClassPrefix.' '.$this->parentClass:$modelName.$this->parentClassPrefix;

        $isEmpty = $this->templateFields?false:true;

        foreach ($this->attributes as $i=>$options) {
            if(!is_array($options)){
                $options = ['attribute'=>$options];
            }
            $attribute = ArrayHelper::merge([
                'attribute'=>'',
                'htmlOptions'=>[],
                'field'=>null,
            ],$options);
            if($isEmpty) {
                $this->templateFields .= '{'.$attribute['attribute'].'}';
            }
            $this->attributes[$i] = $attribute;
        }


        $defOptions = [
            'confirmMessage' => 'Are you sure you want to delete this item?',
            'deleteRouter' => Url::toRoute([Yii::$app->controller->getUniqueId().'/delete']),
            'extData' => [],
            'deleteCallback' => 'function(data,$row,$form){
                   if(data.r){
                        $row.remove();
                   }else{
                        $row.show();
                   }
            }'
        ];
        $this->options = ArrayHelper::merge($defOptions,$this->options);

        if (!($this->options['deleteCallback'] instanceof JsExpression)) {
            $this->options['deleteCallback'] = new JsExpression($this->options['deleteCallback']);
        }


    }
    public function run()
    {


        $attributes         = $this->attributes;
        $view = $this->getView();
        $this->attributes   = [];

        $this->jsTemplate   = $this->getTemplate();
        $html = '';
        $createJsTemplate   = true;



        foreach($this->models as $id=>$model)
        {
            $modelName = $model->formName();
            $temlate   = $this->getTemplate();

            foreach($attributes as $i=>$settings)
            {

                if(!$model->hasAttribute($settings['attribute'])){
                    continue;
                }
                $attribute      = $settings['attribute'];
                $name           = '['.$id.']'.$attribute;
                $nameIndex      = '['.$this->index.']'.$attribute;


                $settings['attribute']       = $name;
                $settings['attributeIndex']  = $nameIndex;
                $settings['uniqId']          = $id;
                $settings['idTpl']           = Html::getInputId($model,$nameIndex);


                if(is_callable($settings['field'])){
                    $func = $settings['field'];
                }else{
                    $func = function($ActiveForm,$htmlOptions){
                        return $ActiveForm->textInput($htmlOptions);
                    };
                }


                $field = $this->field($model,$settings,$func);
                if($field==null) {
                    $temlate = '';
                } else {
                    $temlate = str_replace('{'.$attribute.'}',$field,$temlate);
                }
                if($createJsTemplate) {
                    $ActiveField = $this->jsTemplateField($model,$settings,$func);
                    $this->jsTemplate = str_replace('{'.$attribute.'}',$ActiveField,$this->jsTemplate);
                }
                if(isset($this->form->attributes[$nameIndex])) {
                    $this->attributes[$i] = $this->form->attributes[$nameIndex];
                    unset($this->form->attributes[$nameIndex]);
                }
            }
            $html .= $temlate;
            if($createJsTemplate){
                $createJsTemplate = false;
            }
        }
        $this->registerAssets();
        return $html;
    }
    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $parentClass = explode(' ',trim($this->parentClass));
        $parentClass = trim($parentClass[0]);

        $closeButtonClass = explode(' ',trim($this->closeButtonClass));
        $closeButtonClass = trim($closeButtonClass[0]);

        $options = ArrayHelper::merge(array(
            'uniqId'=>(--$this->uniqId),
            'parentClass'=>$parentClass,
            'attributes'=>$this->attributes,
            'appendTo'=>$this->appendTo,
            'index'=>$this->index,
            'template'=>$this->jsTemplate,
            'form'=>'#'.$this->form->id,
            'closeButtonClass'=>$closeButtonClass,
        ),$this->options);

        $options = Json::encode($options);

        $view = $this->getView();

        if($this->cssFiles) {
            foreach ($this->cssFiles as $cssFile) {
                $view->registerCssFile($cssFile,[MultiFieldsAsset::className()]);
            }
            MultiFieldsAsset::register($view)->css = [];
        } else {
            MultiFieldsAsset::register($view);
        }



        $view->registerJs("jQuery('".$this->btn."').multiFields(".$options.");");
    }

    public function field($model,$settings,$func)
    {
        if($this->createOnlyTemlate){
            return null;
        }
        $settings['htmlOptions']['mf-uniq'] = $settings['uniqId'];
        $ActiveForm = $this->form->field($model,$settings['attribute']);
        return $func($ActiveForm,$settings['htmlOptions']);
    }
    public function jsTemplateField($model,$settings,$func)
    {
        $settings['htmlOptions']['mf-uniq'] = $this->index;
        $ActiveForm = $this->form->field($model,$settings['attributeIndex']);
        return $func($ActiveForm,$settings['htmlOptions']);
    }
    public function getTemplate()
    {
        if(is_callable($this->template)){
            $func = $this->template;
            return $func($this->parentClass,$this->closeButtonClass,$this->templateFields);
        }
        throw new InvalidConfigException('Template must be function!');
    }

}