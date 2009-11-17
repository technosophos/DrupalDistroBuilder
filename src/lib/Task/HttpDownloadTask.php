<?php
/**
 * Task for downloading files to a location.
 */
 
class HttpDownloadTask extends Task {
  
  protected $url = NULL;
  protected $file = NULL;
  
  public function init() {}
  public function main() {
    if (empty($this->url)) {
      throw new Exception('No URL to download.');
    }
    if (empty($this->file)) {
      $this->file = basename($this->url);
    }
    copy($this->url, $this->file);
  }
  /**
   * Set the URL origin.
   * @param string $url
   *  An HTTP URL (or any protocol that PHP understands).
   */
  public function setURL($url) {
    $this->url = $url;
  }
  /**
   * Destination filename.
   *
   * @param string $filename
   *   The filename or directory where the file will be saved.
   */
  public function setToFile($filename) {
    $this->file = $filename;
  }
}