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

        $this->start_page = UserConfig::get($GLOBALS['user']->id)->FAVORITE_COURSES_START_PAGE;
        if ($this->start_page == '' && match_route('dispatch.php/my_courses')) {
            $msg = array(sprintf('<a href="%s">%s</a>', PluginEngine::getLink($this, array('really' => true), 'show/set_startpage'), _('Ja')));
            $msg[] = sprintf('<a href="%s">%s</a>', PluginEngine::getLink($this, array('cancel' => true), 'show/set_startpage'), _('Nein'));
            PageLayout::postMessage(MessageBox::info(_('Wollen Sie die Favoritenliste (Reiter "Meiner Favoriten") als Startseite einstellen?'), $msg));
        }

        if (Navigation::hasItem('/browse')) {
            $navigation = new Navigation($this->getName());
            $navigation->setURL(PluginEngine::GetURL($this, array(), 'show/index'));

            if ($this->start_page == 'yes') {
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
