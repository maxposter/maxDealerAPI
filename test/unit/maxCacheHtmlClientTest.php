<?php
require_once(dirname(__FILE__).'/../lib/unit.php');
require_once(dirname(__FILE__).'/../lib/compareXml.php');

require_once('maxCacheHtmlClientTest.class.php');

$t = new lime_test(18, new lime_output_color());

$t->info('setXsltTemplate() & getXsltTemplate');
$tc = new maxCacheHtmlClientTest();
$tc->setXsltTemplate('aaaaa');
$t->is($tc->getXsltTemplate(), 'aaaaa', 'Setter and getter work properly.');

$t->info('clearCache()');
$tc = new maxCacheHtmlClientTest();
$tc->clearCache();
$t->is(
  $tc->dirs,
  array('cache/xml/', 'cache/html/'),
  'Cleared both caches.'
);

$t->info('getPath2XSLT()');
$tc = new maxCacheHtmlClientTest();
$t->is(
  $tc->getPath2XSLT('vehicles'),
  'source/xsl/vehicles.xsl',
  'Path to XSLT was retrieved right.'
);

$t->info('getHtmlCacheHashKey()');
$tc = new maxCacheHtmlClientTest();
$tc->setRequestParams(array('bbb'=>'999', 'aaa'=>array(1 => 111, 2 => 222)));
$t->is(
  $tc->getHtmlCacheHashKey('vehicles'),
  'vehicles&aaa[1]=111&aaa[2]=222&bbb=999',
  'Html cache hash key was retrieved right.'
);

$t->info('cacheHtml()');
$tc = new maxCacheHtmlClientTest();
$tc->setRequestParams(array('bbb'=>'999', 'aaa'=>array(1 => 111, 2 => 222)));
$t->is(
  $tc->cacheHtml('abracadabra', 'aaaabbbbcccc'),
  false,
  'Html for theme abracadabra does not cached.'
);
$t->is(
  $tc->cacheHtml('vehicles', 'aaaabbbbcccc'),
  true,
  'Html for theme vehicles cached.'
);
$t->is(
  $tc->cacheHtmlPath,
  'cache/html/109291fcf5a54b30323a5b1d9cc863d6.html',
  'Path for html cahce generated right.'
);
$t->is(
  $tc->cachedHtml,
  'aaaabbbbcccc',
  'Html cahced successfully'
);

$t->info('xsltTransform()');
$tc = new maxCacheHtmlClientTest2();
$tc->xml = DOMDocument::load(dirname(__FILE__).'/vehicle.xml');
$tc->xsltPath = dirname(__FILE__).'/vehicle.xsl';
$t->is(
  $tc->xsltTransform('xslt_name'),
  'Honda',
  'XSLT transform complete.'
);
$t->is(
  $tc->xsltName,
  array('xslt_name'),
  'XSLT name transfered to the function right.'
);

$t->info('transformXml2Html()');
$tc = new maxCacheHtmlClientTest2();
$tc->xml = DOMDocument::load(dirname(__FILE__).'/vehicle.xml');
$tc->xsltPath = dirname(__FILE__).'/vehicle.xsl';
$t->is(
  $tc->transformXml2Html(),
  'Honda',
  'Retived right html.'
);
$t->is(
  $tc->xsltName,
  array('vehicle', 'vehicle'),
  'Apllied XSLT template from XML theme.'
);
$t->is(
  $tc->html,
  'Honda',
  'Cached right HTML'
);
$tc = new maxCacheHtmlClientTest2();
$tc->xml = DOMDocument::load(dirname(__FILE__).'/vehicle.xml');
$tc->xsltPath = dirname(__FILE__).'/vehicle.xsl';
$tc->setXsltTemplate('new_template');
$tc->transformXml2Html();
$t->is(
  $tc->xsltName,
  array('new_template', 'new_template'),
  'Apllied custom XSLT template.'
);
$tc = new maxCacheHtmlClientTest2();
$tc->xml = DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?><response id="error"/>');
$tc->xsltPath = dirname(__FILE__).'/vehicle.xsl';
$tc->setXsltTemplate('new_template');
$tc->transformXml2Html();
$t->is(
  $tc->xsltName,
  array('error', 'error'),
  'Apllied XSLT template from XML theme, because theme = error.'
);

$t->info('loadHtmlFromCache()');
$tc = new maxCacheHtmlClientTest2();
$tc->setRequestThemeName('vehicles');
$tc->loadHtmlFromCache();
$t->is($tc->type, 'html', 'Right data type searched in cache.');
$t->is($tc->xsltName, array('vehicles'), 'xslt themplate name get from request theme name.');
$tc->xsltName = array();
$tc->setXsltTemplate('second_xslt_theme');
$tc->loadHtmlFromCache();
$t->is($tc->xsltName, array('second_xslt_theme'), 'xslt themplate name overvrite request theme name.');

