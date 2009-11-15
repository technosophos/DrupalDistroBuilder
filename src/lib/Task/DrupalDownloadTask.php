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

class DrupalDownloadTask extends Task {
  
  protected $majorVersion = '6.x';
  protected $exactVersion = NULL;
  protected $path = '.';
  
  public function init() {}
  
  public function main() {
    $dcr = new DrupalCoreRelease($this->majorVersion);
    
    $version = $dcr->latestVersionId();
    $url = $dcr->latestDownloadUrl();
    //$outfile = sprintf('%s/drupal-%s.tgz', $this->path, $version);
    $outfile = sprintf('%s/drupal-dl.tgz', $this->path);
    
    copy($url, $outfile);
    
    $this->getProject()->setNewProperty('drupal.version', $version);
  }
  
  public function setMajorVersion($version) {
    $this->majorVersion = $version;
  }
  
  public function setExactVersion($versionString) {
    $this->exactVersion = $versionString;
  }
  
  public function setDir($path) {
    $this->path = $path;
  }
}