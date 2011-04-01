<?php
require_once($max_path.'/maxCacheXmlClient.php');

class maxCacheXmlClientTest extends maxCacheXmlClient
{
  public
    $cacheActualPointStr,
    $clearedPaths = array()
  ;

  public function getDefaultOptions()
  {
    return parent::getDefaultOptions();
  }

  public function getRequestParamsAsString($_param, $_key = null)
  {
    return parent::getRequestParamsAsString($_param, $_key);
  }

  public function getCacheHashKey($_themeName)
  {
    return parent::getCacheHashKey($_themeName);
  }

  public function getXmlCacheHashKey($_themeName)
  {
    return parent::getXmlCacheHashKey($_themeName);
  }

  public function getCacheDir($_type)
  {
    return parent::getCacheDir($_type);
  }

  public function getCachePath($_type, $_themeName)
  {
    return parent::getCachePath($_type, $_themeName);
  }

  public function getCacheActualPointPath()
  {
    return parent::getCacheActualPointPath();
  }

  protected function getCacheActualPointFromFile()
  {
    return $this->cacheActualPointStr;
  }

  public function getCacheActualPoint()
  {
    return parent::getCacheActualPoint();
  }

  public function checkCacheExpiresAt()
  {
    return parent::checkCacheExpiresAt();
  }

  protected function delTree($dir)
  {
    $this->clearedPaths[] = $dir;
  }

  protected function saveCacheActualPoint(array $_cacheActualPoint)
  {
    $this->clearedPaths[] = implode(' ', $_cacheActualPoint);
  }

  public function updateCacheActualPoint(array $_cacheActualPoint)
  {
    return parent::updateCacheActualPoint($_cacheActualPoint);
  }
}

class maxCacheXmlClientTest2 extends maxCacheXmlClient
{
  protected function getCacheActualPointFromFile()
  {
    return $this->cacheActualPointStr;
  }

  protected function delTree($dir)
  {
    // Намеренно перекрыта
  }

  protected function updateCacheActualPoint(array $_cacheActualPoint)
  {
    $this->cacheActualPoint = $_cacheActualPoint;
  }

  protected function getCachePath($type, $name)
  {
    return isset($this->cachePaths[$type]) ? dirname(__FILE__).'/'.$this->cachePaths[$type] : false;
  }

  public function loadFromCache($_type, $_themeName)
  {
    return parent::loadFromCache( $_type, $_themeName);
  }

  public function loadXmlFromCache()
  {
    return parent::loadXmlFromCache();
  }
}

class maxCacheXmlClientTest3 extends maxCacheXmlClient
{
  public
    $xml,
    $loadXML = 0,    // Номер вызова метода loadXMLFromMaxPoster()
    $loadXMLPaths = array(),
    $cacheActualPointStr,
    $cachePaths,
    $cachedPath,
    $cachedStr
  ;

  public function setResponseHeaders($_ch, $_header)
  {
    return parent::setResponseHeaders($_ch, $_header);
  }

  protected function loadXmlFromMirror($_path)
  {
    $this->loadXML++;
    $this->loadXMLPaths[] = $_path;
    return $this->loadXML % 2 ? true : false;
  }

  public function loadXML()
  {
    return parent::loadXML();
  }

  public function cacheXML()
  {
    return parent::cacheXML();
  }

  protected function saveCacheToFile($_path, $_string)
  {
    $this->cachedPath = $_path;
    $this->cachedStr = $_string;
    return true;
  }
}
