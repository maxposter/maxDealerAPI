<?php
require_once($max_path.'/maxCacheHtmlClient.php');

class maxCacheHtmlClientTest extends maxCacheHtmlClient
{
  public 
    $dirs = array(),
    $cacheHtmlPath,
    $cachedHtml
  ;
  
  protected function delTree($_dir)
  {
    $this->dirs[] = $_dir;
  }
  
  public function clearCache()
  {
    return parent::clearCache();
  }
  
  public function getPath2XSLT($_xslName)
  {
    return parent::getPath2XSLT($_xslName);
  }
  
  public function getHtmlCacheHashKey($_themeName)
  {
    return parent::getHtmlCacheHashKey($_themeName);
  }
  
  protected function saveCacheToFile($_path, $_string)
  {
    $this->cacheHtmlPath = $_path;
    $this->cachedHtml = $_string;
    return true;
  }
  
  public function cacheHtml($_xsltName, $_html)
  {
    return parent::cacheHtml($_xsltName, $_html);
  }
}

class maxCacheHtmlClientTest2 extends maxCacheHtmlClient
{
  public
    $xml,
    $xsltName = array(),
    $html,
    $xsltPath,
    $type
  ;
  
  public function getPath2XSLT($_xslName)
  {
    $this->xsltName[] = $_xslName;
    return $this->xsltPath;
  }
  
  public function xsltTransform($_xstlName)
  {
    return parent::xsltTransform($_xstlName);
  }
  
  protected function cacheHtml($_xsltName, $_html)
  {
    $this->xsltName[] = $_xsltName;
    $this->html = $_html;
  }
  
  public function transformXml2Html()
  {
    return parent::transformXml2Html();
  }
  
  protected function loadFromCache($_type, $_themeName)
  {
    $this->type = $_type;
    $this->xsltName[] = $_themeName;
    return false;
  }
  
  public function loadHtmlFromCache()
  {
    return parent::loadHtmlFromCache();
  }
}