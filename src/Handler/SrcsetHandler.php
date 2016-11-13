<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Handler;

use Silex\Application;

class SrcsetHandler
{
    private $app;

    /**
     * @var array
     */
    protected $_extensionConfig;

    /**
     * SrcsetHandler constructor.
     * @param array $_extensionConfig
     */
    public function __construct(array $_extensionConfig)
    {
        $this->_extensionConfig = $_extensionConfig;
    }


    protected function getImageOptions( $filename, $config, array $options )
    {

    }

    // get all the modifications being done to the image and pass them to / create an array
    protected function setImageModifications(array $modifications = [] )
    {

    }

    public function getSizesAttrib($configName, $default = ['100vw'] )
    {
        $sizes = isset( $this->_extensionConfig[$configName]['sizes'])
            ? $this->_extensionConfig[$configName]['sizes']
            : $default;

        return $sizes;
    }


    public function getWidthDensity($configName, $default = 'w')
    {
        $valid = [ 'w', 'x', 'd' ];
        $widthDensity = isset($this->_extensionConfig[$configName][ 'widthDensity' ]);

        if (isset($widthDensity) && !empty($widthDensity)) {
            $wd = strtolower($this->_extensionConfig[$configName][ 'widthDensity' ]);

            if ($wd == 'd' ) {
                $wd = 'x';
            }

        } else {
            $wd = $default;
        }

        return $wd;
    }


    public function getResolutions($configName, $defaultResolutions = [ 1, 2, 3 ])
    {

        $resolutions = isset( $this->_extensionConfig[$configName]['resolutions']) ? $this->_extensionConfig[$configName]['resolutions'] : $defaultResolutions;

        return $resolutions;
    }

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
        return $resError;
    }

}