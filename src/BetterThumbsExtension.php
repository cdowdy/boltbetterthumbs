<?php

namespace Bolt\Extension\cdowdy\betterthumbs;

//use Bolt\Application;

use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Bolt\Extension\cdowdy\betterthumbs\Controller\BetterThumbsBackendController;
//use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;
use Bolt\Extension\SimpleExtension;
use Bolt\Filesystem as BoltFilesystem;

use Silex\Application;

use Bolt\Menu\MenuEntry;


use Bolt\Extension\cdowdy\betterthumbs\Controller\BetterThumbsController;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\Thumbnail;
use Bolt\Extension\cdowdy\betterthumbs\Handler\SrcsetHandler;
use Bolt\Extension\cdowdy\betterthumbs\Providers\BetterThumbsProvider;


use Pimple as Container;



/**
 * BetterThumbs extension class.
 *
 * @author Cory Dowdy <cory@corydowdy.com>
 */
class BetterThumbsExtension extends SimpleExtension
{

    /**
     * @var string
     */
    private $_currentPictureFill = '3.0.2';
    /**
     * @var bool
     */
    private $_scriptAdded = FALSE;

    /**
     * @return array
     */
    protected function registerFrontendControllers()
    {
        $app = $this->getContainer();
        $config = $this->getConfig();
        return [
            '/img' => new BetterThumbsController($config),

        ];
    }

    /**
     * @return array
     * backend controller for docs page
     */
    protected function registerBackendControllers()
    {
        $config = $this->getConfig();

        return [
            '/extend/betterthumbs' => new BetterThumbsBackendController($config),
        ];
    }

    /**
     * @return array
     */
    protected function registerMenuEntries()
    {
        $menu = new MenuEntry('betterthumbs-menu', 'betterthumbs/files');
        $menu->setLabel('BetterThumbs Files')
            ->setIcon('fa:image')
            ->setPermission('settings')
        ;
        $menu2 = new MenuEntry('betterthumbs-docs', 'betterthumbs/docs');

        $menu2->setLabel('BetterThumbs Docs')
            ->setIcon('fa:file-text-o')
            ->setPermission('settings')
        ;

        return [
            $menu, $menu2
        ];
    }


    /**
     * @return array
     */
    public function getServiceProviders()
    {
        return [
            $this,
            new BetterThumbsProvider($this->getConfig()),
        ];
    }

    /**
     * @param Container $container
     * @return array
     */
    protected function registerNutCommands(Container $container)
    {
        return [
            new Nut\BetterThumbsCommand($container),
//            new Nut\BetterThumbsCachePrimeCommand($container),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return ['templates'];
    }


    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        $options = ['is_safe' => ['html']];
        $this->getConfig();
        return [
            'img' => ['image',  $options ],
            'bthumb' => ['single', $options ],
//            'picture' => ['picture', $options ],

        ];
    }


    /**
     * @param $file
     * @param string $name
     * @param array $options
     * @return \Twig_Markup
     */
    public function image($file, $name = 'betterthumbs', array $options = [])
    {
        $app = $this->getContainer();
        $config = $this->getConfig();

        $this->addAssets();

        $configName = $this->getNamedConfig($name);

        // if the image isn't found return bolt's 404 image
        // set the width the the first width in the presets array
        $sourceExists = $app['betterthumbs']->sourceFileExists($file);
        $notFoundImg = $this->notFoundImage();
        $notFoundSize = $this->imageNotFoundParams();

        // Modifications from config merged with presets set in the config
        $mods = $this->getModificationParams($configName);
        // if there is template modifications place those in the mods array
        if (isset($options['modifications'])) {
            $finalMods = array_replace_recursive($mods, $options['modifications']);
        } else {
            $finalMods = $mods;
        }

        // get our options and merge them with ones passed from the template
        $defaultsMerged = $this->getOptions($file, $configName, $options);
        // classes merged from template
        $mergedClasses = $defaultsMerged['class'];
        $htmlClass = $this->optionToArray($mergedClasses);
        // ID passed from config merged with the template
        $id = $defaultsMerged['id'];
        // if any data-attriubtes are set print em out
        $dataAttributes = $defaultsMerged['data_attrib'];
        // alt text mergd from the twig template
        $altText = $defaultsMerged['altText'];
        // width denisty merged from the twig template
        $widthDensity = $defaultsMerged['widthDensity'];
        // sizes attribute merged from the twig template and made sure it's an array
        $sizesAttrib = $this->optionToArray($defaultsMerged['sizes']);
        // get the resolutions passed in from our config file
        $resolutions = $defaultsMerged['resolutions'];


        // the 'src' image parameters. get the first modifications in the first array
        $srcImgParams = current($finalMods);

        $srcImg = $this->buildThumb($config, $configName, $file, $srcImgParams, $altText);

        $thumb = $this->buildSrcset($file, $config, $configName, $widthDensity, $resolutions, $finalMods);

        $context = [
            'srcImg' => $srcImg,
            'srcset' => $thumb,
            'widthDensity' => $widthDensity,
            'classes' => $htmlClass,
            'id' => $id,
            'dataAttributes' => $dataAttributes,
            'altText' => $altText,
            'sizes' => $sizesAttrib,
            'sourceExists' => $sourceExists,
            'notFoundSize' => $notFoundSize,
            'notFoundImg' => $notFoundImg,
        ];
        // TODO: put the srcset.thumb.html template back in before commit
        $renderTemplate = $this->renderTemplate('srcset.thumb.html.twig', $context);

        return new \Twig_Markup($renderTemplate, 'UTF-8');
    }

