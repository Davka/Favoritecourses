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

        if ($GLOBALS['perm']->have_perm('admin')) {
            return;
        }

        $start_page = UserConfig::get($GLOBALS['user']->id)->FAVORITE_COURSES_START_PAGE;
        if ($start_page == '') {
            $question = createQuestion(_('Wollen Sie die Favoritenliste als Startseite einstellen?'),
                                       array('really' => true),
                                       array('cancel' => true),
                                       PluginEngine::getLink($this, array(), 'show/set_startpage'));
            PageLayout::addBodyElements($question);
        }

        if (Navigation::hasItem('/browse')) {
            $navigation = new Navigation($this->getName());
            $navigation->setURL(PluginEngine::GetURL($this, array(), 'show/index'));

            if ($start_page == 'yes') {
                Navigation::insertItem('/browse/fav_courses', $navigation, 'my_courses');
                Navigation::getItem('/browse')->setURL($navigation->getURL());
            } else {
                Navigation::addItem('/browse/fav_courses', $navigation);
            }
        }
    }

    public function getName()
    {
        return _('Meine Favoriten');
    }

    public function perform($unconsumed_path)
    {
        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, array(), null), '/'),
            'show'
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }
}
