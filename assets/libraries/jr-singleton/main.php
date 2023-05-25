<?php

/*
 * Version:           1.0
 *
 * Requires at least: 6.1
 * Tested up to:      6.2
 *
 * Requires PHP:      8.0
 *
 * -----------------------------------------------
 *
 * Plugin Name: JR : Singleton
 * Plugin URI:
 *
 * Description: This is the base class for singletons. It also has a convenient function for creating setting screens.
 *
 * Author:      Jack Russell
 * Author URI:  https://tekuaru.jack-russell.jp/
 *
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: jr_translation
 * Domain Path: /assets/languages/
 *
 * -----------------------------------------------
 *
 * Update URI:    https://bitbucket.org/jack-russell/jr-singleton/
 *
 * Update Owner:  jack-russell
 * Update Repo:   jr-singleton
 *
 */

defined( 'ABSPATH' ) || exit;

//====================================================================================================================================================
//  Class 定義
//====================================================================================================================================================

if( ! class_exists( 'jr_singleton' ) ) {
  class jr_singleton {

    const PLUGIN_SLUG   = 'jr_plugin';
    const PLUGIN_OPTION = 'jr_plugin_option';

    const URL_SEPARATOR = '/';

    protected $option = [];

    /* -------------------------------------------- */

    const PLUGIN_PREFIX = 'jr-plugin-';

    /* -------------------------------------------- */

    static protected $instance = [];

    static public function getInstance() {
      return static::$instance[static::class] ?? static::$instance[static::class] = new static();
    }

    /* -------------------------------------------- */

    final private function __construct() {

      static::immediately();

      /* -------------------------------------------- */

      add_action( 'init', [ $this, 'init' ] );

    }

    /* -------------------------------------------- */

    function immediately() {}

    /* -------------------------------------------- */

    function init() {

      load_plugin_textdomain( 'jr_translation', false, plugin_basename( dirname( __FILE__ ) ) . '/assets/languages' );

      /* -------------------------------------------- */

      $plugin_prefix = static::PLUGIN_PREFIX;

      $option_get = get_option( static::PLUGIN_OPTION );
      $this->option = wp_parse_args( $option_get, static::defaults() );
      $option = $this->option;

    }

//--------------------------------------------------------------------------------------------------
//  基本設定 : デフォルト値
//--------------------------------------------------------------------------------------------------

    static protected function defaults() {

      $plugin_prefix = static::PLUGIN_PREFIX;

      $defaults = [];

      // Filter : jr-plugin-defaults
      return apply_filters( $plugin_prefix . 'defaults', $defaults );
    }

//--------------------------------------------------------------------------------------------------
//  管理画面 : 設定
//--------------------------------------------------------------------------------------------------

    static protected function _add_admin_menu( $page_title, $menu_title, $menu_slug, $callback = '', $position = null ) {
      global $menu;

      $plugin_slug = static::PLUGIN_SLUG;

      $menu_switch = false;
      foreach( $menu as $sub_menu ) {
        if( in_array( $plugin_slug , $sub_menu ) ) {
          $menu_switch = true;
          break;
        }
      }

      if( $menu_switch ) {
        add_submenu_page(
          $plugin_slug,
          $page_title,
          $menu_title,
          'activate_plugins',
          $menu_slug,
          $callback,
          $position
        );
      } else {
        add_options_page(
          $page_title,
          $menu_title,
          'activate_plugins',
          $menu_slug,
          $callback,
          $position
        );
      }
    }

//--------------------------------------------------------------------------------------------------
//  設定ファイル : 読み込み
//--------------------------------------------------------------------------------------------------

    static protected function setting_loader( $file ) {

      if( empty( $file ) )
        return false;

      $dir = '';

      if( is_multisite() ) :
        $dir = implode( DIRECTORY_SEPARATOR, [ WP_CONTENT_DIR, $file . '-setting' . '-blog-' . get_current_blog_id() . '.php' ] );
      else :
        $dir = implode( DIRECTORY_SEPARATOR, [ WP_CONTENT_DIR, $file . '-setting' . '.php' ] );
      endif;

      if( file_exists( $dir ) )
        require_once( $dir );

    }

//====================================================================================================================================================
//  関数
//====================================================================================================================================================

//--------------------------------------------------------------------------------------------------
//  Form 作成
//--------------------------------------------------------------------------------------------------

    protected function render_admin_form( $form = [], $prefix = '' ) {

      $separator = ':';

      $option = $this->option;

      $html = [];

      $html[] = '<div class="form-area">';

      if( ! empty( $form ) && is_array( $form ) ) {
        foreach( $form as $form_table ) {

          $html[] = '<div class="form-item">';

          if( isset( $form_table['heading'] ) ) {
            $heading = $form_table['heading'];
            if( isset( $form_table['add_heading'] ) ) {
              $heading .= ' ' . $separator . ' ' . $form_table['add_heading'];
            }
            $html[] = '<h3>' . $heading . '</h3>';
          }

          $html[] = '<table class="form-table">';
          $html[] = '<tbody>';

          if( ! empty( $form_table['row'] ) && is_array( $form_table['row'] ) ) {
            foreach( $form_table['row'] as $row ) {

              $row_id = implode( '-', $row['name'] );
              $row_name = '[' . implode( '][', $row['name'] ) . ']';
              $row_value = null;
              if( isset( $option[$prefix.$row_id] ) ) $row_value = $option[$prefix.$row_id];
              if( empty( $row_value ) && isset( $row['value'] ) ) $row_value = $row['value'];

              $html[] = '<tr valign="top">';

              switch( $row['type'] ) {

                case 'checkbox' :
                  $html[] = '<th scope="row"><label for="' . esc_attr( $row_id ) . '">' . $row['label'] . '</label></th>';
                  $html[] = '<td>';
                  $html[] = '<input type="hidden" name="setting' . esc_attr( $row_name ) . '" value="0">';
                  $html_tmp = '';
                    $html_tmp .= '<input type="checkbox" name="setting' . esc_attr( $row_name ) . '" id="' . esc_attr( $row_id ) . '" value="1"';
                    if( $row_value ) $html_tmp .= ' checked';
                    $html_tmp .= '>' . PHP_EOL;
                    $html[] = $html_tmp;
                  $html[] = '</td>';
                  break;

                case 'select' :
                  if( empty( $row['option'] ) ) {
                    break;
                  }
                  $html[] = '<th scope="row"><label for="' . esc_attr( $row_id ) . '">' . $row['label'] . '</label></th>';
                  $html[] = '<td>';
                  $html[] = '<select name="setting' . esc_attr( $row_name ) . '">';
                  foreach( $row['option'] as $key => $value ) {
                    $html_tmp = '';
                      $html_tmp .= '<option value="' . esc_attr( $key ) . '"';
                      if( $key == $row_value ) $html_tmp .= ' selected';
                      $html_tmp .= '>' . $value . '</option>';
                      $html[] = $html_tmp;
                  }
                  $html[] = '</select>';
                  $html[] = '</td>';
                  break;

                case 'text' :
                  $html[] = '<th scope="row"><label for="' . esc_attr( $row_id ) . '">' . $row['label'] . '</label></th>';
                  $html[] = '<td>';
                  $html[] = '<input type="text" name="setting' . esc_attr( $row_name ) . '" id="' . esc_attr( $row_id ) . '" value="' . esc_attr( $row_value ) . '" size="80">';
                  $html[] = '</td>';
                  break;

                case 'textarea' :
                  $html[] = '<th scope="row"><label for="' . esc_attr( $row_id ) . '">' . $row['label'] . '</label></th>';
                  $html[] = '<td>';
                  $html[] = '<textarea name="setting' . esc_attr( $row_name ) . '" id="' . esc_attr( $row_id ) . '" cols="80" rows="10">' . esc_textarea( $row_value ) . '</textarea>';
                  $html[] = '</td>';
                  break;

                case 'image' :
                  static::_render_admin_form_use_media();
                  $html[] = '<th scope="row"><label for="' . esc_attr( $row_id ) . '">' . $row['label'] . '</label></th>';
                  $html[] = '<td class="image">';
                  $html[] = '<div class="button image--set">' . __( 'Set image', 'jr_translation' ) . '</div>';
                  $html[] = '<div class="button image--delete">' . __( 'Remove image', 'jr_translation' ) . '</div>';
                  $html[] = '<input type="hidden" name="setting' . esc_attr( $row_name ) . '" id="' . esc_attr( $row_id ) . '" value="' . esc_attr( $row_value ) . '">';
                  $html[] = '<figure class="image--image"><img style="max-width: 100%;vertical-align: bottom;" src="' . wp_get_attachment_image_url( $row_value, 'full' ) . '"></figure>';
                  $html[] = '</td>';
                  break;

              }

              $html[] = '</tr>';

            }
          }

          $html[] = '</tbody>';
          $html[] = '</table>';

          $html[] = '</div>';

        }
      }

      $html[] = '</div>';

      echo implode( PHP_EOL, $html );

    }

    static protected function _render_admin_form_use_media() {

      $plugin_prefix = static::PLUGIN_PREFIX;

      wp_enqueue_media();

      wp_enqueue_script(
        $plugin_prefix . 'media',
        plugin_dir_url( __FILE__ ) . implode( static::URL_SEPARATOR, [ 'assets', 'js', 'form_table-media.js' ] ),
        [ 'jquery' ],
        @filemtime( plugin_dir_path( __FILE__ ) .  implode( DIRECTORY_SEPARATOR, [ 'assets', 'js', 'form_table-media.js' ] ) )
      );

    }

//--------------------------------------------------------------------------------------------------
//  Post 保存
//--------------------------------------------------------------------------------------------------

    static protected function save_admin_post( $form = [], $prefix = '' ) {

      $plugin_prefix = static::PLUGIN_PREFIX;

      $option = [];

      if( ! empty( $_POST['setting'] ) ) {
        if( ! empty( $form ) && is_array( $form ) ) {
          foreach( $form as $form_table ) {
            if( ! empty( $form_table['row'] ) && is_array( $form_table['row'] ) ) {
              foreach( $form_table['row'] as $row ) {
                $option[ $plugin_prefix . implode( '-', $row['name'] ) ] = stripslashes_deep( static::_get_value_of_multidimensional_associative_array_with_key_array( $_POST, array_merge( ['setting'], $row['name'] ) ) );
              }
            }
          }
        }
      }

      return $option;
    }

    static protected function _get_value_of_multidimensional_associative_array_with_key_array( $haystack, $needle ) {
      $return = '';

      $key = array_shift( $needle );

      if( isset( $haystack[ $key ] ) ) {
        if( ! empty( $needle ) ) {
          $return = static::_get_value_of_multidimensional_associative_array_with_key_array( $haystack[ $key ], $needle );
        } else {
          $return = $haystack[ $key ];
        }
      }

      return $return;
    }

//--------------------------------------------------------------------------------------------------
//  改行テキストを配列に変換
//--------------------------------------------------------------------------------------------------

    static protected function _convert_text_to_array( $text ) {
      $array = explode( "\n", $text );
      $array = array_map( 'trim', $array );
      $array = array_filter( $array, 'strlen' );
      $array = array_values( $array );
      return $array;
    }

//====================================================================================================================================================
//  プラグイン 初期化
//====================================================================================================================================================

  }

  jr_singleton::getInstance();

}
