<?php
require_once(dirname(__FILE__).'/../lib/unit.php');
require_once(dirname(__FILE__).'/../lib/compareXml.php');

require_once('maxCacheXmlClientTest.class.php');

$t = new lime_test(28, new lime_output_color());

$t->info('getDefaultOptions()');

$tc = new maxCacheXmlClientTest();
$defaultOptions = $tc->getDefaultOptions();
$t->ok(isset($defaultOptions['mirror1']), 'Options from maxXmlClient class were setted.');
$t->ok(isset($defaultOptions['cache_xml_dir']), 'Options from maxCacheXmlClient class were setted.');


$t->info('getRequestParamsAsString()');
$tc = new maxCacheXmlClientTest();
$t->is($tc->getRequestParamsAsString('bbb', 'aaa'), '&aaa=bbb', 'Request string 1 is right.');
$t->is($tc->getRequestParamsAsString('', 'aaa'), '', 'Request string 2 is right.');
$t->is(
  $tc->getRequestParamsAsString(array(1 => 111, 2 => 222), 'aaa'),
  '&aaa[1]=111&aaa[2]=222',
  'Request string 3 is right.'
);
$t->is(
  $tc->getRequestParamsAsString(array('bbb'=>'999', 'aaa'=>array(1 => 111, 2 => 222))),
  '&aaa[1]=111&aaa[2]=222&bbb=999',
  'Request string 4 is right.'
);

$t->info('getCacheHashKey()');
$tc = new maxCacheXmlClientTest();
$t->is($tc->getCacheHashKey('vehicles'), 'vehicles', 'Cache hash key for theme without requet params was returned right.');


$t->info('getXmlCacheHashKey()');
$tc = new maxCacheXmlClientTest();
$tc->setRequestParams(array('bbb'=>'999', 'aaa'=>array(1 => 111, 2 => 222)));
$t->is(
  $tc->getXmlCacheHashKey('vehicles'),
  'vehicles&aaa[1]=111&aaa[2]=222&bbb=999',
  'Cache hash key for theme with requet params was returned right.'
);

$t->info('getCacheDir()');
$tc = new maxCacheXmlClientTest();
$t->is($tc->getCacheDir('xml'), 'cache/xml/', 'Path to xml cache dir was retrived right.');

$t->info('getCachePath()');
$tc = new maxCacheXmlClientTest();
$tc->setRequestParams(array('bbb'=>'999', 'aaa'=>array(1 => 111, 2 => 222)));
$t->is(
  $tc->getCachePath('xml', 'vehicles'),
  'cache/xml/'.md5('vehicles&aaa[1]=111&aaa[2]=222&bbb=999').'.xml',
  'Path to the XML cache was built right.'
);

$t->info('getCacheActualPointPath()');
$tc = new maxCacheXmlClientTest();
$t->is($tc->getCacheActualPointPath(), 'cache/actual_point.txt', 'Path to cache actual point file was retrived right.');

$t->info('getCacheActualPoint()');
$tc = new maxCacheXmlClientTest();
$actualAt = time() - 10800;
$expiresAt = time() + 10800;
$tc->cacheActualPointStr = ''.$actualAt;
$t->is($tc->getCacheActualPoint(), array(0, 0), 'Cache actual point was setted to 0, because data in cache actual point file was broken.');

$tc = new maxCacheXmlClientTest();
$actualAt = time() - 10800;
$expiresAt = time() + 10800;
$tc->cacheActualPointStr = ''.$actualAt.' '.$expiresAt;
$t->is($tc->getCacheActualPoint(), array($actualAt, $expiresAt), 'Cache actual point was loaded.');

$tc->cacheActualPointStr = ''.$actualAt.' '.(time() + 21600);
$t->is($tc->getCacheActualPoint(), array($actualAt, $expiresAt),'Second request for cache actual point returns previous data.');

$t->info('checkCacheExpiresAt()');
$tc = new maxCacheXmlClientTest();
$t->is($tc->checkCacheExpiresAt(), false, 'Cache does not actual because cache actual file does not exist.');

$tc = new maxCacheXmlClientTest();
$tc->cacheActualPointStr = ''.(time() - 10800).' '.(time());
$t->is($tc->checkCacheExpiresAt(), true, 'Cache actual.');

$tc = new maxCacheXmlClientTest();
$tc->cacheActualPointStr = ''.(time() - 21600).' '.(time() -1);
$t->is($tc->checkCacheExpiresAt(), false, 'Cache does not actual because actualAt time less then now.');