    public function single($file, $name = 'betterthumbs', array $options = [])
    {
        $app = $this->getContainer();
        $config = $this->getConfig();

        $configName = $this->getNamedConfig($name);

        // if the image isn't found return bolt's 404 image
        // set the width the the first width in the presets array
        $sourceExists = $app['betterthumbs']->sourceFileExists($file);
        $notFoundImg = $this->notFoundImage();
        $notFoundSize = $this->imageNotFoundParams();

        // Modifications from config merged with presets set in the config
//        $mods = $this->getModificationParams($configName);
        $mods = $config[$configName];
        // if there is template modifications place those in the mods array
        if (isset($options)) {
            $finalMods = array_replace_recursive($mods, $options);
        } else {
            $finalMods = $mods;
        }


        // This will create our fallback/src img, set alt text, classes, source image
        $thumbnail = new Thumbnail($config, $configName);

        // set our source image for the src image, set the modifications for this image and finally set the
        // alt text for the entire image element
        $thumbnail->setSourceImage($file)
            ->setModifications($finalMods);

        // create our src image secure URL
        $srcImg = $thumbnail->buildSecureURL();

        $context = [
            'srcImg' => $srcImg,
            'sourceExists' => $sourceExists,
            'notFoundSize' => $notFoundSize,
            'notFoundImg' => $notFoundImg,
            'mods' => $mods,
        ];

        $renderTemplate = $this->renderTemplate('single.html.twig', $context);

        return new \Twig_Markup($renderTemplate, 'UTF-8');
    }


    /**
     * merge two arrays then sort them by their values
     * default sort is numerically
     * ex:
     *  $a = [ 'file.jpg' => '100w', 'file2.jpg' => '300w' ];
     *  $b = ['file-to-add.jpg' => '200w' ];
     *  result: array( 'file.jpg' => '100w', 'file-to-add.jpg' => '200w', 'file2.jpg' => '300w' );
     *
     * @param $firstArray
     * @param $secondArray
     * @param int $sortType
     * @return array
     */
    protected function mergeAndSort( $firstArray, $secondArray, $sortType = SORT_NUMERIC)
    {
        $finalArray = array_merge($firstArray, $secondArray);
        asort($finalArray, $sortType);
        return $finalArray;
    }


    protected function imageNotFoundParams()
    {
        $config = $this->getConfig();
        $presets = $this->flatten_array($config['presets'], 'w');

        return $presets[0] ;
    }

    protected function notFoundImage()
    {
        $app = $this->getContainer();
        $extConfig = $this->getConfig();
        // returns the wrong route ?
//        $notFoundImg = $app['config']->get('general/thumbnails/notfound_image');
        $notFoundImg = 'bolt-public/view/img/default_notfound.png';


        if (isset($extConfig['404_Image'])  ) {
            $notFoundImg =  $extConfig['404_Image'];
        }

        return $notFoundImg;
    }


    /**
     * @param $config
     * @param $configName
     * @param $file
     * @param $params
     * @param $alt
     * @return string
     *
     * build a src image candidate thumbnail
     */
    public function buildThumb($config, $configName, $file, $params, $alt)
    {
        // This will create our fallback/src img, set alt text, classes, source image
        $thumbnail = new Thumbnail($config, $configName);

        // set our source image for the src image, set the modifications for this image and finally set the
        // alt text for the entire image element
        $thumbnail->setSourceImage($file)
            ->setModifications($params)
            ->setAltText($alt);

        // create our src image secure URL
        return $thumbnail->buildSecureURL();
    }


    /**
     * @param $file
     * @param $config
     * @param $configName
     * @param $widthDensity
     * @param $resolutions
     * @param $finalModifications
     * @return array
     *
     * Build our srcset array check if the original file is used, if so place remove the appropriate
     * srcset candidate and replace it with the original file in the correct spot in the srcset array
     */

