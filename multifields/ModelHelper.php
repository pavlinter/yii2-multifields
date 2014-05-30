<?php

namespace pavlinter\multifields;

use Yii;
use yii\base\Model;
use yii\helpers\Html;

class ModelHelper
{
    public static function load(&$models,$scenario = null)
    {
        $res = false;
        if (empty($models)) {
            return $res;
        }
        $className = $models['0']->className();
        $postName  = $models['0']->formName();
        $models = [];
        $posts = Yii::$app->getRequest()->post($postName,[]);
        foreach ($posts as $id => $post) {
            if($id > 0) {
                $model = $className::findOne($id);
                if($model === null){
                    $model = new $className();
                }
            } else {
                $model = new $className();
            }
            if ($scenario !== null) {
                $model->scenario = $scenario;
            }
            if($model->load($post,'')) {
                $models[$id] = $model;
                $res = true;
            }
        }
        return $res;
    }
    public static function validate($models, $attributeNames = null, $clearErrors = true)
    {
        $valid = true;
        if (!is_array($models)) {
            $models = [$models];
        }
        foreach ($models as $model) {
            if (is_array($model)) {
                $valid = self::validate($model, $attributeNames, $clearErrors) && $valid;
            } else {
                $valid = $model->validate($attributeNames, $clearErrors) && $valid;
            }
        }
        return $valid;

    }
    public static function ajaxValidate($models, $attributeNames = null, $clearErrors = true)
    {
        $valid = true;
        if (!is_array($models)) {
            $models = [$models];
        }
        foreach ($models as $model) {
            if (is_array($model)) {
                $valid = self::validate($model, $attributeNames, $clearErrors) && $valid;
            } else {
                $valid = $model->validate($attributeNames, $clearErrors) && $valid;
            }
        }
        return $valid;

    }
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
                $valid = self::ajaxErrors($model);
            } else {
                foreach ($model->getErrors() as $attribute => $errors) {
                    $result[Html::getInputId($model,$attribute)] = $errors;
                }
            }
        }
        return $result;
    }


}