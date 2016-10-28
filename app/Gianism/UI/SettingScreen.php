<?php

namespace Gianism\UI;


class SettingScreen extends Screen {

	protected $slug = 'gianism';

	protected $default_view = 'setting';

	/**
	 * SettingScreen constructor.
	 */
	protected function admin_init() {
		// Add admin page
		add_options_page(
			$this->_( 'Gianism Setting' ),
			$this->_( 'Gianism Setting' ),
			'manage_options',
			'gianism',
			[ $this, 'render' ]
		);
		// Add option save hook
		add_action( 'load-settings_page_gianism', [ $this, 'update_option' ] );
		// Add view
		foreach ( [
			'setting'   => sprintf( '<i class="lsf lsf-paramater"></i> %s', $this->_( 'Gianism Setting' ) ),
			'setup'     => sprintf( '<i class="lsf lsf-help"></i> %s', $this->_( 'How to set up' ) ),
			'customize' => sprintf( '<i class="lsf lsf-wrench"></i> %s', $this->_( 'Customize' ) ),
			'advanced'  => sprintf( '<i class="lsf lsf-magic"></i> %s', $this->_( 'Advanced Usage' ) ),
		] as $slug => $label ) {
			$this->views[ $slug ] = $label;
		}
	}

	/**
	 * Update option
	 */
	public function update_option() {
		if ( $this->input->verify_nonce( 'gianism_option' ) ) {
			$this->option->update();
			wp_redirect( $this->setting_url() );
			exit;
		}
	}

}
