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
        $expectedDrivers = [ 'gd', 'imagick' ];

        // if the driver passed from the config is empty make it 'gd'
        if (empty($ConfigDriver)) {

            $imageDriver = 'gd';

        } else {

            $imageDriver = strtolower($ConfigDriver);

        }

        // check to make sure the image driver is in the expectedDrivers array
        // then return that value
        if (in_array($imageDriver, $expectedDrivers ) ) {

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