<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Handler;

use Silex\Application;
use League\Glide\Urls\UrlBuilderFactory;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\Thumbnail;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;

class SrcsetHandler
{
    private $app;

    /**
     * @var array
     */
    protected $_extensionConfig;
    protected $_configname;
    /**
     * @var array
     * */
    protected $_sizesAttrib;

    protected $_widthDensity;


    /**
     * SrcsetHandler constructor.
     * @param array $_extensionConfig
     */
    public function __construct(array $_extensionConfig, $configname)
    {
        $this->_extensionConfig = $_extensionConfig;
        $this->_configname = $configname;
    }


    /**
     * @param array $default
     * @return array
     */
    public function getSizesAttrib( $default = ['100vw'] )
    {
        $sizes = isset($this->_extensionConfig[$this->_configname]['sizes'])
            ? $this->_extensionConfig[$this->_configname]['sizes']
            : $default;

        return $sizes;
    }

    /**
     * @return mixed
     */
    public function getWidthDensity()
    {
        return $this->_widthDensity;
    }


    /**
     * @param $widthDensity
     */
    public function setWidthDensity($widthDensity)
    {

        $this->_widthDensity = $widthDensity;

    }


    /**
     * @param array $defaultResolutions
     * @return array
     */
    public function getResolutions($defaultResolutions = [ 1, 2, 3 ])
    {
        $configName = $this->_extensionConfig[$this->_configname];
        $resolutions = isset( $configName['resolutions']) ? $configName['resolutions'] : $defaultResolutions;

        return $resolutions;
    }

    /**
     * @param $thumb
     * @param $resolutions
     * @return array
     */
    public function resolutionErrors($thumb, $resolutions)
    {
        $resError = [];
        $thumbCount = count($thumb);
        $resCount = count($resolutions);
        // if the resolutions are more than the thumbnails remove the resolutions to match the thumbnail array
        if ($resCount > $thumbCount) {

            $newResArray = array_slice($resolutions, 0, $thumbCount);
            $resError = array_combine($thumb, $newResArray);
        }
        // if the resolution count is smaller than the number of thumbnails remove the number of thumbnails
        // to match the $resCount Array
        if ($resCount < $thumbCount) {
            $newThumbArray = array_slice($thumb, 0, $resCount);
            $resError = array_combine($newThumbArray, $resolutions);
        }
        if ($resCount === $thumbCount ) {
            $resError = array_combine( $thumb, $resolutions);
        }

        // walk through the array and add an x to each resolution in the array
        array_walk($resError, function (&$value) {
            $value= $value . 'x';
        });;

        return $resError;
    }


    /**
     * @param $fileName
     * @param $widths
     * @param $resolutions
     * @param array $modifications
     * @return array
     */
    public function createSrcset($fileName, $widths, $resolutions, array $modifications )
    {

        // make thumbs an empty array
        $thumb = [];
        $srcset = [];

        $thumbHelper = new Thumbnail($this->_extensionConfig, $this->_configname);
        $thumbHelper->setSourceImage($fileName);
        $thumbHelper->setModifications($modifications);
        $wd = $this->getWidthDensity();

        // Get our presets from the config
        $configHelper = new ConfigHelper($this->_extensionConfig);
        $presets = array_keys($configHelper->setPresets());


        // postfix all widths with a w
        array_walk($widths, function (&$value) {
            $value= $value . 'w';
        });

        // if modifcations are preset use those
        // otherwise fallback to presets and use the 'p=preset-name' shorthand
        if (isset($this->_extensionConfig[$this->_configname]['modifications']) ||
            array_key_exists('modifications', $this->_extensionConfig[$this->_configname])) {
            foreach ($modifications as $parameters  ) {
                $thumb[] .= $thumbHelper->setModifications($parameters)->buildSecureURL();
            }
        } else {
            foreach ($presets as $preset) {
                $thumb[] .= $thumbHelper->setModifications(['p' => $preset ] )->buildSecureURL();
            }
        }

        // prefix all images with '/img'
        array_walk($thumb, function( &$key ) {
           $key = '/img' . $key;
        });

       // if modifications are empty need to get the widths from presets
        if ($wd === 'w') {
            $srcset =  array_combine($thumb, $widths);
        }

        if ($wd === 'x') {
            $srcset =  $this->resolutionErrors($thumb, $resolutions);
        }
        return $srcset;
    }

}