$t->info('updateCacheActualPoint()');
$actualAt = time() - 10800;
$expiresAt = time();
$tc = new maxCacheXmlClientTest();
$tc->cacheActualPointStr = ''.$actualAt.' '.$expiresAt;
$tc->updateCacheActualPoint(array($actualAt, $expiresAt));
$t->is($tc->clearedPaths, array(), 'Cache actual poin and cache files were not cleared.');

$actualAt = time();
$expiresAt = time()+10800;
$tc->updateCacheActualPoint(array($actualAt, $expiresAt));
$t->is(
  $tc->clearedPaths,
  array('cache/xml/', ''.$actualAt.' '.$expiresAt),
  'Cache actual poin and cache files were cleared.'
);

$t->info('loadFromCache()');
$tc = new maxCacheXmlClientTest2();
$tc->cacheActualPointStr = ''.(time()-10800).' '.(time() + 10800);
$tc->cachePaths = array('html'=>'vehicle.html');
$t->is($tc->loadFromCache('html', 'vehicles'), '<html><body>Vehicle</body></html>', 'Cached data was loaded from the cache.');

$tc = new maxCacheXmlClientTest2();
$tc->cacheActualPointStr = ''.(time()-21600).' '.(time() - 1);
$tc->cachePaths = array('html'=>'vehicle.html');
$t->is($tc->loadFromCache('html', 'vehicles'), false, 'Cache expired. New request to MaxPoster needs.');

$t->info('loadXmlFromCache()');
$tc = new maxCacheXmlClientTest2();
$tc->setRequestThemeName('vehicles');
$tc->cacheActualPointStr = ''.(time()-10800).' '.(time() + 10800);
$tc->cachePaths = array('xml'=>'vehicle.xml');
$t->is(
  $tc->loadXmlFromCache()->getElementsByTagName('response')->item(0)->getAttribute('id'),
  'vehicle',
  'XML was loaded. DOMDocument was created.'
);

$tc = new maxCacheXmlClientTest2();
$tc->setRequestthemeName('vehicles');
$tc->cacheActualPointStr = ''.(time()-21600).' '.(time() - 1);
$tc->cachePaths = array('xml'=>'vehicle.xml');
$t->is(
  $tc->loadXmlFromCache(),
  false,
  'XML cache expired. New request to MaxPoster needs.'
);

$t->info('cacheXML()');
$tc = new maxCacheXmlClientTest3();
$tc->setRequestThemeName('vehicles');
$tc->setRequestParams(array('mark'=>'Audi'));
$tc->xml = DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8"?><response id="vehicles"/>');
$tc->setResponseHeaders(null, 'Last-Modified: '.date('Y-m-d H:i:s', time()-10800));
$tc->setResponseHeaders(null, 'Expires: '.date('Y-m-d H:i:s', time()+10800));
$tc->cacheActualPointStr = ''.(time()-10800).' '.(time() + 10800);
$t->is($tc->cacheXML(), true, 'XML was cached.');
$t->is(
  $tc->cachedPath,
  'cache/xml/'.md5('vehicles&mark=Audi').'.xml',
  'XML was saved to right cache path.'
);
$t->ok(
  compareXML($tc->cachedStr,'<?xml version="1.0" encoding="utf-8"?><response id="vehicles"/>'),
  'Cached rigth XML.'
);
$tc = new maxCacheXmlClientTest3();
$tc->xml = DOMDocument::loadXML('<?xml version="1.0" encoding="utf-8" ?><response id="error"></response>');
$tc->setResponseHeaders(null, 'Last-Modified: '.date('Y-m-d H:i:s', time()-10800));
$tc->setResponseHeaders(null, 'Expires: '.date('Y-m-d H:i:s', time()+10800));
$t->is($tc->cacheXML(), false, 'XML was not cached, because error theme does not cached.');
$tc = new maxCacheXmlClientTest3();
$tc->xml = '<?xml version="1.0" encoding="utf-8" ?><response id="error"></response>';
$tc->setResponseHeaders(null, 'Last-Modified: '.date('Y-m-d H:i:s', time()-10800));
$tc->setResponseHeaders(null, 'Expires: '.date('Y-m-d H:i:s', time()+10800));
$t->is($tc->cacheXML(), false, 'Data was not cached, because data was not instance of DOMDocument.');
