<h1>Theme Options</h1>
<?php
/** @var \App\Elements\Form $form */
echo $form->useRest()->open();

// Hours
$hours = $form->repeater('Company Hours')
    ->setControlAdd('Add Hours')
    ->hideContract()
    ->setFields(
        $form->row(
            $form->text('Day', ['placeholder' => 'Monday, Tuesday, ect.']),
            $form->time('Start Time'),
            $form->time('End Time')
        )
    )
    ->when('company_open');

// About
$about = $form->fieldset('Company', 'Details about your company.', [
    $form->text('Company Name'),
    $form->textexpand('Company About'),
    $form->image('Company Logo'),
    $form->input('Company Email')->setTypeEmail(),
    $form->text('Company Phone'),
    $form->toggle('Company Open')->setText('Company open for business'),
    $hours,
]);

// Links
$links = $form->fieldset('Links', 'Global page links.', [
    $form->search('Terms Page')->setPostTypeOptions('page'),
    $form->search('Privacy Page')->setPostTypeOptions('page'),
]);

// Design
$design = $form->fieldset('Design', 'Site design settings.', [
    $form->swatches('Site Color')->setOptions([
        'Vibrant' => ['#333', '#0073aa'],
        'Colorful' => ['#523f6d', '#a3b745'],
    ])
]);

// API
$help = '<a target="blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">Get Your Google Maps API</a>.';

$api = $form->fieldset('Google APIs', 'Connection for Google Maps.', [
   $form->text('Google Maps API Key')->setHelp($help),
]);

// Save
$save = $form->submit( 'Save Changes' );

// Layout
$tabs = \TypeRocket\Elements\Tabs::new()->setFooter( $save )->layoutLeft();
$tabs->tab('About', 'building', $about)->setDescription('Company information');
$tabs->tab('API', 'admin-generic', $api)->setDescription('Access keys for APIs');
$tabs->tab('Design', 'admin-appearance', $design)->setDescription('Site design settings');
$tabs->tab('Links', 'admin-links', $links)->setDescription('Global page links');
$tabs->render();

echo $form->close();