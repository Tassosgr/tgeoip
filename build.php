<?php
$command = 'php ../../utils/build-files/build.php ' . __DIR__ . ' joomla';
if( ($fp = popen($command, 'r')) )
{
	while( !feof($fp) )
	{
        echo fread($fp, 1024);
        flush();
	}
	
    fclose($fp);
}
?>