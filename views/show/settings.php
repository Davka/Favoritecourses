<form action="<?= $controller->url_for('show/save_settings')?>" method="post">
    <?=CSRFProtection::tokenTag()?>

    <table class="default">
        <colgroup>
            <col width="20px">
            <col>
        </colgroup>
    <? foreach ($courses as $sem => $courses): ?>
        <tbody id="sem-<?= md5($sem) ?>">
            <tr>
                <th>
                    <input type="checkbox" data-proxyfor="#sem-<?= md5($sem) ?> td :checkbox">
                </th>
                <th><?= htmlReady($sem) ?></th>
            </tr>
        <? foreach ($courses as $course): ?>
            <tr>
                <td><input type="checkbox" name="favorites[]" value="<?= $course['Seminar_id']?>" <?= in_array($course['Seminar_id'], $ids) ? 'checked' : ''?> /></td>
                <td><?= htmlReady($course['course_name']) ?></td>
            </tr>
        <? endforeach; ?>
        </tbody>
    <? endforeach; ?>
    </table>

    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Auswahl speichern'), 'save_courses')?>
    </div>
</form>