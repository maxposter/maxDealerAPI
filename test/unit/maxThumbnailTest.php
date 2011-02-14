<?php
require_once(dirname(__FILE__).'/../lib/unit.php');

require_once(dirname(__FILE__).'/maxThumbnailTest.class.php');


$t = new lime_test(15, new lime_output_color());

$tc = new maxThumbnailTest();

$t->info('getOption()');
$t->is($tc->getOption('dealer_id'), 106, 'dealer_id option was retrived right.');

$t->info('isMultyDealer()');
$tc = new maxThumbnailTest();
$t->is(
  $tc->isMultyDealer(),
  false,
  'Dealer 106 is not multiple dealer'
);
$tc = new maxThumbnailTest(array('dealer_id' => '123_456'));
$t->is(
  $tc->isMultyDealer(),
  true,
  'Dealer 123_456 is  multiple dealer'
);

$t->info('getSourcePhotoUrl()');
$t->is(
  $tc->getSourcePhotoUrl(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg'),
  'http://www.maxposter.ru/photo/123/54321/original/sdf767sd6f8sd6f8sdfgsd.jpeg',
  'Source photo URL was retrived right.'
);


$t->info('getPhotoFilePath()');
$tc->setOption('photo_dir', dirname(__FILE__).'/photo');
$tc->setOption('dealer_id', 123);
$t->is(
  $tc->getPhotoFilePath(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg', 'source'),
  $tc->getOption('photo_dir').DIRECTORY_SEPARATOR.'54321'.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'sdf767sd6f8sd6f8sdfgsd.jpeg',
  'Path for big photo for one dealer retrived right.'
);

$tc->setOption('dealer_id','123_456');
$t->is(
  $tc->getPhotoFilePath(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg', 'source'),
  $tc->getOption('photo_dir').DIRECTORY_SEPARATOR.'123'.DIRECTORY_SEPARATOR.'54321'.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'sdf767sd6f8sd6f8sdfgsd.jpeg',
  'Path for big photo for some dealers retrived right.'
);

$t->is(
  $tc->getPhotoFilePath(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg', 'middle'),
  $tc->getOption('photo_dir').DIRECTORY_SEPARATOR.'123'.DIRECTORY_SEPARATOR.'54321'.DIRECTORY_SEPARATOR.'middle'.DIRECTORY_SEPARATOR.'sdf767sd6f8sd6f8sdfgsd.jpeg',
  'Path for small photo retrived right.'
);


$t->info('checkSourcePhoto()');

// Создаем файл с исходным фото
$tc->setOption('dealer_id', 123);
$sourceFilePath = $tc->getPhotoFilePath(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg', 'source');
@mkdir(dirname($sourceFilePath), 0775, true);
file_put_contents($sourceFilePath, '111');
try
{
  $t->is(
    $tc->checkSourcePhoto(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg'),
    true,
    'Source photo file exists.'
  );
}
catch (Exception $e)
{
  $t->fail('Source photo file exist, but thrown exception.');
}
// Удаление файла с исходным фото и каталог для него
@unlink($sourceFilePath);
@rmdir(dirname($sourceFilePath));
@rmdir(dirname(dirname($sourceFilePath)));
@rmdir(dirname(dirname(dirname($sourceFilePath))));

try
{
  $tc->checkSourcePhoto(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg');
  $t->fail('Source photo does not exists at requested URL. Must be thrown exception.');
}
catch (Exception $e)
{
  $t->is(
    $e->getMessage(),
    'Photo file '.$tc->getSourcePhotoUrl(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg').' does not exist.',
    'Photo file '.$tc->getSourcePhotoUrl(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg').' does not exist. Thrown right exception.'
  );
}

try
{
  $tc->setOption('loadSourcePhotoFromUrl', 1111);
  $tc->checkSourcePhoto(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg');
  $t->fail('Source photo was not saved. Must be thrown exception.');
}
catch (Exception $e)
{
  $t->is(
    $e->getMessage(),
    'File save error '.$tc->getPhotoFilePath(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg', 'source'),
    'Photo file '.$tc->getPhotoFilePath(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg', 'source').' was not saved. Thrown right exception.'
  );
}

try
{
  $tc->setOption('savePhoto', 1111);
  $t->is(
    $tc->checkSourcePhoto(123, 54321, 'sdf767sd6f8sd6f8sdfgsd.jpeg'),
    true,
    'Source photo was loaded.'
  );
}
catch (Exception $e)
{
  $t->fail('Source photo must be loaded. But thrown exception.');
}

$t->info('getUrlPattern()');
$tc->setOption('dealer_id', 123);
$tc->setOption('allowed_photo_sizes', array(
  '640x480'  => array('width' => 640, 'height' => 480),
  '120x90'  => array('width' => 120, 'height' => 90),
));
$t->is(
  $tc->getUrlPattern(),
  '\/([0-9]*)\/(640x480|120x90)\/([0-9a-z]*.(jpg|jpeg))$',
  'Got right url pattern for once dealer'
);

$tc->setOption('dealer_id', '123_456');
$t->is(
  $tc->getUrlPattern(),
  '\/(123|456)\/([0-9]*)\/(640x480|120x90)\/([0-9a-z]*.(jpg|jpeg))$',
  'Got right url pattern for some dealers'
);

$t->info('getRequestParams()');
$tc->setOption('dealer_id', 123);
$t->is(
  $tc->getRequestParams(array(1 => array('11'), 2 => array('22'), 3 => array('33'))),
  array(123, '11', '22', '33'),
  'Request params for once dealer retrived right.'
);

$tc->setOption('dealer_id', '123_456');
$t->is(
  $tc->getRequestParams(array(1 => array('11'), 2 => array('22'), 3 => array('33'), 4 => array('44'))),
  array('11', '22', '33', '44'),
  'Request params for some dealers retrived right.'
);