    public function buildSrcset($file, $config, $configName, $widthDensity, $resolutions, $finalModifications)
    {
        // get our srcset handler
        $srcsetHandler = new SrcsetHandler($config, $configName);

        // set the width density passed from our config
        $srcsetHandler->setWidthDensity($widthDensity);

        // check to make sure widths are set, if not fall back to our preset widths
        $optionWidths = $this->checkWidths($configName, $finalModifications);

        // build our srcset string with our srcset handler
        $srcsetThumbs = $srcsetHandler->createSrcset($file, $optionWidths, $resolutions, $finalModifications);

        // check the config see if 'use_original' and a widthdensity is set
        $useOriginal = $this->useOriginal($configName, $file,  $widthDensity);


        return $this->mergeAndSort($srcsetThumbs, $useOriginal);
    }


    /**
     * @param $option
     * @param $optionType
     * @param $fallback
     * @return mixed
     */
    protected function checkIndex( $option, $optionType, $fallback )
    {
        return ( isset( $option[$optionType]) ? $option[$optionType] : $fallback );
    }


    /**
     * @param $option
     * @return array
     * take the option passed in from the template. Check if its in an array.
     * if its not an array make it one.
     * also check to make sure there is actual data in the array with array_filter
     * we only want to print a class if there is something actually there.
     */
    protected function optionToArray( $option ) {
        // check if the option that we need to be an array is in fact in an array
        $isArray = is_array($option) ? $option : array($option);

        // return the array and make sure it is not empty
        return array_filter($isArray);

    }


    /**
     * @param $namedConfig
     * @param $finalModsWidths
     * @return array
     */
    protected function checkWidths($namedConfig, $finalModsWidths )
    {
        $extConfig = $this->getConfig();
        $configName = $this->getNamedConfig($namedConfig);
        /**
         * if modifications are set use those. Otherwise fallback to the presets and use the widths
         */
        if (isset($extConfig[$configName]['modifications']) || array_key_exists('modifications', $extConfig[$configName]) ) {
            return $this->flatten_array($finalModsWidths, 'w');
        } else {
            return $this->flatten_array($extConfig['presets'], 'w');
        }
    }


    /**
     * @param $config
     * @return array gets modification params from the config, merges them if empty with presets
     * gets modification params from the config, merges them if empty with presets
     * and allows them to be merged with options passed in from a template
     * @internal param array $options
     */
    protected function getModificationParams($config)
    {
        $extConfig = $this->getConfig();
        $configName = $this->getNamedConfig($config);
        $presetParams = $extConfig['presets'];

        // if modifications are set in the config use those. If not fallback to the presets
        if (isset($extConfig[$configName]['modifications']) || array_key_exists('modifications', $extConfig[$configName]) ) {

            return $extConfig[$configName]['modifications'];

        } else {

            return $presetParams;
        }

    }



    /**
     * @param $namedconfig
     * @param $filename
     * @return mixed|string
     */
    protected function setAltText($namedconfig, $filename)
    {

        $configName = $this->getNamedConfig($namedconfig);
        $configFile = $this->getConfig();

        $altText = $this->checkIndex($configFile[$configName], 'altText', NULL);


        if ($altText == '~') {
            $altText = '' ;
        } elseif (empty($altText)) {
            $tempAltText = pathinfo($filename);
            $altText = $tempAltText[ 'filename' ];
        } else {
            $altText = $configFile[$configName]['altText'];
        }

        return $altText;
    }

    /**
     * passthrough to use original image to prevent image modifications
     * @param $configName
     * @param $file
     * @param $widthDensity
     * @return array
     */
    protected function useOriginal( $configName, $file, $widthDensity )
    {
        $originalFile = [];
        $extConfig = $this->getConfig();
        $namedConfig = $this->getNamedConfig($configName);
//        $useOriginal = $extConfig[$configName]['use_original'];

        if (isset($extConfig[$configName]['use_original']) ) {

            $originalFile = [
                '/files/'. $file  => $extConfig[$namedConfig]['use_original'] . $widthDensity
            ];
        }

        return array_filter($originalFile);
    }

    /**
     * @param $configName
     * @param string $default
     * @return string
     */
    protected function checkWidthDensity($configName, $default = 'w' )
    {
        $extConfig = $this->getConfig();
        $namedConfig = $this->getNamedConfig($configName);
//        $valid = [ 'w', 'x', 'd' ];
        $widthDensity = isset($extConfig[$namedConfig][ 'widthDensity' ]);

        if (isset($widthDensity) && !empty($widthDensity)) {
            $wd = strtolower($extConfig[$namedConfig][ 'widthDensity' ]);

            if ($wd == 'd' ) {
                $wd = 'x';
            }

        } else {
            $wd = $default;
        }
        return $wd;
    }

