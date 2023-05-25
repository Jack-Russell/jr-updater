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
 * Plugin Name: JR : Updater
 * Plugin URI:
 *
 * Description: Include releases from Git in WordPress updates. Import from: GitHub, Bitbucket.
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
 * Update URI:    https://github.com/Jack-Russell/jr-updater
 *
 * Update Owner:  Jack-Russell
 * Update Repo:   jr-updater
 *
 */

defined( 'ABSPATH' ) || exit;

//====================================================================================================================================================
//  Class 定義
//====================================================================================================================================================

if( ! class_exists( 'jr_updater' ) ) {

  // JR : Singleton
  if( ! class_exists( 'jr_singleton' ) ) {
    require_once( implode( DIRECTORY_SEPARATOR, [ 'assets', 'libraries', 'jr-singleton', 'main.php' ] ) );
  }

  class jr_updater extends jr_singleton {

    const PLUGIN_PREFIX = 'jr-updater-';

    /* -------------------------------------------- */

    function init() {

      parent::init();

      load_plugin_textdomain( 'jr_translation', false, plugin_basename( dirname( __FILE__ ) ) . '/assets/languages' );

      /* -------------------------------------------- */

      add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

      // add_filter( 'extra_plugin_headers', [ $this, 'defaults_plugin_headers' ] );

      // priority : 10
      add_filter( 'update_themes_github.com', [ $this, 'update_themes_github' ], 10, 3 );

      // priority : 10
      add_filter( 'update_plugins_github.com', [ $this, 'update_plugins_github' ], 10, 3 );

      // priority : 10
      add_filter( 'update_themes_bitbucket.org', [ $this, 'update_themes_bitbucket' ], 10, 3 );

      // priority : 10
      add_filter( 'update_plugins_bitbucket.org', [ $this, 'update_plugins_bitbucket' ], 10, 3 );

      // priority : 10
      add_filter( 'http_request_args', [ $this, 'request_args' ], 10, 2 );

      // priority : 10
      add_filter( 'upgrader_post_install', [ $this, 'upgrader_post_install' ], 10, 3 );

    }

//--------------------------------------------------------------------------------------------------
//  管理画面 : 設定
//--------------------------------------------------------------------------------------------------

    function add_admin_menu() {

      parent::_add_admin_menu(
        __( 'Updater', 'jr_translation' ),
        __( 'Updater Setting', 'jr_translation' ),
        self::PLUGIN_PREFIX . 'setting',
        function() {
          require_once( implode( DIRECTORY_SEPARATOR, [ 'assets', 'templates', 'setting.php' ] ) );
        }
      );

    }

//--------------------------------------------------------------------------------------------------
//  基本設定 : デフォルト値 : プラグインヘッダー
//--------------------------------------------------------------------------------------------------

    static function defaults_plugin_headers( $headers ) {

      if( ! in_array( 'Update Owner', $headers ) )
        $headers[] = 'Update Owner';

      if( ! in_array( 'Update Repo', $headers ) )
        $headers[] = 'Update Repo';

      return $headers;
    }

//====================================================================================================================================================
//  Updater
//====================================================================================================================================================

//--------------------------------------------------------------------------------------------------
//  GitHub
//--------------------------------------------------------------------------------------------------

    static function update_themes_github( $update, $theme_data, $theme_stylesheet ) {

      foreach( $GLOBALS['wp_theme_directories'] as $theme_directory ) {
        if( file_exists( $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css' ) ) {
          $stylesheet_directory = $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css';
          break;
        }
      }

      $plugin_headers = get_file_data( $stylesheet_directory, [
        'Update Owner'  => 'Update Owner',
        'Update Repo'   => 'Update Repo',
      ] );

      if( ! isset( $plugin_headers['Update Owner'], $plugin_headers['Update Repo'] ) ) {
        return $update;
      }

      /**
       * GitHub : REST API : Get the latest release
       * https://docs.github.com/ja/rest/releases/releases#get-the-latest-release
       *
       * /repos/{owner}/{repo}/releases/latest
       */
      $response = wp_remote_get(
        sprintf(
          'https://api.github.com/repos/%s/%s/releases/latest',
          $plugin_headers['Update Owner'],
          $plugin_headers['Update Repo'],
        )
      );

      if( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return $update;
      }

      $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

      $update = [
        'version'     => $theme_data['Version'],
        'new_version' => isset( $response_body['tag_name'] ) ? $response_body['tag_name'] : null,
        'package'     => isset( $response_body['zipball_url'] ) ? $response_body['zipball_url'] : null,
        'url'         => isset( $response_body['html_url'] ) ? $response_body['html_url'] : null,
        'slug'        => null,
      ];

      return $update;
    }

    static function update_plugins_github( $update, $plugin_data, $plugin_file ) {

      $plugin_headers = get_file_data( WP_PLUGIN_DIR . '/' . $plugin_file, [
        'Update Owner'  => 'Update Owner',
        'Update Repo'   => 'Update Repo',
      ] );

      if( ! isset( $plugin_headers['Update Owner'], $plugin_headers['Update Repo'] ) ) {
        return $update;
      }

      /**
       * GitHub : REST API : Get the latest release
       * https://docs.github.com/ja/rest/releases/releases#get-the-latest-release
       *
       * /repos/{owner}/{repo}/releases/latest
       */
      $response = wp_remote_get(
        sprintf(
          'https://api.github.com/repos/%s/%s/releases/latest',
          $plugin_headers['Update Owner'],
          $plugin_headers['Update Repo'],
        )
      );

      if( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return $update;
      }

      $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

      $update = [
        'version'     => $plugin_data['Version'],
        'new_version' => isset( $response_body['tag_name'] ) ? $response_body['tag_name'] : null,
        'package'     => isset( $response_body['zipball_url'] ) ? $response_body['zipball_url'] : null,
        'url'         => isset( $response_body['html_url'] ) ? $response_body['html_url'] : null,
        'slug'        => null,
      ];

      return $update;
    }

//--------------------------------------------------------------------------------------------------
//  Bitbucket
//--------------------------------------------------------------------------------------------------

    static function update_themes_bitbucket( $update, $theme_data, $theme_stylesheet ) {

      foreach( $GLOBALS['wp_theme_directories'] as $theme_directory ) {
        if( file_exists( $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css' ) ) {
          $stylesheet_directory = $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css';
          break;
        }
      }

      $plugin_headers = get_file_data( $stylesheet_directory, [
        'Update Owner'  => 'Update Owner',
        'Update Repo'   => 'Update Repo',
      ] );

      if( ! isset( $plugin_headers['Update Owner'], $plugin_headers['Update Repo'] ) ) {
        return $update;
      }

      /**
       * bitbucket : The Bitbucket Cloud REST API
       * https://developer.atlassian.com/cloud/bitbucket/rest/api-group-refs/
       *
       * /2.0/repositories/{username}/{slug}/refs/tags
       */
      $response = wp_remote_get(
        sprintf(
          'https://api.bitbucket.org/2.0/repositories/%s/%s/refs/tags?sort=-name',
          $plugin_headers['Update Owner'],
          $plugin_headers['Update Repo'],
        )
      );

      if( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return $update;
      }

      $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

      $update = [
        'version'     => $theme_data['Version'],
        'new_version' => isset( $response_body['values'][0]['name'] ) ? $response_body['values'][0]['name'] : null,
        'package'     => isset( $response_body['values'][0]['target']['repository']['links']['html']['href'], $response_body['values'][0]['target']['hash'] ) ? sprintf( '%s/get/%s.zip', $response_body['values'][0]['target']['repository']['links']['html']['href'], $response_body['values'][0]['target']['hash'], ) : null,
        'url'         => isset( $response_body['values'][0]['links']['html']['href'] ) ? $response_body['values'][0]['links']['html']['href'] : null,
        'slug'        => null,
      ];

      return $update;
    }

    static function update_plugins_bitbucket( $update, $plugin_data, $plugin_file ) {

      $plugin_headers = get_file_data( WP_PLUGIN_DIR . '/' . $plugin_file, [
        'Update Owner'  => 'Update Owner',
        'Update Repo'   => 'Update Repo',
      ] );

      if( ! isset( $plugin_headers['Update Owner'], $plugin_headers['Update Repo'] ) ) {
        return $update;
      }

      /**
       * bitbucket : The Bitbucket Cloud REST API
       * https://developer.atlassian.com/cloud/bitbucket/rest/api-group-refs/
       *
       * /2.0/repositories/{username}/{slug}/refs/tags
       */
      $response = wp_remote_get(
        sprintf(
          'https://api.bitbucket.org/2.0/repositories/%s/%s/refs/tags?sort=-name',
          $plugin_headers['Update Owner'],
          $plugin_headers['Update Repo'],
        )
      );

      if( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return $update;
      }

      $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

      $update = [
        'version'     => $plugin_data['Version'],
        'new_version' => isset( $response_body['values'][0]['name'] ) ? $response_body['values'][0]['name'] : null,
        'package'     => isset( $response_body['values'][0]['target']['repository']['links']['html']['href'], $response_body['values'][0]['target']['hash'] ) ? sprintf( '%s/get/%s.zip', $response_body['values'][0]['target']['repository']['links']['html']['href'], $response_body['values'][0]['target']['hash'], ) : null,
        'url'         => isset( $response_body['values'][0]['links']['html']['href'] ) ? $response_body['values'][0]['links']['html']['href'] : null,
        'slug'        => null,
      ];

      return $update;
    }

//--------------------------------------------------------------------------------------------------
//  request_args
//--------------------------------------------------------------------------------------------------

    function request_args( $parsed_args, $url ) {

      $plugin_prefix = self::PLUGIN_PREFIX;

      $option = $this->option;

      /**
       * GitHub
       */
      if( ! empty( $url ) && strpos( parse_url( $url, PHP_URL_HOST ), 'github' ) !== false ) {

        if( ! isset( $parsed_args['headers']['Accept'] ) ) {
          $parsed_args['headers']['Accept'] = 'application/vnd.github+json';
        }

        /* -------------------------------------------- */

        if( ! isset( $parsed_args['headers']['X-GitHub-Api-Version'] ) ) {
          $parsed_args['headers']['X-GitHub-Api-Version'] = '2022-11-28';
        }

        /* -------------------------------------------- */

        $token = '';

        $url_path_array   = array_values( array_filter( explode( '/', parse_url( $url, PHP_URL_PATH ) ) ) );

        $github_user      = isset( $url_path_array[1] ) ? $url_path_array[1] : '';
        $github_data_key  = 'token';

        if( isset( $option[ $plugin_prefix . implode( '-', [ 'github', $github_user, $github_data_key ] ) ] ) ) {
          $token = $option[ $plugin_prefix . implode( '-', [ 'github', $github_user, $github_data_key ] ) ];
        }

        if( ! isset( $parsed_args['headers']['Authorization'] ) && ! empty( $token ) ) {
          $parsed_args['headers']['Authorization'] = 'Bearer ' . $token;
        }

      }

      /**
       * Bitbucket
       */
      if( ! empty( $url ) && strpos( parse_url( $url, PHP_URL_HOST ), 'bitbucket' ) !== false ) {

        /**
         * zip の 取得は password 利用
         * API の 取得は token 利用
         */
        if( strpos( parse_url( $url, PHP_URL_PATH ), '.zip' ) !== false ) {

          $token = '';

          $url_path_array   = array_values( array_filter( explode( '/', parse_url( $url, PHP_URL_PATH ) ) ) );

          $bitbucket_user       = isset( $url_path_array[0] ) ? $url_path_array[0] : '';
          $bitbucket_repository = '*';
          $bitbucket_data_key   = 'password';

          if( isset( $option[ $plugin_prefix . implode( '-', [ 'bitbucket', $bitbucket_user, $bitbucket_repository, $bitbucket_data_key ] ) ] ) ) {
            $token = $option[ $plugin_prefix . implode( '-', [ 'bitbucket', $bitbucket_user, $bitbucket_repository, $bitbucket_data_key ] ) ];
          }

          $parsed_args['headers']['Authorization'] = 'Basic ' . base64_encode( $bitbucket_user . ':' . $token );

        } else {

          if( ! isset( $parsed_args['headers']['Accept'] ) ) {
            $parsed_args['headers']['Accept'] = 'application/json';
          }

          /* -------------------------------------------- */

          $token = '';

          $url_path_array   = array_values( array_filter( explode( '/', parse_url( $url, PHP_URL_PATH ) ) ) );

          $bitbucket_user       = isset( $url_path_array[2] ) ? $url_path_array[2] : '';
          $bitbucket_repository = isset( $url_path_array[3] ) ? $url_path_array[3] : '';
          $bitbucket_data_key   = 'token';

          if( isset( $option[ $plugin_prefix . implode( '-', [ 'bitbucket', $bitbucket_user, $bitbucket_repository, $bitbucket_data_key ] ) ] ) ) {
            $token = $option[ $plugin_prefix . implode( '-', [ 'bitbucket', $bitbucket_user, $bitbucket_repository, $bitbucket_data_key ] ) ];
          }

          if( ! isset( $parsed_args['headers']['Authorization'] ) && ! empty( $token ) ) {
            $parsed_args['headers']['Authorization'] = 'Bearer ' . $token;
          }

        }

      }

      return $parsed_args;
    }

//--------------------------------------------------------------------------------------------------
//  upgrader_post_install
//--------------------------------------------------------------------------------------------------

    function upgrader_post_install( $res, $hook_extra, $result ) {

      global $wp_filesystem;

      $plugin_prefix = self::PLUGIN_PREFIX;

      $option = $this->option;

      // [theme] ヘッダー情報 から 新しいディレクトリ名 を 決める
      if( isset( $hook_extra['theme'], $result['destination'] ) ) {
        if( file_exists( $result['destination'] . '/' . 'style.css' ) ) {
          $file_headers = get_file_data( $result['destination'] . '/' . 'style.css', [
            'Update URI'    => 'Update URI',
            'Update Owner'  => 'Update Owner',
            'Update Repo'   => 'Update Repo',
          ] );
        }
      }

      // [plugin] ヘッダー情報 から 新しいディレクトリ名 を 決める
      // [note] ヘッダーファイル の 名前 が 変わっていたら 動かない
      if( isset( $hook_extra['plugin'], $result['destination'] ) ) {
        if( file_exists( $result['destination'] . end( explode( '/', $hook_extra['plugin'] ) ) ) ) {
          $file_headers = get_file_data( $result['destination'] . end( explode( '/', $hook_extra['plugin'] ) ), [
            'Update URI'    => 'Update URI',
            'Update Owner'  => 'Update Owner',
            'Update Repo'   => 'Update Repo',
          ] );
        }
      }

      /* -------------------------------------------- */

      /**
       * GitHub
       */

      if( isset( $file_headers ) ) {
        if( ! empty( $file_headers['Update URI'] ) && strpos( parse_url( $file_headers['Update URI'], PHP_URL_HOST ), 'github' ) !== false ) {
          $destination_name = $file_headers['Update Repo'];
          $destination      = str_replace( $result['destination_name'], $destination_name, $result['destination'] );
        }
      }

      /* -------------------------------------------- */

      /**
       * Bitbucket
       */

       if( isset( $file_headers ) ) {
        if( ! empty( $file_headers['Update URI'] ) && strpos( parse_url( $file_headers['Update URI'], PHP_URL_HOST ), 'bitbucket' ) !== false ) {
          $destination_name = $file_headers['Update Repo'];
          $destination      = str_replace( $result['destination_name'], $destination_name, $result['destination'] );
        }
      }

      /* -------------------------------------------- */

      if( isset( $destination, $destination_name ) && ( $result['destination'] !== $destination ) ) {

        $wp_filesystem->move( $result['destination'], $destination );

        $result['destination']        = $destination;
        $result['remote_destination'] = $destination;
        $result['destination_name']   = $destination_name;

        $res = $result;

      }

      /* -------------------------------------------- */

      /*
      if( $this->active ) {
          activate_plugin( $this->basename );
      }
      */

      /* -------------------------------------------- */

      return $res;
    }

//====================================================================================================================================================
//  プラグイン 初期化
//====================================================================================================================================================

  }

  jr_updater::getInstance();

}
