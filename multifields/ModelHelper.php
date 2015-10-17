<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2014
 * @package yii2-multifields
 * @version 1.0.2
 */

namespace pavlinter\multifields;

use Yii;
use yii\helpers\Html;

/**
 * Helpers for MultiFields Widget
 */
class ModelHelper
{
    /**
     * @param $models
     * @param null $scenario
     * @param bool $existModels
     * @return bool
     */
    public static function load(&$models, $scenario = null, $existModels = true)
    {
        $res = false;
        if (empty($models)) {
            return $res;
        }

        $model              = reset($models);
        $className          = $model->className();
        $postName           = $model->formName();
        $defaultScenario    = $model->scenario;
        $cacheModels        = $models;
        $models             = [];
        $posts = Yii::$app->getRequest()->post($postName,[]);
        
        foreach ($posts as $id => $post) {
            if($id > 0) {
                if ($existModels && isset($cacheModels[$id])) {
                    $model = $cacheModels[$id];
                } else {
                    $model = $className::findOne($id);
                }
                if($model === null){
                    $model = new $className();
                }
            } else {
                $model = new $className();
            }
            if ($scenario !== null) {
                $model->scenario = $scenario;
            } else {
                $model->scenario = $defaultScenario;
            }
            if($model->load($post, '')) {
                $models[$id] = $model;
                $res = true;
            }
        }
        return $res;
    }

    /**
     * @param $models
     * @param null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public static function validate($models, $attributeNames = null, $clearErrors = true)
    {
        $valid = true;
        if (!is_array($models)) {
            $models = [$models];
        }
        foreach ($models as $model) {
            if (is_array($model)) {
                $valid = static::validate($model, $attributeNames, $clearErrors) && $valid;
            } else {
                $valid = $model->validate($attributeNames, $clearErrors) && $valid;
            }
        }
        return $valid;

    }

    /**
     * @param $models
     * @return array
     */
    public static function ajaxErrors($models)
    {
        if (!is_array($models)) {
            $models = [$models];
        }
        $result = [];
        foreach ($models as $model) {
            if (is_array($model)) {
                foreach ($model as $i => $m) {
                    foreach ($m->getErrors() as $attribute => $errors) {
                        $result[Html::getInputId($m, "[$i]" . $attribute)] = $errors;
                    }
                }
            } else {
                foreach ($model->getErrors() as $attribute => $errors) {
                    $result[Html::getInputId($model,$attribute)] = $errors;
                }
            }
        }
        return $result;
    }

    /**
     * @param $arr
     * @param $model
     * @param $attribute
     * @param $key
     * @param null $id
     * @return bool
     */
    public static function ajaxChangeField(&$arr,$model,$attribute,$key,$id = null)
    {
        if ($id === null) {
            $id = $model->id;
        }
        if ($key == $id) {
            return false;
        }
        $arr[] = [
            'id' => Html::getInputId($model,'['.$key.']'.$attribute),
            'uniq' => $id,
            'newName' => Html::getInputName($model,'['.$id.']'.$attribute),
        ];
        return true;
    }

}