<?php
/**
 * Simple Image Service "imagex" which allows the resizing and cropping with a focus point of images
 *
 * To clean up locally cached thumbnails and source images execute this command periodically:
 *      find ./cache -mtime +30 -type f -delete
 *
 *
 * Author: thomasklaner
 * Date: 26/02/15
 * Time: 10:56
 */

require_once('src/Http/RequestParameters.php');

use Http\RequestParameters;

define('CACHE_DIRECTORY', __DIR__ . '/cache/');
define('SOURCE_CACHE_DIRECTORY', CACHE_DIRECTORY . 'source/');
define('THUMBS_CACHE_DIRECTORY', CACHE_DIRECTORY . 'thumbs/');

$parameters = new RequestParameters($_GET, array(
    'url' => array('required' => true),
    'mode' => array('required' => false, 'default' => 'resize'),
    'width' => array('required' => false, 'default' => 0),
    'height' => array('required' => false, 'default' => 0),
    'x' => array('required' => false, 'default' => 0),
    'y' => array('required' => false, 'default' => 0)
));

// Before starting new image processing check if resized image is already available
$imageFileName =  THUMBS_CACHE_DIRECTORY . $parameters->getHash() . '.thumb';
if(file_exists($imageFileName)) {
    $image = new Imagick($imageFileName);
} else {
    // Cache input/source image
    $sourceImageFileName = SOURCE_CACHE_DIRECTORY . md5($parameters->get('url')) . '.orig';
    if(!file_exists($sourceImageFileName)) {
        file_put_contents($sourceImageFileName, file_get_contents($parameters->get('url')));
    }
    $image = new Imagick($sourceImageFileName);

    if($parameters->get('width') > 0 || $parameters->get('height') > 0) {
        // first resize
        $wR = $image->getimagewidth() / $parameters->get('width');
        $hR = $image->getimageheight() / $parameters->get('height');
        if($parameters->get('mode') == 'crop') {
            $image->resizeimage(($wR <= $hR) ? $parameters->get('width') : 0, ($wR > $hR) ? $parameters->get('height') : 0, Imagick::FILTER_CATROM, 1);
        } else {
            $image->resizeimage(($wR >= $hR) ? $parameters->get('width') : 0, ($wR < $hR) ? $parameters->get('height') : 0, Imagick::FILTER_CATROM, 1);
        }

        // then crop
        if($parameters->get('mode') == 'crop') {
            $x = min(round(($parameters->get('x') / 2 + .5) * $image->getimagewidth() - $parameters->get('width')/2), $image->getimagewidth()-$parameters->get('width'));
            $y = min(round(($parameters->get('y') / -2 + .5) * $image->getimageheight() - $parameters->get('height')/2), $image->getimageheight()-$parameters->get('height'));
            $image->cropimage($parameters->get('width'), $parameters->get('height'), $x, $y);
        }
    }

    // Cache/Store output image
    $imageExtension = strtolower($image->getimageformat());
    $image->writeimage(($imageExtension == 'jpeg' ? 'jpg' : $imageExtension) .':' . $imageFileName);
}

header('Content-type: ' . $image->getimagemimetype());
echo $image;
