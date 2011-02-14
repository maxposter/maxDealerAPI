<?php
require_once(dirname(__FILE__).'/../lib/unit.php');
require_once('maxOptionTest.class.php');

$t = new lime_test(4, new lime_output_color());

$tc = new maxOptionTest();

$t->is(
  $tc->getOption('first'),
  'first_value',
  'Option was initialized right.'
);

$tc->setOptions(array('second' => 'second_value', 'third' => 'third_value'));
$t->is(
  $tc->getOptions(),
  array('first' => 'first_value', 'second' => 'second_value', 'third' => 'third_value'),
  'Setter and getter methods work right.'
);

$t->info('getRequiredOption()');
try
{
  $tc->getRequiredOption('fourth');
  $t->fail('Fourth option does not set. Must be thrown exception.');
  $t->pass('Skip one test.');
}
catch (maxException $e)
{
  $t->is($e->getCode(), maxException::ERR_DOES_NOT_SET_REQUIRED_OPTION, 'Fourth option does not set. Thriwn right exception.');
  $t->is($e->getMessage(), 'Не задан обязательный параметр "fourth".', 'Exception has right message.');
}