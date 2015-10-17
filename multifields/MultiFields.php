<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2014
 * @package yii2-multifields
 * @version 1.0.2
 */

namespace pavlinter\multifields;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Class MultiFields
 */
class MultiFields extends \yii\base\Widget
{
    public $form;
    public $models;
    public $attributes = [];
    /**
     * Index for js template.
     * Js file index will be replaced with unique key
     */
    public $index = 'index';
    public $parentClassPrefix = '-mf-row';

    public $templateFields;
    public $template = null;
    public $closeButtonClass = 'mf-btn-close pull-right';
    public $clientOptions   = [];

    private $parentClass = null;
    private $uniqId = 0;
    private $jsTemplate;

    public function init()
    {
        parent::init();

        if ($this->models === []) {
            throw new InvalidConfigException('The "models" property is empty.');
        }

        if ($this->attributes === []) {
            throw new InvalidConfigException('The "attributes" property must be set.');
        }

        $Models = [];

        $modelName = reset($this->models);

        $modelName = $modelName->formName();
        foreach($this->models as $id => $model) {
            if ($model->isNewRecord) {
                $uniqId = --$this->uniqId;
            } else {
                $uniqId = $model->id;
            }
            $Models[$uniqId] = $model;
        }
        $this->models = $Models;

        if ($this->template === null) {
            $this->template = function($parentOptions, $closeButtonClass, $templateFields) {
                $closeBtn = Html::tag('a', '&times;', ['class' => $closeButtonClass, 'href' => 'javascript:void(0);']);
                return Html::tag('div', $closeBtn . $templateFields, $parentOptions);
            };
        }

        $this->parentClass = $modelName . $this->parentClassPrefix;
        $templateFields = '';

        foreach ($this->attributes as $i => $settings) {
            if (!is_array($settings)) {
                $settings = ['attribute' => $settings];
            }
            $settings = ArrayHelper::merge([
                'attribute' => '',
                'options'=> [],
                'field' => null,
            ],$settings);

            $templateFields .= '{' . $settings['attribute'] . '}';
            $this->attributes[$i] = $settings;
        }
        if ($this->templateFields === null) {
            $this->templateFields = $templateFields;
        }

        $defClientOptions = [
            'confirmMessage' => Yii::t('yii', 'Are you sure you want to delete this item?'),
            'deleteRouter' => Url::to(['delete']),
        ];
        $this->clientOptions = ArrayHelper::merge($defClientOptions, $this->clientOptions);
    }

    /**
     * Executes the widget.
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
        $attributes         = $this->attributes;
        $this->attributes   = [];
        $this->jsTemplate   = $this->getTemplate($this->index);
        $html = '';
        $createJsTemplate   = true;
        $nameIndexs = [];

        foreach ($this->models as $id => $model)
        {
            $temlate   = $this->getTemplate($id);
            foreach($attributes as $i => $settings)
            {
                if (!$model->hasAttribute($settings['attribute'])) {
                    continue;
                }

                $attribute      = $settings['attribute'];
                $name           = '[' . $id . ']' . $attribute;
                $nameIndex      = '[' . $this->index . ']' . $attribute;

                $settings['attribute']       = $name;
                $settings['attributeIndex']  = $nameIndex;
                $settings['uniqId']          = $id;
                $settings['idTpl']           = Html::getInputId($model,$nameIndex);

                if (!is_callable($settings['field'])) {
                    $settings['field'] = function($activeField,$options,$parentClass,$closeButtonClass){
                        return $activeField->textInput($options);
                    };
                }

                $field = $this->field($model, $settings);
                $temlate = str_replace('{' . $attribute . '}', $field, $temlate);

                if ($createJsTemplate) {
                    $activeField = $this->jsTemplateField($model, $settings);
                    $this->jsTemplate = str_replace('{' . $attribute . '}', $activeField, $this->jsTemplate);
                }
                $nameIndexs[$i] = $nameIndex;
            }
            $html .= $temlate;
            if ($createJsTemplate) {
                $createJsTemplate = false;
            }
        }




        foreach ($nameIndexs as $i => $ni) {
            foreach ($this->form->attributes as $j => $at) {
                if($at['name'] === $ni) {
                    $this->attributes[$i] = $at;
                    unset($this->form->attributes[$j]);
                }
            }
        }

        if (count($this->attributes) !== count($attributes)) {
            throw new InvalidConfigException('The field rules or scenario must be set.');
        }

        $this->form->attributes = array_values($this->form->attributes);

        $this->registerAssets();
        return $html;
    }
    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $closeButtonClass = explode(' ', trim($this->closeButtonClass))[0];

        $clientOptions = ArrayHelper::merge([
            'btn' => '.cloneBtn',
            'uniqId' => (--$this->uniqId),
            'parentClass' => $this->parentClass,
            'attributes' => $this->attributes,
            'appendTo' => '',
            'index' => $this->index,
            'template' => $this->jsTemplate,
            'form' => '#' . $this->form->id,
            'closeButtonClass' => $closeButtonClass,
            'requiredRows' => 1,
        ],$this->clientOptions);
        $btn = ArrayHelper::remove($clientOptions,'btn');
        $clientOptions = Json::encode($clientOptions);

        $view = $this->getView();

        MultiFieldsAsset::register($view);
        $view->registerJs("jQuery('" . $btn . "').multiFields(" . $clientOptions . ");",$view::POS_LOAD);
    }

    /**
     * @param $model
     * @param $settings
     * @return mixed
     */
    public function field($model,$settings)
    {
        $settings['options']['data-mf-uniq'] = $settings['uniqId'];
        $activeField = $this->form->field($model, $settings['attribute']);
        return call_user_func($settings['field'], $activeField, $settings['options'], ['class' => $this->parentClass,'data-mf-uniq' => $settings['uniqId']], $this->closeButtonClass);
    }

    /**
     * @param $model
     * @param $settings
     * @return mixed
     */
    public function jsTemplateField($model,$settings)
    {
        $settings['options']['data-mf-uniq'] = $this->index;
        $activeField = $this->form->field($model, $settings['attributeIndex']);
        return call_user_func($settings['field'], $activeField, $settings['options'], ['class' => $this->parentClass,'data-mf-uniq' => $settings['uniqId']], $this->closeButtonClass);
    }

    /**
     * @param $uniq
     * @return mixed
     * @throws InvalidConfigException
     */
    public function getTemplate($uniq)
    {
        if (is_callable($this->template)) {
            return call_user_func($this->template, ['class' => $this->parentClass,'data-mf-uniq' => $uniq], $this->closeButtonClass, $this->templateFields);
        }
        throw new InvalidConfigException('Template must be function!');
    }

}