<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */

foreach ( $this->service->all_services() as $service ) {
	/** @var \Gianism\Service\AbstractService $instance */
	$instance = $this->service->get( $service );
	$path = $instance->get_admin_template( 'setup' );
	if ( $path && file_exists( $path ) ) {
		include $path;
	}
}
