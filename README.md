# CSV\File

This class makes it easy to work with user-uploaded CSV files.

You never know if the file will be comma-seperated, tab seperated, whatever...
You never know what fields are in what order...

Here's my solution: detect the seperator based on char frequency and use the header row for property names, then iterate through it.

Obviously the header solution isn't 100% ideal, but when you're only looking for "`email`" and "`first_name`" 
it's pretty simple.  Feel free to steal the idea.

```php
$csv=new CSV\File( "myemaillist.csv" );
$csv->parse(); // Make sure you check for exceptions!
foreach ( $csv as $record ) echo $record->email;
```

You can add more seperator possibilities to check like this: `$csv->valid_seperators[]="%%";`
