<?php
/*
 * Plugin name: Reports
 * Description: View various reports, e.g. HTTP request staistics from GoAccess
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname( __FILE__ ) . '/../lib/logs-page.php';

if ( ! class_exists('Logs') ) {
  class Logs {

    private $capability_required;

    public static $instance;

    public static function init() {
      if ( is_null( self::$instance ) ) {
        self::$instance = new Logs();
      }
      return self::$instance;
    }

    private function __construct() {
      $this->capability_required = 'activate_plugins';

      // on multisite, only the super-admin can use this plugin
      if ( is_multisite() ) {
        $this->capability_required = 'manage_network';
      }

      add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
      add_action( 'wp_ajax_fetch_log_rows', array( $this, 'ajax_fetch_log_rows' ) );
    
      seravo_add_postbox(
        'logs',
        __('Logs', 'seravo'),
        array( __CLASS__, 'seravo_logs_postbox' ),
        'tools_page_logs_page',
        'normal'
      );
    }

    /**
     * Enqueues styles and scripts for the admin tools page
     *
     * @param mixed $hook
     * @access public
     * @return void
     */
    public function admin_enqueue_styles( $hook ) {
      wp_register_style( 'log_viewer', plugin_dir_url( __DIR__ ) . '/style/log-viewer.css', '', Helpers::seravo_plugin_version() );
      wp_register_script( 'log_viewer', plugin_dir_url(__DIR__) . '/js/log-viewer.js', '', Helpers::seravo_plugin_version());

      if ( $hook === 'tools_page_logs_page' ) {
        wp_enqueue_style( 'log_viewer' );
        wp_enqueue_script( 'log_viewer' );
      }
    }


    /**
     * Adds the submenu page for Server Logs under tools
     *
     * @access public
     * @return void
     */
    public function add_submenu_page() {
      add_submenu_page(
          'tools.php',
          __('Logs', 'seravo'),
          __('Logs', 'seravo'),
          $this->capability_required,
          'logs_page',
          'Seravo\seravo_postboxes_page'
      );
    }

    public function seravo_logs_postbox() {
      global $current_log;

      $regex = null;
      if ( isset( $_GET['regex'] ) ) {
        $regex = $_GET['regex'];
      }

      // Default log view is the PHP error log as it is the most important one
      $default_logfile = 'php-error.log';

      // Use supplied log name if given
      if ( isset( $_GET['logfile'] ) ) {
        $current_logfile = $_GET['logfile'];
      } else {
        $current_logfile = $default_logfile;
      }

      $max_num_of_rows = 50;
      if ( isset( $_GET['max_num_of_rows'] ) ) {
          $max_num_of_rows = (int) $_GET['max_num_of_rows'];
      }

      // Automatically fetch all logs from /data/log/*.log
      $logs = glob( '/data/log/*.log' );
      if ( empty( $logs ) ) :
          echo '<div class="notice notice-warning" style="padding:1em;margin:1em;">' .
          __('No logs found in <code>/data/log/</code>.', 'seravo') . '</div>';
          return;
      endif;

      // Create an array of the logfiles with basename of log as key
      $logfiles = array();
      foreach ( $logs as $key => $log ) {
        $logfiles[ basename( $log ) ] = $log;
      }

      // Set logfile based on supplied log name if it's available
      if ( isset( $logfiles[ $current_logfile ] ) ) {
        $logfile = $logfiles[ $current_logfile ];
      } elseif ( isset( $logfiles[ $default_logfile ] ) ) {
        $logfile = $logfiles[ $default_logfile ];
      } else {
        $logfile = null;
      }

      ?>
      <ul class="subsubsub">
        <?php foreach ( $logs as $key => $log ) : ?>
        <li>
          <a href="tools.php?page=logs_page&logfile=<?php echo basename( $log ); ?>&max_num_of_row=<?php echo $max_num_of_rows; ?>"
            class="<?php echo basename( $log ) == $current_logfile ? 'current' : ''; ?>">
            <?php echo basename( $log ); ?>
          </a>
          <?php echo ( $key < ( count( $logs ) - 1 ) ) ? ' |' : ''; ?>
        </li>
      <?php endforeach; ?>
      </ul>
      <p class="clear"></p>
      <?php Logs_Page::render_log_view( $logfile, $regex, $max_num_of_rows );
    }

  }

  Logs::init();
}
