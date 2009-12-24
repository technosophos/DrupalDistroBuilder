<?php
/**
 * Phing patch task.
 */

/**
 * Simple task for patching.
 *
 * This task takes a patch file and attempts to patch it into the current
 * working directory.
 * Usage:
 * <code>
 * <taskdef classname="lib.Task.SimplePatchTask" name="patch"/>
 * ...
 * <patch p="1" patchfile="${somefile}"/>
 * ...
 * </code>
 */
class SimplePatchTask extends Task {
  
  protected $level = 0;
  protected $file;
  
  public function init() {}
  public function main() {
    $p = $this->level;
    $file = $this->file;
    
    $retval = NULL;
    
    // XXX: This could clearly be more sophisticated.
    passthru(printf('patch -p%d < %s', $p, $file), $retval);
    
  }
  
  /**
   * Set the patch level.
   */
  public function setP($p) {
    $this->level = $p;
  }
  
  /**
   * Set the patch file.
   */
  public function setPatchFile($filename) {
    $this->file = $filename;
  }
}