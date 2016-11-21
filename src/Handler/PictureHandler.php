<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Handler;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\Thumbnail;


class PictureHandler
{
    /**
     * @var array
     */
    protected $_extensionConfig;

    /**
     * @var string
     */
    protected  $_configName;

    public function __construct(array $_extensionConfig, $_configName)
    {
        $this->_extensionConfig = $_extensionConfig;
        $this->_configname = $_configName;
    }


}