<?php
/**
 * Simple Image Service "imagex" which allows the resizing and cropping with a focus point of images
 *
 * To clean up locally cached thumbnails and source images execute this command periodically:
 *      find ./cache -mtime +30 -type f -delete
 *
 * Author: thomasklaner
 * Date: 26/02/15
 * Time: 10:56
 */

namespace Imagex;

use Imagick;
use Imagex\Http\RequestParameters;

class Imagex {

    /** @var array */
    protected $config;

    /** @var RequestParameters */
    protected $parameters;

    /** @var Imagick */
    protected $image;

    public function __construct(array $config = array()) {
        $defaultConfig = array(
            'cache_directory' => 'cache',
            'source_cache_directory' => 'source',
            'thumbs_cache_directory' => 'thumbs'
        );
        $this->config = array();
        foreach($defaultConfig as $key => $value) {
            $this->config[$key] = @$config[$key] ?: $value;
        }
    }

    public function process(array $params) {
        $this->parameters = new RequestParameters($params, array(
            'url' => array('required' => true),
            'mode' => array('required' => false, 'default' => 'resize'),
            'width' => array('required' => false, 'default' => 0),
            'height' => array('required' => false, 'default' => 0),
            'x' => array('required' => false, 'default' => 0),
            'y' => array('required' => false, 'default' => 0)
        ));

        // Before starting new image processing check if resized image is already available
        $imageFileName =  $this->getConfigPath('cache_directory') . $this->getConfigPath('thumbs_cache_directory') . $this->parameters->getHash() . '.thumb';
        if(file_exists($imageFileName)) {
            $this->image = new Imagick($imageFileName);
        } else {
            // Cache input/source image
            $sourceImageFileName = $this->getConfigPath('cache_directory') . $this->getConfigPath('source_cache_directory') . md5($this->parameters->get('url')) . '.orig';
            if(!file_exists($sourceImageFileName)) {
                file_put_contents($sourceImageFileName, file_get_contents($this->parameters->get('url')));
            }
            $this->image = new Imagick($sourceImageFileName);

            if($this->parameters->get('width') > 0 || $this->parameters->get('height') > 0) {
                // first resize
                $wR = $this->image->getimagewidth() / $this->parameters->get('width');
                $hR = $this->image->getimageheight() / $this->parameters->get('height');
                if($this->parameters->get('mode') == 'crop') {
                    $this->image->resizeimage(($wR <= $hR) ? $this->parameters->get('width') : 0, ($wR > $hR) ? $this->parameters->get('height') : 0, Imagick::FILTER_CATROM, 1);
                } else {
                    $this->image->resizeimage(($wR >= $hR) ? $this->parameters->get('width') : 0, ($wR < $hR) ? $this->parameters->get('height') : 0, Imagick::FILTER_CATROM, 1);
                }

                // then crop
                if($this->parameters->get('mode') == 'crop') {
                    $x = max(min(round(($this->parameters->get('x') / 2 + .5) * $this->image->getimagewidth() - $this->parameters->get('width')/2), $this->image->getimagewidth()-$this->parameters->get('width')), 0);
                    $y = max(min(round(($this->parameters->get('y') / -2 + .5) * $this->image->getimageheight() - $this->parameters->get('height')/2), $this->image->getimageheight()-$this->parameters->get('height')), 0);
                    $this->image->cropimage($this->parameters->get('width'), $this->parameters->get('height'), $x, $y);
                }
            }

            // Cache/Store output image
            $imageExtension = strtolower($this->image->getimageformat());
            $this->image->writeimage(($imageExtension == 'jpeg' ? 'jpg' : $imageExtension) .':' . $imageFileName);
        }
    }

    public function renderImage() {
        header('Content-type: ' . $this->image->getimagemimetype());
        echo $this->image;
    }

    protected function getConfigPath($name) {
        return $this->ensureTrailingSlash($this->config[$name]);
    }

    private function ensureTrailingSlash($path) {
        return strrpos($path, '/') == strlen($path)-1 ? $path : $path . '/';
    }
} 