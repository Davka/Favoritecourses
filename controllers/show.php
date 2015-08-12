<?php

class ShowController extends StudipController
{

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;
    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setTitle($this->plugin->getName());

        if (Request::isXhr()) {
            $this->set_content_type('text/html;charset=windows-1252');
            $this->set_layout(null);
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base.php'));
        }
    }
    
    public function after_filter($action, $args)
    {
        $title = PageLayout::getTitle();
        if ($title) {
            $this->response->add_header('X-Title', $title);
        }
        
        parent::after_filter($action, $args);
    }

    public function index_action()
    {
        $actions = new ActionsWidget();
        $actions->addLink(_('Einstellungen'), $this->url_for('show/settings'), 'icons/16/blue/tools.png')->asDialog();
        Sidebar::Get()->addWidget($actions);

        $options = new OptionsWidget();
        $params =  $this->plugin->start_page == 'yes' ? array('cancel' => true) : array('really' => true);
        $options->addCheckbox(_('Als Startseite verwenden'), $this->plugin->start_page == 'yes', $this->url_for('show/set_startpage', $params));
        Sidebar::Get()->addWidget($options);

        $favorites = UserConfig::get($GLOBALS['user']->id)->FAVORITE_COURSES;
        $favorites = json_decode($favorites);
        if ($favorites) {
            $this->courses = $this->prepareCourses($favorites);
        } else {
            PageLayout::postMessage(MessageBox::info(_('Sie haben noch keine Favoriten eingestellt. Sie können diese in den Einstellungen festlegen!')));
        }
    }

    public function settings_action()
    {
        PageLayout::setTitle(_('Favoriten - Einstellungen'));

        $favorites = UserConfig::get($GLOBALS['user']->id)->FAVORITE_COURSES;
        if ($favorites) {
            $this->ids = json_decode($favorites);
        } else {
            $this->ids = array();
        }

        $this->courses = $this->getCourses();
    }

    public function set_startpage_action()
    {
        if(Request::get('really')) {
            UserConfig::get($GLOBALS['user']->id)->store('FAVORITE_COURSES_START_PAGE', 'yes');
            $this->redirect('show/index');
            return;
        } else {
            UserConfig::get($GLOBALS['user']->id)->store('FAVORITE_COURSES_START_PAGE', 'no');
            $this->redirect(URLHelper::getLink('dispatch.php/my_courses'));
            return;
        }
    }

    public function save_settings_action()
    {
        CSRFProtection::verifyRequest();
        $favorites = Request::getArray('favorites');
        $favorites = json_encode($favorites);
        UserConfig::get($GLOBALS['user']->id)->store('FAVORITE_COURSES', $favorites);
        PageLayout::postMessage(MessageBox::success(_('Ihre Favoritenauswahl wurde erfolgreich gespeichert!')));
        $this->redirect('show/index');
    }

    public function prepareCourses($ids)
    {
        $courses = array();
        $param_array = 'name seminar_id visible veranstaltungsnummer start_time duration_time status visible ';
        $param_array .= 'chdate admission_binding modules admission_prelim';
        $modules = new Modules();
        $member_ships = User::findCurrent()->course_memberships->toGroupedArray('seminar_id', 'status gruppe');
        foreach ($ids as $id) {
            $course = Course::find($id);
            // export object to array for simple handling
            $_course = $course->toArray($param_array);
            $_course['start_semester'] = $course->start_semester->name;
            $_course['end_semester'] = $course->end_semester->name;
            $_course['sem_class'] = $course->getSemClass();
            $_course['obj_type'] = 'sem';
            $_course['sem_tree'] = $course->study_areas->toArray();
            $_course['last_visitdate'] = object_get_visit($course->id, 'sem', 'last');
            $_course['visitdate'] = object_get_visit($course->id, 'sem', '');

            $user_status = @$member_ships[$course->id]['status'];
            if(!$user_status && Config::get()->DEPUTIES_ENABLE && isDeputy($GLOBALS['user']->id, $course->id)) {
                $user_status = 'dozent';
                $is_deputy = true;
            } else {
                $is_deputy = false;
            }

            $_course['user_status'] = $user_status;
            $_course['gruppe'] = !$is_deputy ? @$member_ships[$course->id]['gruppe'] : MyRealmModel::getDeputieGroup($course->id);
            $_course['modules'] = $modules->getLocalModules($course->id, 'sem', $course->modules, $course->status);
            $_course['name'] = $course->name;
            $_course['temp_name'] = $course->name;
            $_course['is_deputy'] = $is_deputy;
            if ($course->duration_time != 0 && !$course->getSemClass()->offsetGet('studygroup_mode')) {
                $_course['name'] .= ' (' . $course->getFullname('sem-duration-name') . ')';
            }
            MyRealmModel::getObjectValues($_course);
            $courses[] = $_course;
        }
        return $courses;
    }

    public function getCourses()
    {
        $semesters = array_reverse(SemesterData::GetSemesterArray());
        $courses = array();
        $_ids = array();
        foreach ($semesters as $sem) {
            $cm = DBManager::get()->fetchAll("SELECT seminar_user.*, seminare.Name as course_name
                             FROM seminar_user
                             LEFT JOIN seminare USING (seminar_id)
                             WHERE user_id = ? AND (seminare.start_time >= ? AND (seminare.start_time + seminare.duration_time) <= ?)
                             ORDER BY seminare.Name",
                array($GLOBALS['user']->id, $sem['beginn'], $sem['ende']),
                __CLASS__ . '::buildExisting');
            if (!empty($cm)) {
                array_walk($cm, function ($a) use (&$_ids) {
                    if (!in_array($a['Seminar_id'], $_ids)) {
                        $_ids[] = $a['Seminar_id'];
                    }
                });
                $courses[$sem['name']]['courses'] = $cm;
            }
        }

        if (!empty($ids)) {
            $cm = DBManager::get()->fetchAll("SELECT seminar_user.*, seminare.Name as course_name
                             FROM seminar_user
                             LEFT JOIN seminare USING (seminar_id)
                             WHERE user_id = ? AND seminar_id NOT IN (?)
                             ORDER BY seminare.Name",
                array($GLOBALS['user']->id, $_ids),
                __CLASS__ . '::buildExisting');

            if (!empty($cm)) {
                array_walk($cm, function ($a) use (&$_ids) {
                    if (!in_array($a['Seminar_id'], $_ids)) {
                        $_ids[] = $a['Seminar_id'];
                    }
                });
                $courses['unbegrenzt laufende']['courses'] = $cm;
            }
        }

        return $courses;
    }

    // customized #url_for for plugins
    function url_for($to)
    {
        $args = func_get_args();

        # find params
        $params = array();
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        # urlencode all but the first argument
        $args = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->dispatcher->plugin, $params, join('/', $args));
    }
}
