<?php
require 'bootstrap.php';

/**
 * FavoritecourcesPlugin.class.php
 *
 * ...
 *
 * @author  David Siegfried <david.siegfried@uni-vechta.de>
 * @version 0.1a
 */
class FavoritecourcesPlugin extends StudIPPlugin implements SystemPlugin
{

    public function __construct()
    {
        parent::__construct();
        if (Navigation::hasItem('/browse')) {
            $navigation = new AutoNavigation($this->getName());
            $navigation->setURL(PluginEngine::GetURL($this, array(), 'show/index'));
            Navigation::addItem('/browse/fav_courses', $navigation);
        }
    }

    public function getName()
    {
        return _('Meine Favoriten');
    }

    public function initialize()
    {
        PageLayout::addStylesheet($this->getPluginURL() . '/assets/style.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/application.js');
    }

    public function perform($unconsumed_path)
    {
        $this->setupAutoload();
        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, array(), null), '/'),
            'show'
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }

    private function setupAutoload()
    {
        if (class_exists('StudipAutoloader')) {
            StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
        } else {
            spl_autoload_register(function ($class) {
                include_once __DIR__ . $class . '.php';
            });
        }
    }
}
