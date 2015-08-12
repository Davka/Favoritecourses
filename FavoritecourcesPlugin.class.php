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

        if(!$GLOBALS['perm']->have_perm('admin')) {
            $start_page = UserConfig::get($GLOBALS['user']->id)->FAVORITE_COURSES_START_PAGE;
            if ($start_page == '') {
                echo createQuestion(_('Wollen Sie die Favoritenliste als Startseite einstellen?'), array('really' => true), array('cancel' => true), PluginEngine::getLink($this, array(), 'show/set_startpage'));
            }

            if (Navigation::hasItem('/browse')) {
                $navigation = new AutoNavigation($this->getName());
                $navigation->setURL(PluginEngine::GetURL($this, array(), 'show/index'));


                if ($start_page == 'yes') {
                    Navigation::insertItem('/browse/fav_courses', $navigation, 'my_courses');
                    Navigation::getItem('/browse')->setURL(PluginEngine::GetURL($this, array(), 'show/index'));
                } else {
                    Navigation::addItem('/browse/fav_courses', $navigation);
                }
            }
            $this->start_page = $start_page;
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
