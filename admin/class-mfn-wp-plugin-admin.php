<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/crholm
 * @since      1.0.0
 *
 * @package    Mfn_Wp_Plugin
 * @subpackage Mfn_Wp_Plugin/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mfn_Wp_Plugin
 * @subpackage Mfn_Wp_Plugin/admin
 * @author     Rasmus Holm <rasmus.holm@modularfinance.se>
 */
class Mfn_Wp_Plugin_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mfn_Wp_Plugin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mfn_Wp_Plugin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mfn-wp-plugin-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mfn_Wp_Plugin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mfn_Wp_Plugin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        $options = get_option($this->plugin_name);

        $subscription = mfn_get_subscription_by_plugin_url(get_option("mfn-subscriptions"), mfn_plugin_url());
        $subscription_id = $subscription['subscription_id'] ?? '';

        $mfn_admin_params = array(
            'plugin_url' => mfn_plugin_url(),
            'sync_url' => $options['sync_url'] ?? '',
            'hub_url' => mfn_fetch_hub_url() ?? '',
            'subscription_id' => $subscription_id
        );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mfn-wp-plugin-admin.js', array( 'jquery' ), $this->version, false );
        wp_localize_script($this->plugin_name, 'mfn_admin_params', $mfn_admin_params);
	}

    public function add_plugin_admin_menu() {

        /*
         * Add a settings page for this plugin to the Settings menu.
         *
         * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
         *
         *        Administration Menus: http://codex.wordpress.org/Administration_Menus
         *
         */
        add_options_page( 'MFN Feed', 'MFN Feed', 'manage_options', $this->plugin_name, array($this, 'display_plugin_setup_page')
        );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */

    public function add_action_links( $links ) {
        /*
        *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
        */
        $settings_link = array(
            '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
        );
        return array_merge(  $settings_link, $links );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */

    public function display_plugin_setup_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib.php';
        include_once( 'partials/mfn-wp-plugin-admin-display.php' );
    }

    public function reg_option() {
        register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));

    }

    private function validate_slug($r): string
    {
        $r = strtolower(preg_replace("/[^a-zA-z0-9-_]/", "", $r));
        $r = trim(isset($r) ? $r : '');
        $r = preg_replace('/[_]+/', '_', $r);
        $r = preg_replace('/[-]+/', '-', $r);
        return $r;
    }

    public function validate($input) {
        $input['hub_url'] = str_replace(' ', '', trim(isset($input['hub_url']) ? $input['hub_url'] : ''));
        $input['sync_url'] = str_replace(' ', '', trim(isset($input['sync_url']) ? $input['sync_url'] : ''));


        if(isset($input['rewrite_post_type']) && is_array($input['rewrite_post_type'])) {
            foreach($input['rewrite_post_type'] as $key => $slug) {
                $k = explode("_", $key);
                $k = $k[0];

                if($k === "slug" || $k === "single-slug") {
                    $input['rewrite_post_type'][$key] = $this->validate_slug($slug);
                }
                else {
                    $input['rewrite_post_type'][$key] = trim(isset($input['rewrite_post_type'][$key]) ? $input['rewrite_post_type'][$key] : '');
                }
            }
            // serialize array before saving
            $input['rewrite_post_type'] = serialize($input['rewrite_post_type']);
        }
        return $input;
    }
}
