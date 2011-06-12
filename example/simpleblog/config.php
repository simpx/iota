$dbConfig = array(
    'HOST' => 'localhost',
    'USER' => 'root',
    'PSW' => '12345',
    'DATABASE' => 'IotaBlog');
 
$config['dbConfig'] = $dbConfig;
 
Iota::application(null,$config);
