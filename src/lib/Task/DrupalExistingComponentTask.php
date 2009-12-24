<?php
/**
 * Get all of the existing modules and export the list to a property.
 * 
 * @package DrupalDistroBuilder
 * @author M Butcher <matt@aleph-null.tv>
 * @copyright Copyright (c) 2009, Matt Butcher.
 * @version 1.0
 * @license http://opensource.org/licenses/lgpl-2.1.php The GNU Lesser GPL (LGPL).
 */
require_once 'lib/DrupalReleaseHistory.php';

class DrupalExistingComponentTask extends Task {
  protected $component;
  protected $drupalVersion = '6.x';
  protected $path = '.';
  protected $sep = ',';
  protected $isRecursive = FALSE;
  protected $pinlist = array();
  protected $pinlistSep = ',';
  protected $drh = NULL;
  
  public function __construct() {
    $this->drh = new DrupalReleaseHistory();
  }
  
  public function init(){}
  
  public function main(){
    
    $buffer = array();
    $infoFiles = array();
    $pattern = '/^([\-_\w]+\.info)$/';
    
    //$dirit = $this->isRecursive ? new RecursiveDirectoryIterator($this->path) : new DirectoryIterator($this->path);
    $dirit = new DirectoryIterator($this->path);
    foreach ($dirit as $file) {
      if (!$file->isDot() && $file->isDir()) {
        $filename = $file->getFilename();
        //$match = $filename . '.info';
        $matches = array();
        // Check for .info.
        $inner = new DirectoryIterator($file->getPathname());
        foreach ($inner as $innerfile) {
          if (preg_match($pattern, $innerfile->getFilename(), $matches)) {
            $infoFiles[$filename] = $innerfile->getPathname();
            $buffer[] = $filename;
            
            // We only need to find the first info file.
            break;
          }
        }
      }
    }
    
    if (!empty($this->pinlist)) {
      $buffer = $this->skipPinlist($buffer);
    }
    
    if ($this->onlyUpgrades) {
      $updates = array();
      $buffer = $this->skipNonUpgrades($buffer, $infoFiles, $updates);
      $this->getProject()->setNewProperty('drupal.modules.available.updates', implode(',',$updates));
    }
    
    $files = implode($this->sep, $buffer);
    $this->getProject()->setNewProperty('drupal.modules.existing', $files);
  }

  public function setRecursive($recursionFlag) {
    $this->isRecursive = filter_var($recursionFlag, FILTER_VALIDATE_BOOLEAN);
  }
  
  public function setOnlyUpgrades($upgrades) {
    $this->onlyUpgrades = filter_var($upgrades, FILTER_VALIDATE_BOOLEAN);
  }
  
  public function setDir($path) {
    $this->path = $path;
  }
  
  public function setSeparator($sep) {
    $this->sep = $sep;
  }
  
  public function setPinList($pin) {
    $this->pinlist = explode($this->pinlistSep, $pin);
  }
  
  ////////////
  // Internal
  
  protected function skipPinlist($buffer) {
    $buffer2 = array();
    foreach ($buffer as $item) {
      if (!in_array($item, $this->pinlist)) {
        $buffer2[] = $item;
      }
    }
    return $buffer2;
  }
  
  protected function skipNonUpgrades($buffer, $infoFiles, &$updates) {
    $buffer2 = array();
    foreach ($buffer as $item) {
      $existing = $this->versionFromInfo($infoFiles[$item]);
      $hist = $this->drh->getPackageInfo($item);
      if ($existing != $hist->getVersionId()) {
        $buffer2[] = $item;
        $updates[] = $item . '-' . $hist->getVersionId();
      }
      
    }
    return $buffer2;
  }
  
  /**
   * Quick-n-dirty version string from info file.
   */
  protected function versionFromInfo($info) {
    if (!is_file($info)) return '';
    $lines = file($info);
    $version = '';
    
    // We have to parse them all because some modules have version twice.
    foreach ($lines as $line) {
      if (strpos($line, 'version') === 0) {
        list($name, $version) = explode('=', $line, 2);
      }
    }
    $version = str_replace('"', '', $version);
    $version = str_replace('\'', '', $version);
    return trim($version);
  }
}