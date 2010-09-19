<?php
/**
 * This package contains tools for processing the Drupal release history.
 *
 * @package DrupalDistroBuilder
 * @author M Butcher <matt@aleph-null.tv>
 * @copyright Copyright (c) 2009, Matt Butcher.
 * @version 1.0
 * @license http://opensource.org/licenses/lgpl-2.1.php The GNU Lesser GPL (LGPL).
 * @subpackage DrupalReleaseHistory
 */

/**
 * QueryPath is used to process XML data.
 * @see http://querypath.org
 */
require_once 'QueryPath/QueryPath.php';

/**
 * Provide access to the standard release history XML feed.
 */
class DrupalReleaseHistory {
  
  const drupalBaseUrl = 'http://updates.drupal.org/release-history/';
  protected $drupalVersion;
  
  public static function parseVersionString($versionString) {
    $parts = explode('-', $versionString);
    $version = array(
      'package name' => $parts[0],
      'drupal version' => $parts[1],
      'package version' => $parts[2],
    );
    if (isset($parts[3])) {
      $version['package extra'] = $parts[3];
    }
    
    return $version;
  }
  
  public function __construct($drupalVersion = '6.x') {
    $this->drupalVersion = $drupalVersion;
  }
  
  public function getPackageInfo($package) {
    if(strpos($package, '-')) {
      $parts = self::parseVersionString($package);
    }
    else {
      $parts['package name'] = $package;
      $parts['drupal version'] = $this->drupalVersion;
    }
    
    $drupalVersion = isset($parts['drupal version']) ? $parts['drupal version'] : $this->drupalVersion;
    
    $url = self::drupalBaseUrl . $parts['package name'] . '/' . $drupalVersion;
    
    $qp = qp($url);
    
    return new DrupalProject($qp, $parts);
    
  }
}

/**
 * Provide access to the Drupal core XML release history.
 *
 * Because core is handled differently than modules and themes, it must be
 * handled separately.
 */
class DrupalCoreRelease {
  protected $drupalVersion;
  
  public function __construct($version = '6.x') {
    $this->drupalVersion = $version;
  }
  
  public function latestVersionId() {
    $url = DrupalReleaseHistory::drupalBaseUrl . 'drupal/' . $this->drupalVersion;
    return qp($url, 'release:first>version')->text();
  }
  
  public function latestDownloadUrl() {
    $url = DrupalReleaseHistory::drupalBaseUrl . 'drupal/' . $this->drupalVersion;
    return qp($url, 'release:first>download_link')->text();
  }
}

/**
 * Data access for project information.
 */
class DrupalProject {
  
  protected $qp;
  protected $packageInfo;
  protected $lenience = TRUE;
  
  /**
   * @param mixed $xml
   *  QueryPath object or anything that QueryPath can parse.
   * @param array $packageInfo
   *  Information about what package you want to retrieve.
   */
  public function __construct($xml, $packageInfo) {
    $this->qp = qp($xml);
    $this->packageInfo = $packageInfo;
  }
  
  public function setLenience($lenient = TRUE) {
    $this->lenience = $linient;
  }
  
  /**
   * Indicates whether the system will be forgiving of projects that don't have official releases.
   *
   * When the system is lenient, it will try to do a number of "more flexible" things to figure
   * out what package version to download on projects that do not explicitly declare a stable 
   * version.
   */
  public function isLenient() {
    return $this->lenience;
  }
  
  public function getPackageName() {
    return $this->packageInfo['package name'];
  }
  
  public function getVersionId() {
    if (isset($this->packageInfo['package version'])) {
      $version = $this->packageInfo['drupal version'] . '-' . $this->packageInfo['package version'];
      if (isset($this->packageInfo['package extra'])) {
        $version .= '-' . $this->packageInfo['package extra'];
      }
    }
    else {
      
      // Get major:
      $major = $this->qp->branch()
        ->top('project>recommended_major,project>default_major')->eq(0)->text();
        
      if (empty($major)) {
        
        if ($this->isLenient()) {
          $major = $this->qp->branch()->top('project>releases>release:first>version_major')->text();
        }
        // Test again.
        if (empty($major)){
          $this->qp->writeXML();
          throw new DrupalVersionException('No recommended or default version for ' . $this->packageInfo['package name']);          
        }
        
      }
      
      // Get minor:
      $releases = $this->qp->branch()->top('version_major:contains(' . $major . ')')->parent();
      $highest_patch = 'x';
      foreach ($releases as $release) {
        $patch_level = $release->find('version_patch')->text();
        
        // Clean this up. It's ugly.
        if (!isset($patch_level)) continue;
        if ($release->next('version_extra')->size() > 0) continue;
        if ($release->end()->siblings('status')->text() != 'published') continue;
        
        if ($patch_level > $highest_patch) $highest_patch = $patch_level;
      }
      
      $version = $this->packageInfo['drupal version'] . '-' . $major . '.' . $highest_patch;
      
      // If the highest patch number is still X, then we are at a dev version.
      if ($highest_patch == 'x') {
        $version .= '-dev';
      }
    }
    return $version;
  }
  
  public function getDownloadUrl($versionString = NULL) {
    if (empty($versionString)) {
      $versionString = $this->getVersionId();
    }
    $url = $this->qp
      ->branch()
      ->top('version:contains(' . $versionString . ')')
      ->parent()
      ->children('download_link:first')
      ->text();
    
    if (empty($url)) {
      throw new DrupalVersionException('No URL for the supplied version ' . htmlentities($versionString));
    }
    
    return $url;
  }
}

class DrupalVersionException extends Exception {}