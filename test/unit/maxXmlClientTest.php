<?php
require_once(dirname(__FILE__).'/../lib/unit.php');
require_once(dirname(__FILE__).'/../lib/compareXml.php');

require_once('maxXmlClientTest.class.php');

$t = new lime_test(24, new lime_output_color());

$tc = new maxXmlClientTest();

$t->info('__construct()');
$tc = new maxXmlClientTest(array(
    'dealer_id' => 'code',
    'allowed_themes' => array('vehicles'),
    'api_version' => 1
));
$t->ok(count($tc->getOptions()) > 2, 'Default options were setted.');
$t->is($tc->getOption('dealer_id'),'code','Class options were overload.');

$t->info('setTheme');
$t->is($tc->setRequestThemeName('vehicles'), true, 'Theme vehicles was setted.');
$t->is($tc->setRequestThemeName(56), true, 'Theme vehicle with id = 56 was setted.');
try
{
  $tc->setRequestThemeName('hdgf');
  $t->fail('Wrong theme "hdgf". Must be thrown exception.');
}
catch (maxException $e)
{
  $t->is(
    $e->getCode(),
    maxException::ERR_WRONG_REQUEST_THEME,
    'Wrong theme "hdgf". Thrown right exception.'
  );
}

$t->info('getRequestTheme()');
$tc = new maxXmlClientTest();
try
{
  $tc->getRequestThemeName();
  $t->fail('Request theme does not set. Must be thrown exception.');
}
catch (maxException $e)
{
  $t->is(
    $e->getCode(),
    maxException::ERR_DOES_NOT_SET_REQUEST_THEME,
    'Request theme does not set. Thrown right exception.'
  );
}
$tc->setRequestThemeName('vehicles');
$t->is($tc->getRequestThemeName(), 'vehicles', 'Theme returs right.');

$t->info('setRequestParams() && getRequestParams()');
$tc->setRequestParams(array('aaa'=>1, 'bbb'=>2));
$t->is($tc->getRequestParams(), array('aaa'=>1, 'bbb'=>2), 'Request params were setted.');

$t->info('setRequestParams() && getRequestParams()');
$tc->setRequestParams(array('aaa'=>1, 'bbb'=>2));
$t->is($tc->getRequestParams(), array('aaa'=>1, 'bbb'=>2), 'Request params were setted.');

$t->info('getRelativePath()');
$tc = new maxXmlClientTest(array('api_version' => 1));
try
{
  $tc->getRelativePath('vehicles');
  $t->fail('dealer_id does not set. Must be thrown exception.');
}
catch (maxException $e)
{
  $t->is(
    $e->getCode(),
    maxException::ERR_DOES_NOT_SET_DEALERID,
    'dealer_id does not set. Thrown right exception.'
  );
}
$tc->setOption('dealer_id', 123);
$tc->setRequestThemeName('vehicles');
$t->is(
  $tc->getRelativePath(),
  'api1/123/vehicles.xml',
  'Relative path was retrived right.'
);

$tc->setOption('dealer_id', '123_456_789');
$tc->setRequestThemeName('vehicles');
$t->is(
  $tc->getRelativePath(),
  'api1/123_456_789/vehicles.xml',
  'Relative path was retrived right.'
);

$t->info('getAbsolutePath()');
$tc = new maxXmlClientTest(array('dealer_id' => 123, 'api_version' => 1));
$tc->setRequestThemeName('vehicles');
$t->is(
  $tc->getAbsolutePath('mirror1'),
  'http://export1.maxposter.ru/api1/123/vehicles.xml',
  'Absolute path was retrived right.'
);
try
{
  $tc->getAbsolutePath('mirror3');
  $t->fail('Setted wrong mirror. Must be thrown exception.');
}
catch (maxException $e)
{
  $t->is(
    $e->getCode(),
    maxException::ERR_DOES_NOT_SET_MIRROR,
    'Wrong mirror was setted. Thrown right exception.'
  );
}

$t->info('getCurlOptions()');
$tc = new maxXmlClientTest(array('dealer_id' => 123, 'password' => 456));
$t->is(
  $tc->getCurlOptions(),
  array(
    CURLOPT_HEADER => 0,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_HEADERFUNCTION => array($tc, 'setResponseHeaders'),
    CURLOPT_TIMEOUT => 5,
    CURLOPT_USERPWD => $tc->getOption('dealer_id').':'.$tc->getOption('password')
  ),
  'Got right default CURL option'
);

$tc = new maxXmlClientTest(array('dealer_id' => 123, 'password' => 456, 'CURL' => array(CURLOPT_TIMEOUT => 30)));
$t->is(
  $tc->getCurlOptions(),
  array(
    CURLOPT_HEADER => 0,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_HEADERFUNCTION => array($tc, 'setResponseHeaders'),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERPWD => $tc->getOption('dealer_id').':'.$tc->getOption('password')
  ),
  'Set CURLOPT_TIMEOUT from class option'
);

$t->info('getResponseThemeName()');
$tc = new maxXmlClientTest(array('dealer_id' => 123));
try 
{
  $tc->getResponseThemeName();
  $t->fail('Can not define response theme, because does not set response XML. Must be thrown exception.');
}
catch (maxException $e)
{
  $t->is(
    $e->getCode(),
    maxException::ERR_DOES_NOT_SET_RESPONSE_THEME,
    'Can not define response theme, because does not set response XML. Thrown right exception.'
  );
}
$tc = new maxXmlClientTest(array('dealer_id' => 123, 'api_version' => 1));
$tc->xml = DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8" ?><response id="vehicle" />');
$t->is($tc->getResponseThemeName(), 'vehicle', 'Define response theme name = vehicle.');

$t->info('getXml()');
$tc = new maxXmlClientTest(array('dealer_id' => 123, 'api_version' => 1));
$tc->setRequestThemeName('vehicles');
$t->is($tc->getXml(), true, 'Thirst request return Xml.');
$t->is(
  $tc->loadXMLPaths,
  array('http://export1.maxposter.ru/api1/123/vehicles.xml'),
  'Thirst request executed to http://export1.maxposter.ru/api1/123/vehicles.xml'
);
$tc->loadXMLPaths = array();
$tc->xml = null;
$t->is($tc->getXml(), true, 'Second request return true.');
$t->is(
  $tc->loadXMLPaths,
  array(
    'http://export1.maxposter.ru/api1/123/vehicles.xml',
    'http://export2.maxposter.ru/api1/123/vehicles.xml',
  ),
  'Second request was executed to both mirrors.'
);

$tc = new maxXmlClientTest();
$t->ok(
  compareXML(
    $tc->getXml()->saveXML(),
    '<?xml version="1.0" encoding="utf-8"?><response id="error"><error error_id="'.maxException::ERR_DOES_NOT_SET_DEALERID.'">'.maxException::$err_messages[maxException::ERR_DOES_NOT_SET_DEALERID].'</error></response>'
  ),
  'Does not set dealer_id. Error xml retrived.'
);
$tc = new maxXmlClientTest();
$tc->setOption('dealer_id', 123);
$t->ok(
  compareXML(
    $tc->getXml()->saveXML(),
    '<?xml version="1.0" encoding="utf-8"?><response id="error"><error error_id="'.maxException::ERR_DOES_NOT_SET_REQUEST_THEME.'">'.maxException::$err_messages[maxException::ERR_DOES_NOT_SET_REQUEST_THEME].'</error></response>'
  ),
  'Does not set request theme name. Error xml retrived.'
);