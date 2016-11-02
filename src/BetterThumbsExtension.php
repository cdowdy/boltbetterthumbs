<?php

namespace Bolt\Extension\cdowdy\betterthumbs;

//use Bolt\Application;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Bolt\Extension\cdowdy\betterthumbs\Controller\BetterThumbsController;
use Bolt\Extension\SimpleExtension;
use Bolt\Filesystem as BoltFilesystem;



/**
 * BoltResponsiveImages extension class.
 *
 * @author Cory Dowdy <cory@corydowdy.com>
 */
class BetterThumbsExtension extends SimpleExtension
{

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

        ];
    }


    /**
     * The callback function when {{ my_twig_function() }} is used in a template.
     *
     * @return string
     */
    public function image( $file  )
    {
        $app = $this->getContainer();


        $context = [
            'img' => $file,
        ];

        $renderTemplate = $this->renderTemplate('thumb.html.twig', $context);

        return new \Twig_Markup($renderTemplate, 'UTF-8');
    }

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
     */
    protected function getDefaultConfig()
    {
        return [
            'default' => [
                'widths' => [ 320, 480, 768 ],
                'heights' => [ 0 ],
                'widthDensity' => 'w',
                'sizes' => [ '100vw'  ],
                'cropping' => 'resize',
                'altText' => '',
                'class' => ''
            ],
            'allowed_types' => [
                'webp',
                'jpeg',
                'jpg',
                'png',
                'gif',
                'jxr'
            ]
        ];
    }

    public function isSafe()
    {
        return true;
    }


}
