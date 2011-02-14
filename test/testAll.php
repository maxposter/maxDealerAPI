<?php
require_once(dirname(__FILE__).'/lib/unit.php');
require_once(dirname(__FILE__).'/lib/symfony/sfFinder.class.php');

$h = new lime_harness(new lime_output_color());
$h->base_dir = $test_path.'/unit';

// register unit tests
$finder = sfFinder::type('file')->follow_link()->name('*Test.php');
$h->register($finder->in($h->base_dir));

$h->run();