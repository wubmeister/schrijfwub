<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Form\Form;

$form = Form::factory('myform')->decorate('semantic');

?>
<?= $form->open() ?>

	<?= $form->textField('myfield', 'My field')->decorate('semantic') ?>

<?= $form->close() ?>
