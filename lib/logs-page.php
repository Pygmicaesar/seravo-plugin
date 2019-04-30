<?php

namespace Seravo;

if ( ! defined('ABSPATH') ) {
  die('Access denied');
}

class Logs_Page {
  private $capability_required;

  public function render_log_view( $logfile, $regex = null, $max_num_of_rows ) {
    global $current_log;
    ?>
    <div class="log-view">
      <?php if ( is_readable( $logfile ) ) : ?>
      <div class="tablenav top">
        <form class="log-filter" method="get">
          <label class="screen-reader-text" for="regex">Regex:</label>
          <input type="hidden" name="page" value="logs_page">
          <input type="hidden" name="log" value="<?php echo $current_log; ?>">
          <input type="hidden" name="logfile" value="<?php echo basename($logfile); ?>">
          <input type="search" name="regex" value="<?php echo $regex; ?>" placeholder="">
          <input type="submit" class="button" value="<?php _e('Filter', 'seravo'); ?>">
        </form>
      </div>
      <div class="log-table-view"
            data-logfile="<?php echo esc_attr( $logfile ); ?>"
            data-logbytes="<?php echo esc_attr( filesize($logfile) ); ?>"
            data-regex="<?php echo esc_attr($regex); ?>">
          <table class="wp-list-table widefat striped" cellspacing="0">
            <tbody>
              <?php $result = self::render_rows( $logfile, -1, $max_num_of_rows, $regex ); ?>
            </tbody>
          </table>
      </div>
      <?php endif; ?>
      <?php if ( ! is_null( $logfile ) ) : ?>
      <?php if ( ! is_readable( $logfile ) ) : ?>
      <div id="message" class="notice notice-error">
        <p>
          <?php
            // translators: $s name of the logfile
            printf( __("File %s does not exist or we don't have premissions to read it.", 'seravo' ), $logfile );
          ?>
        </p>
      </div>
      <?php elseif ( ! $result ) : ?>
        <p><?php _e('Log empty', 'seravo' ); ?></p>
      <?php else : ?>
        <p><?php _e('Scroll to load more lines from the log.', 'seravo'); ?></p>
      <?php
        endif;
        endif;
      ?>
      <div class="log-view-active">
      </div>
      <p>
        <?php
        // translators: $s full path of the logfile
        printf( __('Full log files can be found on the server in the path %s.', 'seravo'), '<code>/data/log</code>');
        ?>
      </p>
    </div>
    <?php
  }

  public function render_rows( $logfile, $offset, $lines, $regex = null, $cutoff_bytes = null ) {
    $regex = '#' . preg_quote( $regex ) . '#';

    $rows = self::read_log_lines_backwards( $logfile, $offset, $lines, $regex, $cutoff_bytes );

    if ( empty( $rows ) ) {
      return 0;
    }

    $num_of_rows = 0;
    foreach ( $rows as $row ) {
      ++$num_of_rows;
      ?>
      <tr>
        <td><span class="logrow"><?php echo $row; ?></span></td>
      </tr>
      <?php
    }
    return $num_of_rows;
  }

  public function ajax_fetch_log_rows() {
    $capability_required = 'activate_plugins';

    if ( is_multisite() ) {
      $capability_required = 'manage_network';
    }

    if ( ! current_user_can( $capability_required ) ) {
      exit;
    }

    if ( isset( $_REQUEST['logfile'] ) ) {
      $logfile = $_REQUEST['logfile'];
    } else {
      exit;
    }

    $offset = 0;
    if ( isset( $_REQUEST['offset'] ) ) {
      $offset = -( 1 + (int) $_REQUEST['offset'] );
    }

    $regex = null;
    if ( isset( $_REQUEST['regex'] )) {
      $regex = $_REQUEST['regex'];
    }

    $cutoff_bytes = null;
    if ( isset( $_REQUEST['cutoff_bytes'] ) ) {
      $cutoff_bytes = $_REQUEST['cutoff_bytes'];
    }

    render_rows( $logfile, $offset, 100, $regex, $cutoff_bytes );
    exit;
  }

  public static function read_log_lines_backwards( $filepath, $offset = -1, $lines = 1, $regex = null, $cutoff_bytes = null) {
    $f = @fopen( $filepath, 'rb' );

    if ( $f === false) {
      return false;
    }

    $filesize = filesize( $filepath );
    
    $buffer = 4096;
  
    if ( is_null( $cutoff_bytes ) ) {
      // Jump to last character
      fseek( $f, -1, SEEK_END );
    } else {
      fseek( $f, $cutoff_bytes - 1, SEEK_SET );
    }

    // Start reading
    $output = [];
    $linebuffer = '';

    if ( fread( $f, 1 ) !== "\n" ) {
      $linebuffer = "\n";
    }

    $lines--;

    while ( $lines > 0 ) {
      $seek = min( ftell( $f ), $buffer );

      $last_buffer = ( ftell( $f ) <= $buffer );

      if ( $seek <= 0 ) {
        break;
      }

      fseek( $f, -$seek, SEEK_CUR );
      
      $chunk = fread ( $f, $seek );

      fseek ($f, -mb_strlen( $chunk, '8bit' ), SEEK_CUR);

      $linebuffer = $chunk . $linebuffer;

      $complete_lines = [];

      if ( $last_buffer ) {
        $complete_lines [] = rtrim( substr( $linebuffer, 0, strpos( $linebuffer, "\n" ) ) );
      }

      while ( preg_match( '/\n(.*?\n)/s', $linebuffer, $matched ) ) {
        $match = $matched[1];
      
        $linebuffer = substr_replace( $linebuffer, '', strpos( $linebuffer, $match ), strlen( $match ) );

        $complete_lines [] = rtrim( $match );
      }

      $limit = count( $complete_lines );
      while ( $offset < -1 && $limit > 0 ) {
        array_pop( $complete_lines );
        $offset++;
      }

      if ( ! is_null( $regex ) ) {
        $complete_lines = preg_grep( $regex, $complete_lines);

        foreach ( $complete_lines as &$line ) {
          $line = preg_replace( $regex, '<span class="highlight">$0</span>', $line );
        }
      }

      $lines -= count( $complete_lines );

      $output = array_merge( $complete_lines, $output );
    }

    while ( ++$lines < 0 ) {
      array_shift( $output );
    }

    fclose( $f );

    return $output;
  }
}