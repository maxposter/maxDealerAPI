<?php
require_once(dirname(__FILE__).'/../lib/unit.php');

require_once($max_path.'/maxImageResize.php');

$t = new lime_test(7, new lime_output_color());

$t->info('calcResizeParams()');
$tc = new maxImageResize(640, 480);
$t->is(
  $tc->calcResizeParams(120, 90, 1024, 768),
  array(0, 0, 0, 0, 120, 90, 1024, 768),
  'Source photo 4x3 bigger than min size'
);

$t->is(
  $tc->calcResizeParams(120, 90, 640, 480),
  array(0, 0, 0, 0, 120, 90, 640, 480),
  'Source photo 4x3 has size equal min size'
);

$t->is(
  $tc->calcResizeParams(120, 90, 400, 300),
  array(22, 17, 0, 0, 75, 56, 400, 300),        // Результат будет иметь рамку фонового цвета
  'Source photo 4x3 has size less than min size'
);

$t->is(
  $tc->calcResizeParams(120, 90, 1200, 1000),
  array(0, 0, 0, 50, 120, 90, 1200, 900),        // Исходник будет обрезан сверху и снизу на 50 пикселей
  'Source photo has big size but wrong proportion (big height)'
);

$t->is(
  $tc->calcResizeParams(120, 90, 1300, 900),
  array(0, 0, 50, 0, 120, 90, 1200, 900),        // Исходник будет обрезан слева и справа на 50 пикселей
  'Source photo has big size but wrong proportion (big width)'
);

$t->is(
  $tc->calcResizeParams(120, 90, 600, 500),
  array(6, 0, 0, 0, 108, 90, 600, 500),        // Исходник будет сжат без обрезания, а результат сдвинут по X на 6 пикселей
  'Source photo has wrong proportion but height bigger than min heigth'
);

$t->is(
  $tc->calcResizeParams(120, 90, 700, 400),
  array(0, 11, 0, 0, 120, 68, 700, 400),        // Исходник будет сжат без обрезания, а результат сдвинут по Y на 11 пикселя
  'Source photo has wrong proportion but width bigger than min width'
);