<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Helpers;

use Silex\Application;


class ConfigHelper
{
    /**
     * @var array
     */
    protected $_extensionConfig;

    /**
     * @var
     */
    protected $_signKey;

    protected $_defaults;

    protected $_presets;

    protected $_max_image_size;

    protected $_notFoundEnabled;

    protected $_ErrorImagePath;

    protected $_cacheAdapter;


    /**
     * ConfigHelper constructor.
     * @param array $_extensionConfig
     */
    public function __construct(array $_extensionConfig)
    {
        $this->_extensionConfig = $_extensionConfig;
    }

    /**
     * Set the Image Driver to be used. Either 'gd' or 'imagick'
     * @return mixed|string
     */
    public function setImageDriver( )
    {
        $ConfigDriver = $this->_extensionConfig['Image_Driver'];
        $expectedDrivers = [ 'gd', 'imagick', 'imagemagick' ];

        // if the driver passed from the config is empty make it 'gd'
        if (empty($ConfigDriver)) {

            $imageDriver = 'gd';

        } else {

            $imageDriver = strtolower($ConfigDriver);

        }

        // check to make sure the image driver is in the expectedDrivers array
        // then return that value
        if (in_array($imageDriver, $expectedDrivers ) ) {
            if ($imageDriver === 'imagemagick') {
                $imageDriver = 'imagick';
            }
            return $imageDriver;
            // if it isn't and it's some unsupported driver just make it 'gd'
        } else {

            $imageDriver = $expectedDrivers[0];
        }

        return $imageDriver;
    }


    /**
     * @return mixed
     */
    public function setSignKey()
    {
        $secureKey = $this->_extensionConfig['security']['secure_sign_key'];

        return $secureKey;
    }

    /**
     * @return mixed
     */
    public function getMaxImageSize()
    {
        return $this->_max_image_size;
    }

    /**
     * @param mixed $max_image_size
     */
    public function setMaxImageSize($max_image_size)
    {
        $configMax  = $this->_extensionConfig['security']['max_image_size'];

        if (empty($max_image_size)) {
            $this->_max_image_size = 2000*2000;
        } else {
            $this->_max_image_size = $max_image_size;
        }
    }

    /**
     * @return mixed
     */
    public function getNotFoundEnabled()
    {
        return $this->_notFoundEnabled;
    }

    /**
     * @param mixed $notFoundEnabled
     * @return ConfigHelper
     */
    public function setNotFoundEnabled($notFoundEnabled)
    {
        $this->_notFoundEnabled = $notFoundEnabled;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrorImagePath()
    {
        return $this->_ErrorImagePath;
    }

    /**
     * @param mixed $ErrorImagePath
     * @return ConfigHelper
     */
    public function setErrorImagePath($ErrorImagePath)
    {
        $this->_ErrorImagePath = $ErrorImagePath;
        return $this;
    }




    public function setDefaults()
    {
        $configDefaults = $this->_extensionConfig['defaults'];
        $defaultQuality = ['q' => 80 ];

        if (!empty($configDefaults)) {

            return array_merge( $defaultQuality, $configDefaults );

        } else {

            return $defaultQuality;
        }
    }

    /**
     * @return mixed
     */
    public function getCacheAdapter()
    {
        return $this->_cacheAdapter;
    }

    /**
     * @param mixed $cacheAdapter
     * @return ConfigHelper
     */
    public function setCacheAdapter($cacheAdapter, $default = 'local')
    {
        $valid = ['local', 'memory'];

        if (empty($this->_extensionConfig['Filesystem']['adapter'])) {
            $cacheAdapter = $default;
        } else {
            $cacheAdapter = $this->_extensionConfig['Filesystem']['adapter'];
        }

        $this->_cacheAdapter = $cacheAdapter;

        return $this;
    }

    public function setFilesystemAdapter()
    {
        $adapter = $this->_extensionConfig['Filesystem']['adapter'];

        return $this->setCacheAdapter($adapter)->getCacheAdapter();
    }



    /**
     * @return mixed
     */
    public function setPresets()
    {
        $configPresets = $this->_extensionConfig['presets'];

        $defaultPresets = [
            'small' => [
                'w' => 175,
                'fit' => 'contain'
            ],
            'medium' => [
                'w' => 350,
                'fit' => 'contain'
            ],
            'large' => [
                'w' => 700,
                'fit' => 'contain'
            ],
            'xlarge' => [
                'w' => 1400,
                'fit' => 'stretch'
            ],
        ];

        if (empty($configPresets) ) {
            $configPresets = $defaultPresets;
        }

        return $configPresets;
    }


}