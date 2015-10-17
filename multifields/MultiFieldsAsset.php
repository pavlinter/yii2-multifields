<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2014
 * @package yii2-multifields
 * @version 1.0.2
 */

namespace pavlinter\multifields;

/**
 * Asset bundle for MultiFields Widget
 */
class MultiFieldsAsset extends \yii\web\AssetBundle
{
    public $sourcePath =  '@vendor/pavlinter/yii2-multifields/assets';
    public $css = [
        'css/style.css',
    ];
    public $js = [
        'js/jquery.multiFields.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];

}
