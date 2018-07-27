<?php
namespace CSV {

/**
 * This class makes it easy to work with user-uploaded CSV files.
 * You never know if the file will be comma-seperated, tab seperated, whatever...
 * You never know what fields are in what order...
 * Here's my solution: detect the seperator and use the header row for property names, then iterate through it.
 * Obviously the header solution isn't 100% ideal, but when you're only looking for "email" and "first_name" 
 * it's pretty simple.  Feel free to steal the idea.
 *
 * $csv=new CSV\File( "myemaillist.csv" );
 * $csv->parse(); // Make sure you check for exceptions!
 * foreach ( $csv as $record ) echo $record->email;
 *
 * You can add more seperator possibilities to check like this: $csv->valid_seperators[]="%%";
 */
class File implements \Iterator {
  private $file=""; // user defined through construct or check

  private $filename=""; // auto-split
  private $path=""; // auto-split

  private $fd=null;

  private $raw_header = ""; // just for reference, probably won't use
  private $first_line = null;

  private $header_checked = false;
  private $has_valid_headers = false;
  private $translated_headers = array();

  private $seperator_checked = false;
  private $has_valid_seperator = false;
  private $seperator = null;

  private $checked = false;
  private $parsed = false;

  private $csv = array();

  private $iterator_cursor = 0;

  public $valid_seperators=array ( ',', "\t", ';', '|' );

  public function __construct ( $file ) {
    $this->file=$file;

    if ( ! empty($file) ) {
      $this->check($this->file);
    }

    return true;
  }

  public function __destruct ( ) {
    fclose($this->fd);
  }

  /**
   * Detects the seperator of the CSV file, mostly by frequency of occurance.
   */
  private function detect_seperator ( ) {
    if ( $this->seperator_checked ) { return $this->has_valid_seperator; }

    fseek($this->fd,0);
    $lines="";
    for ( $i=0; $i < 5; $i++ ) {
      if ( false === ($lines.=fgets($this->fd,4096)) ) {
        Error::log_message("Failed reading first 5 lines");
        return false;
      }
    }
    fseek($this->fd,0);

    $max_count=-1;
    $max_i=-1;
    for ( $i=0; $i < count($this->valid_seperators); $i++ ) {
      $count=substr_count($lines,$this->valid_seperators[$i]);
      if ( $count > $max_count ) { $max_count=$count; $max_i=$i; }
    }

    $this->seperator_checked=true;

    if ( $max_count > 0 ) { $this->has_valid_seperator=true; $this->seperator=$this->valid_seperators[$max_i]; }
    return $this->has_valid_seperator;
  }

  /**
   * Detects the headers of the CSV file, does some basic processing so they are legal property names
   */
  private function detect_headers ( ) {
    if ( $this->header_checked ) { return $this->has_valid_headers; }
    if ( ! $this->seperator_checked ) { throw new \Exception("Can not determine header until seperator is found."); }
    if ( ! $this->has_valid_seperator ) { throw new \Exception("Do not check for a header on a file without a determined seperator."); }

    fseek($this->fd,0);
    if ( false === ($this->first_line=fgets($this->fd,4096)) ) {
      throw new \Exception("Failed reading first line",1);
    }
    //  fseek($this->fd,0); // Don't reset file pointer because we don't want to include header in results

    $this->raw_header=explode($this->seperator,$this->first_line);

    $translated=array();
    for ( $i=0; $i < count($this->raw_header); $i++ ) {
      $header=preg_replace('/[^A-Za-z0-9_ ]/','',trim(strtolower($this->raw_header[$i])));
      $header=str_replace(' ','_',$header);
      $translated[$i]=$header;
    }

    $this->translated_headers=$translated;

    $this->header_checked = true;

    if ( count($translated) > 0 ) $this->has_valid_headers=true;

    return $this->has_valid_headers;
  }

  /**
   * Prep the file for reading... open file descriptor, figure out the seperator, attempt to figure out headers
   */
  private function check ( ) {
    $this->path=dirname(realpath($this->file));
    $this->filename=basename($this->file);

    if ( false === ($this->fd=fopen($this->file,"r")) ) {
      throw new \Exception("Can not open file '" . $this->file . "'");
    }

    if ( false === $this->detect_seperator() ) {
      throw new \Exception("No valid seperator");
      return false;
    }

    if ( false === $this->detect_headers() ) {
      throw new \Exception("No valid header");
      return false;
    }

    $this->checked=true;
  }

  /**
   * Populate this->csv with the CSV's data for iteration
   */
  public function parse ( ) {
    if ( ! $this->checked ) { $this->check(); } // Not catching exception here so it can bubble up to caller
    if ( $this->parsed ) { return true; } // Not catching exception here so it can bubble up to caller

    $current_line=0;

    while ( $line_info=fgetcsv($this->fd,4096,$this->seperator,'"') ) {
      $info=array();

      foreach ( $line_info as $key => $value ) {
        $key=trim($key);
        $value=trim($value);
        if ( isset($this->translated_headers[$key]) && ! empty($this->translated_headers[$key]) ) {
          $field_name=$this->translated_headers[$key];
          $info[$field_name]=trim($value);
        }
      }

      $this->csv[]=$info;
    }
    $this->parsed=true;
  }  


  /******* ITERATOR METHODS *********/

  /**
   * Set cursor to previous item in the list
   */
  public function prev () {
    $this->iterator_cursor--;
  }

  /**
   * Gets the current item in the list
   */
  public function current () {
    $item=$this->csv[ $this->iterator_cursor ];
    return (object) $item;    
  }

  /**
   * Set cursor to the next item in the list
   */
  public function next () {
    ++$this->iterator_cursor;
  }

  /**
   * Rewind cursor
   */
  public function rewind ( ) {
    $this->iterator_cursor = 0;
  }

  /**
   * Get the current position in the list
   */
  public function key () {
    return $this->iterator_cursor;
  }

  /**
   * Checks if a particular element of the list exists
   */
  public function valid () {
    return isset($this->csv[ $this->iterator_cursor ]);
  }


} // End class 'File'


} // End Namespace 'CSV'
