<?php
$environmentAddresses = array(
	'development' => array( 'localhost' ),
	// 'staging'     => array( '<your_staging_site>.com' ),
	'production'  => array( '<your_production_site>.com' ),
	);

// environments
foreach ( $environmentAddresses as $environment => $addresses ) {
	define( 'ENV_'.strtoupper($environment), isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], $addresses) );
}

// sandbox
define( 'SANDBOX', ENV_DEVELOPMENT || ( isset( $_SERVER['REMOTE_ADDR'] ) && in_array( $_SERVER['REMOTE_ADDR'], array('my_ip_address') ) ) );
