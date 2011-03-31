<?php
require_once($max_path.'/maxXmlClient.php');

class maxXmlClientTest extends maxXmlClient
{
  public 
    $headers,
    $loadXML = 0,
    $loadXMLPaths = array(),
    $xml
  ;
  
  public function getRelativePath()
  {
    return parent::getRelativePath();
  }
  
  public function getAbsolutePath($_mirror)
  {
    return parent::getAbsolutePath($_mirror);
  }
  
  public function getCurlOptions()
  {
    return parent::getCurlOptions();
  }
  
  protected function loadXmlFromMirror($_path)
  {
    $this->loadXML++;
    $this->loadXMLPaths[] = $_path;
    return $this->loadXML % 2 ? true : false;
  }
  
  public function getDealerId()
  {
    return parent::getDealerId();
  }
}