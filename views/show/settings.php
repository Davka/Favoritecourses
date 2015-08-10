<form action="<?= $controller->url_for('show/save_settings')?>" method="post">
    <?=CSRFProtection::tokenTag()?>
    <table class="default">

        <? foreach ($courses as $sem => $c) : ?>
            <thead>
            <tr>
                <th></th>
                <th><?= htmlReady($sem) ?></th>
            </tr>
            </thead>
            <tbody>
            <? foreach ($c['courses'] as $cm) : ?>

                <tr>
                    <td><input type="checkbox" name="favorites[]" value="<?= $cm['Seminar_id']?>" <?= in_array($cm['Seminar_id'], $ids) ? 'checked' : ''?> /></td>
                    <td><?= htmlReady($cm['course_name']) ?></td>
                </tr>

            <? endforeach ?>
            </tbody>
        <? endforeach ?>
    </table>

    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Auswahl speichern'), 'save_courses')?>
    </div>
</form>