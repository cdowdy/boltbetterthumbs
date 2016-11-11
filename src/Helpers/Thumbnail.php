<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Helpers;

use Silex\Application;


/**
 * Class Thumbnail
 * @package Bolt\Extension\cdowdy\betterthumbs\Helpers\Thumb
 */
class Thumbnail
{

    /**
     * @var
     */
    protected $_sourceImage;

    /**
     * @var
     */
    protected $_altText;

    /**
     * @var
     */
    protected $_title;

    /**
     * @var
     */
    protected $_height;

    /**
     * @var
     */
    protected $_width;

    /**
     * @var
     * Modifications done to the image through intervention (crop/filters etc)
     */
    protected $modifications = [];
    /**
     * @var array
     */
    protected $_extensionConfig;


    protected $_configName;

    protected $widthDensity;

    protected $sizesAttrib = [];

    /**
     * Thumbnail constructor.
     * @param $_extensionConfig
     */
    public function __construct( array $_extensionConfig )
    {
        $this->_extensionConfig = $_extensionConfig;
//        $this->_extensionConfig[$_configName] = $_configName;
    }

    /**
     * @return mixed
     */
    public function getSourceImage()
    {
        return $this->_sourceImage;
    }


    /**
     * @param $sourceImage
     * @return $this
     */
    public function setSourceImage($sourceImage)
    {
        /* copied from bolts thumbnail helper
         * in bolt/bolt/src/Helpers/Image/Thumbnail.php
         */

        if (is_array($sourceImage)) {
            $rawFileName = isset($sourceImage['filename']) ? $sourceImage['filename'] : (isset($sourceImage['file']) ? $sourceImage['file'] : null);
            isset($sourceImage['title']) ? $this->_title = $sourceImage['title'] : $rawFileName;
            isset($sourceImage['alt']) ? $this->_altText = $sourceImage['alt'] : $rawFileName;
            $sourceImage = $rawFileName;
        }

        $this->_sourceImage = $sourceImage;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAltText()
    {
        return $this->_altText;
    }

    /**
     * @param mixed $altText
     */
    public function setAltText($altText)
    {
        $this->_altText = $altText;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }


    /**
     * @param bool $round
     * @return float
     */
    public function getHeight( $round = true )
    {
        if ($round) {
            return round($this->_height);
        }

        return $this->_height;
    }


    /**
     * @param $height
     * @param int $default
     * @return $this
     */
    public function setHeight($height, $default = 200 )
    {
        $extensionConfig = $this->_extensionConfig;
        $boltConfig = $this->BoltConfig;


        if ( !is_numeric($height) ) {

            if (empty($extensionConfig)) {

                $this->_height = $boltConfig;

            } elseif (empty($boltConfig)) {

                $this->_height = $default;

            } else {

                $this->_height = $default;
            }
        }
        $this->_height = $height;

        return $this;
    }


    /**
     * @param bool $round
     * @return float
     */
    public function getWidth($round = true)
    {
        if ($round) {
            return round($this->_width);
        }

        return $this->_width;
    }


    /**
     * @param $width
     * @param int $default
     * @return $this
     */
    public function setWidth($width, $default = 200)
    {
        $extensionConfig = $this->_extensionConfig;
//        $boltConfig = $this->BoltConfig;


        if ( !is_numeric($width) ) {


            if (empty($extensionConfig)) {

                $this->_width = $boltConfig;

            } elseif (empty($boltConfig)) {

                $this->_width = $default;

            } else {

                $this->_width = $default;
            }
        }

        $this->_width = $width;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getModifications()
    {
        return $this->modifications;
    }


    /**
     * @param array $modifications
     */
    public function setModifications(array $modifications)
    {
        $this->modifications = $modifications;

    }


    /**
     * @return mixed
     */
    public function getWidthDensity()
    {
        return $this->widthDensity;
    }



}