<?php
/**
 * 
 * @package DrupalDistroBuilder
 * @author M Butcher <matt@aleph-null.tv>
 * @copyright Copyright (c) 2009, Matt Butcher.
 * @version 1.0
 * @license http://opensource.org/licenses/lgpl-2.1.php The GNU Lesser GPL (LGPL).
 */
require_once 'lib/DrupalReleaseHistory.php';

class DrupalComponentDownloadTask extends Task {
  protected $component;
  protected $drupalVersion = '6.x';
  protected $path = '.';
  
  public function init(){}
  
  public function main(){
    $drh = new DrupalReleaseHistory($this->drupalVersion);
    $package = $drh->getPackageInfo($this->component);
    $version = $package->getVersionId();
    $url = $package->getDownloadUrl();
    $name = $package->getPackageName();
    
    //$outfile = sprintf('%s/%s-%s.tgz', $this->path, $this->component, $version);
    $outfile = sprintf('%s/%s-dl.tgz', $this->path, $this->component);
    copy($url, $outfile);
    
    $this->getProject()->setNewProperty('component.version', $version);
    $this->getProject()->setNewProperty('component.name', $name);
  }

  public function setComponent($name) {
   $this->component = trim($name);
  } 
  
  public function setDrupalVersion($name) {
    $this->drupalVersion = $name;
  }
  
  public function setDir($path) {
    $this->path = $path;
  }
}