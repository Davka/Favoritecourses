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

        $this->config = UserConfig::get($GLOBALS['user']->id);
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
        Navigation::activateItem('/browse/fav_courses');

        $actions = new ActionsWidget();
        $actions->addLink(_('Einstellungen'), $this->url_for('show/settings'), 'icons/16/blue/tools.png')->asDialog();
        Sidebar::Get()->addWidget($actions);

        $options = new OptionsWidget();
        $params =  $this->plugin->start_page == 'yes' ? array('cancel' => true) : array('really' => true);
        $options->addCheckbox(_('Als Startseite verwenden'), $this->plugin->start_page == 'yes', $this->url_for('show/set_startpage', $params));
        Sidebar::Get()->addWidget($options);

        $favorites = $this->config->FAVORITE_COURSES;
        $favorites = json_decode($favorites);
        if ($favorites) {
            $this->courses = $this->prepareCourses($favorites);
        } else {
            PageLayout::postMessage(MessageBox::info(_('Sie haben noch keine Favoriten eingestellt. Sie können diese in den Einstellungen festlegen!')));
        }

        $this->app_factory = new Flexi_TemplateFactory($GLOBALS['STUDIP_BASE_PATH'] . '/app/views/');
    }

    public function settings_action()
    {
        PageLayout::setTitle(_('Favoriten - Einstellungen'));

        $favorites = $this->config->FAVORITE_COURSES;
        if ($favorites) {
            $this->ids = json_decode($favorites);
        } else {
            $this->ids = array();
        }

        $this->courses = $this->getCourses();
    }

    public function save_settings_action()
    {
        CSRFProtection::verifyRequest();

        $favorites = Request::getArray('favorites');
        $favorites = json_encode($favorites);
        $this->config->store('FAVORITE_COURSES', $favorites);

        PageLayout::postMessage(MessageBox::success(_('Ihre Favoritenauswahl wurde erfolgreich gespeichert!')));
        $this->redirect('show/index');
    }

    public function set_startpage_action()
    {
        if (Request::get('really')) {
            $this->config->store('FAVORITE_COURSES_START_PAGE', 'yes');
            $url = 'show/index';
        } else {
            $this->config->store('FAVORITE_COURSES_START_PAGE', 'no');
            $url = URLHelper::getLink('dispatch.php/my_courses');
        }
        $this->redirect($url);
    }

    protected function prepareCourses($ids)
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
            if (!$user_status && Config::get()->DEPUTIES_ENABLE && isDeputy($GLOBALS['user']->id, $course->id)) {
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

    protected function getCourses()
    {
        $query = "SELECT seminar_user.*, seminare.Name as course_name
                  FROM seminar_user
                  LEFT JOIN seminare USING (seminar_id)
                  WHERE user_id = :user_id
                    AND (seminare.start_time >= :beginn AND (seminare.start_time + seminare.duration_time) <= :ende)
                    AND seminar_id NOT IN (:ids)
                  ORDER BY seminare.Name";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $GLOBALS['user']->id);
        
        $semesters = array_reverse(SemesterData::GetSemesterArray());
        $courses   = array();
        $ids       = array();

        foreach ($semesters as $sem) {
            $statement->bindValue(':beginn', $sem['beginn']);
            $statement->bindValue(':ende', $sem['ende']);
            $statement->bindValue(':ids', $ids ?: '');
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $courses[$sem['name']] = $rows;
                
                $ids = array_merge($ids, array_map(function ($row) {
                    return $row['Seminar_id'];
                }, $rows));
            }
        }

        $query = "SELECT seminar_user.*, seminare.Name as course_name
                  FROM seminar_user
                  LEFT JOIN seminare USING (seminar_id)
                  WHERE user_id = :user_id AND seminar_id NOT IN (:ids)
                  ORDER BY seminare.Name";
        $statement = DBManager::get()->prepare($query);
        $statement->bindValue(':user_id', $GLOBALS['user']->id);
        $statement->bindValue(':ids', $ids ?: '');
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($cm)) {
            $courses['unbegrenzt laufende'] = $rows;
        }

        return $courses;
    }

    // customized #url_for for plugins
    public function url_for($to)
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
