<? if (!empty($courses)) : ?>
    <table class="default">
        <caption><?= _('Meine Favoriten') ?></caption>
        <thead>
        <tr>
            <th colspan="3"><?= _('Name') ?></th>
            <th colspan="2"><?= _('Inhalt') ?></th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($courses as $course)  : ?>
            <? $sem_class = $course['sem_class']; ?>
            <tr>
                <td class="gruppe<?= $course['gruppe'] ?>"></td>
                <td>
                    <? if ($sem_class['studygroup_mode']) : ?>
                        <?=
                        StudygroupAvatar::getAvatar($course['seminar_id'])->is_customized()
                            ? StudygroupAvatar::getAvatar($course['seminar_id'])->getImageTag(Avatar::SMALL, tooltip2($course['name']))
                            : Assets::img('icons/20/blue/studygroup.png', tooltip2($course['name'])) ?>
                    <? else : ?>
                        <?=
                        CourseAvatar::getAvatar($course['seminar_id'])->is_customized()
                            ? CourseAvatar::getAvatar($course['seminar_id'])->getImageTag(Avatar::SMALL, tooltip2($course['name']))
                            : Assets::img('icons/20/blue/seminar.png', tooltip2($course['name'])) ?>
                    <? endif ?>
                </td>
                <? if ($config_sem_number) : ?>
                    <td><?= $course['veranstaltungsnummer'] ?></td>
                <? endif ?>
                <td style="text-align: left">
                    <a href="<?= URLHelper::getLink('seminar_main.php', array('auswahl' => $course['seminar_id'])) ?>"
                        <?= $course['visitdate'] <= $course['chdate'] ? 'style="color: red;"' : '' ?>>
                        <?= htmlReady($course['name']) ?>
                        <?= ($course['is_deputy'] ? ' ' . _("[Vertretung]") : ''); ?>
                    </a>
                    <? if ($course['visible'] == 0) : ?>
                        <? $infotext = _("Versteckte Veranstaltungen können über die Suchfunktionen nicht gefunden werden."); ?>
                        <? $infotext .= " "; ?>
                        <? if (Config::get()->ALLOW_DOZENT_VISIBILITY) : ?>
                            <? $infotext .= _("Um die Veranstaltung sichtbar zu machen, wählen Sie den Punkt \"Sichtbarkeit\" im Administrationsbereich der Veranstaltung."); ?>
                        <? else : ?>
                            <? $infotext .= _("Um die Veranstaltung sichtbar zu machen, wenden Sie sich an eineN der zuständigen AdministratorInnen."); ?>
                        <? endif ?>
                        <?= _("[versteckt]") ?>
                        <?= tooltipicon($infotext) ?>
                    <? endif ?>
                </td>
                <td>
                    <? if (!$sem_class['studygroup_mode']) : ?>
                        <a data-dialog="size=50%"
                           href="<?= URLHelper::getLink('dispatch.php/course/details/index/' . $course['seminar_id']) ?>">
                            <? $params = tooltip2(_("Veranstaltungsdetails")); ?>
                            <? $params['style'] = 'cursor: pointer'; ?>
                            <?= Assets::img('icons/20/grey/info-circle.png', $params) ?>
                        </a>
                    <? else : ?>
                        <?= Assets::img('blank.gif', array('width' => 20, 'height' => 20)); ?>
                    <? endif ?>
                </td>
                <td style="text-align: left; white-space: nowrap;">
                    <? if (!empty($course['navigation'])) : ?>
                        <? foreach (MyRealmModel::array_rtrim($course['navigation']) as $key => $nav)  : ?>
                            <? if (isset($nav) && $nav->isVisible(true)) : ?>
                                <? $image = $nav->getImage(); ?>
                                <a href="<?=
                                UrlHelper::getLink('seminar_main.php',
                                    array('auswahl'     => $course['seminar_id'],
                                          'redirect_to' => strtr($nav->getURL(), '?', '&')
                                    )) ?>" <?= $nav->hasBadgeNumber() ? 'class="badge" data-badge-number="' . intval($nav->getBadgeNumber()) . '"' : '' ?>>
                                    <?= Assets::img($image['src'], array_map("htmlready", $image)) ?>
                                </a>
                            <? elseif (is_string($key)) : ?>
                                <?=
                                Assets::img('blank.gif', array('width'  => 20,
                                                               'height' => 20
                                )); ?>
                            <? endif ?>
                            <? echo ' ' ?>
                        <? endforeach ?>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>

    </table>
<? endif ?>
