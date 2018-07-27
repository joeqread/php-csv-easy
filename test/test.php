<?php

/**
 * I know this should be done in PHPUnit.  I'm still learning it.
 */

require_once("../File.php");

// Make a dummy csv file
$data="First Name\tlast name\tE-mail\tTelephone\r\nJoe\tRead\tjoeqread@gmail.com\t385-275-6111\r\ntest\ttest\ttest@test.com\t801-123-4567\r\n";
file_put_contents("test.csv", $data);

// Start the test!
$csv=new CSV\File( "test.csv" );

try {
  $csv->parse();
  foreach ( $csv as $key => $line ) {
    if ( $key == 0 ) {
      if ( $line->first_name == "Joe" ) echo "First Name Passed! "; else echo "First Name FAILED! ";
      if ( $line->last_name == "Read" ) echo "Last Name Passed! "; else echo "Last Name FAILED! ";
      if ( $line->email == "joeqread@gmail.com" ) echo "Email Passed! "; else echo "Email FAILED! ";
      if ( $line->telephone == "385-275-6111" ) echo "Telephone Passed! "; else echo "Telephone FAILED! ";
      echo "\r\n";
    }
    print_r($line);
  }
} catch ( Exception $e ) {
  echo $e->getMessage();
}

unlink("test.csv");
