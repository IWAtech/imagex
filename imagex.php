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

require_once __DIR__ . '/vendor/autoload.php';

use Imagex\Imagex;

// initialize imagex and process request
$imagex = new Imagex(array('cache_directory' => __DIR__ . '/cache/'));
$imagex->process($_GET);

// sets file header and prints image
$imagex->renderImage();
