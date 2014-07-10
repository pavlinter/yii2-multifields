<?php

namespace pavlinter\multifields;
/**
 * @copyright Copyright &copy; Pavels Radajevs, 2014
 * @package yii2-multifields
 * @version 1.0.0
 */

/**
 * Asset bundle for MultiFields Widget
 *
 * @author Pavels Radajevs <pavlinter@gmail.com>
 * @since 1.0
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
