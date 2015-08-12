<? if (!empty($courses)) : ?>
    <table class="default">
        <caption><?= _('Meine Favoriten') ?></caption>
        <thead>
            <tr>
                <th colspan="3"><?= _('Name') ?></th>
                <th colspan="3"><?= _('Inhalt') ?></th>
            </tr>
        </thead>
        <tbody>
        <?= $this->render_partial($app_factory->open('my_courses/_course.php'), array(
                'course_collection' => $courses,
        )) ?>
        </tbody>
    </table>
<? endif ?>
