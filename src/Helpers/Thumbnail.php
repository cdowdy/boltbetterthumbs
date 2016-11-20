<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Helpers;

use Silex\Application;
use League\Glide\Urls\UrlBuilderFactory;

/**
 * Class Thumbnail
 * @package Bolt\Extension\cdowdy\betterthumbs\Helpers\Thumb
 */
class Thumbnail
{
    /**
     * @var array
     */
    protected $_extensionConfig;

    /**
     * @var
     */
    protected $_configName;
    /**
     * @var
     */
    protected $_sourceImage;

    /**
     * @var array
     */
    protected $_classes;

    /**
     * @var
     */
    protected $_altText;

    /**
     * @var
     */
    protected $_height;

    /**
     * @var
     */
    protected $_width;

    /**
     * @var array
     * Modifications done to the image through intervention (crop/filters etc)
     */
    protected $modifications = [];



    /**
     * Thumbnail constructor.
     * @param array $_extensionConfig
     * @param array $_configName
     */
    public function __construct( array $_extensionConfig, $_configName)
    {
        $this->_extensionConfig = $_extensionConfig;
        $this->configName = $_configName;
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
    public function getClasses()
    {
        return $this->_classes;
    }

    /**
     * @param mixed $classes
     */
    public function setClasses(array $classes)
    {
        $this->_classes = $classes;
    }



    /**
     * @return array
     */
    public function getModifications()
    {
        return $this->modifications;
    }

    public function setModifications(array $modifications)
    {

        $this->modifications = $modifications;
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
     * @return string
     * set the "base url" for the Secure URL to '/' since if we use the "base_url" option of '/img/'
     * we get double '/img//img/' in our URL's
     * /img//img/file-name.jpg?s=signature-here
     *
     * We don't want that. We want urls like:
     * /img/file-name.jpg?s=signature-here
     *
     * so in our template for secure urls we need to have '/img{{ img }}'
     *
     * conversely if we set the base url to an empty string '', it has the same result as setting it to '/'
     */
    public function buildSecureURL()
    {
        $signKey = new ConfigHelper($this->_extensionConfig);

        $secureURL = UrlBuilderFactory::create('/', $signKey->setSignKey() );

        return $secureURL->getUrl($this->_sourceImage, $this->modifications);
    }

}