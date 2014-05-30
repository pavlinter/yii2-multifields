<?php

namespace pavlinter\multifields;
/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2013
 * @package yii2-widgets
 * @version 1.0.0
 */

/**
 * Asset bundle for MultiFields Widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class MultiFieldsAsset extends \yii\web\AssetBundle
{
    public $sourcePath =  '@vendor/pavlinter/yii2-multiFields/assets';
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