    /**
     * @param $config
     * @return array
     */
    protected function setSizesAttrib($config)
    {
        $configName = $this->getNamedConfig($config);
        $config = $this->getConfig();

        $sizesAttrib =  isset( $config[$configName]['sizes']) ? $config[$configName]['sizes'] : ['100vw'];

        return $sizesAttrib;
    }


    /**
     * @param $filename
     * @param $config
     * @param array $options
     * @return array
     */
    protected function getOptions($filename, $config, $options =[])
    {

        $configName = $this->getNamedConfig($config);
        $config = $this->getConfig();
        $srcsetHandler = new SrcsetHandler($config, $configName);

        $altText = $this->setAltText($configName, $filename);
        $class = $this->addClassId($configName, 'class');
        $id = $this->addClassId($configName, 'id');
        $dataAttributes = $this->addClassId($configName, 'data_attrib');
        $sizes = $srcsetHandler->getSizesAttrib($configName);
        $defaultRes = $srcsetHandler->getResolutions();
        $widthDensity = $this->checkWidthDensity($configName);

        $defaults = [
            'widthDensity' => $widthDensity,
            'resolutions' => $defaultRes,
            'sizes' => $sizes,
            'altText' => $altText,
            'class' => $class,
            'id' => $id,
            'data_attrib' => $dataAttributes,
        ];

        $defOptions = array_merge($defaults, $options);

        return $defOptions;
    }


    /**
     * @param $namedConfig
     * @param $optionType
     * @return array|string
     *
     * adds any classes, ID's or data-attributes passed in from the template
     */
    protected function addClassId( $namedConfig, $optionType )
    {
        $configName = $this->getNamedConfig($namedConfig);
        $config = $this->getConfig();

        $typeToAdd = $this->checkIndex( $config[$configName], $optionType, NULL );

        if (is_array( $typeToAdd ) ) {
            $trimmedType = array_map( 'trim', $typeToAdd );
        } else {
            $trimmedType = trim($typeToAdd);
        }

        return $trimmedType;
    }


    /**
     * @param $name
     * @return mixed
     *
     * get a "named config" from the extensions config file
     */
    protected function getNamedConfig($name)
    {

        if (empty( $name ) ) {
            $configName = 'betterthumbs';
        } else {
            $configName = $name ;
        }

        return  $configName ;
    }


    /**
     * Flatten The multidimensional array in the extensions config under Presets.
     *
     * @param array $array
     * @param string $fallbackOption
     * @return array
     */
    protected function flatten_array(array $array, $fallbackOption)
    {

        $fallback = [];

            foreach ($array as $key => $value) {
                if (array_key_exists($fallbackOption, $value )) {
                    $fallback[] = $value[$fallbackOption];
                } else {
                    $fallback[] = 0;
                }

            }

        return $fallback;
    }



    /**
     * You can't rely on bolts methods to insert javascript/css in the location you want.
     * So we have to hack around it. Use the Snippet Class with their location methods and insert
     * Picturefill into the head. Add a check to make sure the script isn't loaded more than once ($_scriptAdded)
     * and stop the insertion of the files multiple times because bolt's registerAssets method will blindly insert
     * the files on every page
     *
     */

    protected function addAssets()
    {
        $app = $this->getContainer();

        $config = $this->getConfig();

        $pfill = $config['picturefill'];

        $extPath = $app['resources']->getUrl('extensions');

        $vendor = 'vendor/cdowdy/';
        $extName = 'betterthumbs/';

        $pictureFillJS = $extPath . $vendor . $extName . 'picturefill/' . $this->_currentPictureFill . '/picturefill.min.js';
        $pictureFill = <<<PFILL
<script src="{$pictureFillJS}" async defer></script>
PFILL;
        $asset = new Snippet();
        $asset->setCallback($pictureFill)
            ->setZone(ZONE::FRONTEND)
            ->setLocation(Target::AFTER_HEAD_CSS);


        // add picturefill script only once for each time the extension is used
        if ($pfill){
            if ($this->_scriptAdded == FALSE ) {
                $app['asset.queue.snippet']->add($asset);
                $this->_scriptAdded = TRUE;
            } else {

                $this->_scriptAdded = TRUE;
            }
        }
    }



    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
            'Image_Driver' => 'gd',
            'Filesystem' => [
                'adapter' => 'local',
            ],
            'security' => [
                'secure_thumbs' => true,
                'secure_sign_key' => ''
            ],
            'defaults' => [ 'q' => 80 ],
            'presets' => [
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
            ],
            'betterthumbs' => [
                'save_data' => FALSE,
                'altText' => '',
                'widthDensity' => 'w',
                'sizes' => [ '100vw'],
                'modifications' => [
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
                ],
            ],

        ];
    }

    /**
     * @return bool
     */
    public function isSafe()
    {
        return true;
    }


}
