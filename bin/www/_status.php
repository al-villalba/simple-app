<?php

require __DIR__ . '/../../vendor/simple/mvc/src/bootstrap.php';

$app = \Simple::app();

if( isset($app->config['log']['level']) &&
	!in_array($app->config['log']['level'], ['debug', 'info'])
) {
	die();
//} else {
//    error_reporting(E_ALL);
//    ini_set('display_errors', 'On');
}

/* show system status */

$errorReporting = '(' . ini_get('error_reporting') . ')';
if( ini_get('error_reporting') == E_ALL ) {
	$errorReporting .= 'E_ALL';
} else {
	$_error_reporting = ini_get('error_reporting') ?:
		E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED;
	foreach( ['E_ERROR', 'E_WARNING', 'E_NOTICE', 'E_DEPRECATED', 'E_PARSE',
		'E_STRICT', 'E_RECOVERABLE_ERROR', 'E_COMPILE_ERROR'] as $e
	) {
		if( $_error_reporting & constant($e) ) {
			$errorReporting .= " $e &";
		}
	}
	$errorReporting = preg_replace('/ &$/', '', $errorReporting);
}

$status['system'] = array(
	'phpversion'  => phpversion(),
	'environment' => $app['env'],
	'php_ini'     => array(
		'display_errors'  => ini_get('display_errors'),
		'error_reporting' => $errorReporting,
		'log_errors'      => ini_get('log_errors'),
		'error_log'       => ini_get('error_log'),
		'include_path'    => ini_get('include_path')
	),
	'host'        => php_uname('u'),
	'php_sapi'    => $app->sapi_name ?? php_sapi_name()
);

/* Database */

if( !empty($app->config['database']) ) {
	$configDb = $app->config['database'];
	$configDb['password'] = '*****';
	$status['database'] = array_merge(
		['class' => get_class($app->db)],
		$configDb,
		['tables'   => []]
	);
	/** @throws PDOException is connection fails */
	//$app->db = new \PDO($status['database']['dsn'], $status['database']['username'], $status['database']['password']);
	if( $app->db->exec('set time_zone = "+00:00"') === false ) {
		throw new \Exception('ERROR setting time_zone in the db. PDO error: ' . json_encode($app->db->errorInfo()));
	}
	try {
		$status['database']['tables'] = $app->db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
	} catch( \Throwable $e ) {
		throw new \Exception('ERROR querying sql. PDO error: ' . json_encode($app->db->errorInfo()));
	}
}

/* show framework status */

$appClass = get_class($app);
$reflection = new ReflectionClass($app);
$status['framework'] = array(
	'class'    => $appClass,
	'version'  => $appClass::VERSION,
	'fileName' => $reflection->getFileName()
);

/* Dependencies */

$phpunit = array(
	'path' => null,
	'version' => null
);
try {
	$phpunitPath = trim(`which phpunit`);
} catch (Exception $e) {
	// safe mode: Guessing path...
	$phpunitPath = '/usr/bin/phpunit';
}
if( is_executable($phpunitPath) ) {
	$phpunit['path'] = $phpunitPath;
	$phpunit['version'] = shell_exec($phpunit['path'] . ' --version');
}
$status['dependencies']['PHPUnit'] = $phpunit;

/* Config */

$status['config'] = $app->config;
if( !empty($status['config']['database']['password']) ) {
	$status['config']['database']['password'] = '*****';
}

/* Output */

$shorOpts = 'o:';
$longOpts = ['output-format:'];
$options = getopt($shorOpts, $longOpts);

switch( $options['output-format'] ?? ($options['o'] ?? 'json') )
{
	case 'html':
		$body = print_r($status, true);
		echo <<<HTML
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <title>Status Page</title>
 </head>
 <body>
  <pre>
$body
  </pre>
 </body>
</html>
HTML;
		break;
	
	case 'json':
	default:
		header('Content-Type: application/json');
		echo json_encode($status);
		break;
}
