<?php

defined( 'ABSPATH' ) || exit;

$plugin_prefix = self::PLUGIN_PREFIX;

//--------------------------------------------------------------------------------------------------
//  フォーム
//--------------------------------------------------------------------------------------------------

$themes = wp_get_themes();

$plugins = get_plugins();

$form = [];

/* -------------------------------------------- */

/**
 * GitHub
 */
$form['github']['heading'] = __( 'GitHub', 'jr_translation' );

// [theme]
foreach( $themes as $theme_stylesheet => $theme_data ) {
  if( ! empty( $theme_data->get( 'UpdateURI' ) ) && strpos( parse_url( $theme_data->get( 'UpdateURI' ), PHP_URL_HOST ), 'github' ) !== false ) {

    foreach( $GLOBALS['wp_theme_directories'] as $theme_directory ) {
      if( file_exists( $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css' ) ) {
        $stylesheet_directory = $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css';
        break;
      }
    }

    $theme_headers = get_file_data( $stylesheet_directory, [
      'Update Owner'  => 'Update Owner',
      'Update Repo'   => 'Update Repo',
    ] );

    if( ! isset( $theme_headers['Update Owner'], $theme_headers['Update Repo'] ) ) {
      continue;
    }

    $form['github']['row'][implode( '-', [ $theme_headers['Update Owner'], 'token' ] )] = [
      'type'  => 'text',
      'label' => sprintf( __( '%s Token', 'jr_translation' ), $theme_headers['Update Owner'] ),
      'name'  => [ 'github', $theme_headers['Update Owner'], 'token' ],
    ];

  }
}

// [plugin]
foreach( $plugins as $plugin_file => $plugin_data ) {
  if( ! empty( $plugin_data['UpdateURI'] ) && strpos( parse_url( $plugin_data['UpdateURI'], PHP_URL_HOST ), 'github' ) !== false ) {

    $plugin_headers = get_file_data( WP_PLUGIN_DIR . '/' . $plugin_file, [
      'Update Owner'  => 'Update Owner',
      'Update Repo'   => 'Update Repo',
    ] );

    if( ! isset( $plugin_headers['Update Owner'], $plugin_headers['Update Repo'] ) ) {
      continue;
    }

    $form['github']['row'][implode( '-', [ $plugin_headers['Update Owner'], 'token' ] )] = [
      'type'  => 'text',
      'label' => sprintf( __( '%s Token', 'jr_translation' ), $plugin_headers['Update Owner'] ),
      'name'  => [ 'github', $plugin_headers['Update Owner'], 'token' ],
    ];

  }
}

/* -------------------------------------------- */

/**
 * Bitbucket
 */
$form['bitbucket']['heading'] = __( 'Bitbucket', 'jr_translation' );

// [theme]
foreach( $themes as $theme_stylesheet => $theme_data ) {
  if( ! empty( $theme_data->get( 'UpdateURI' ) ) && strpos( parse_url( $theme_data->get( 'UpdateURI' ), PHP_URL_HOST ), 'bitbucket' ) !== false ) {

    foreach( $GLOBALS['wp_theme_directories'] as $theme_directory ) {
      if( file_exists( $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css' ) ) {
        $stylesheet_directory = $theme_directory . '/' . $theme_stylesheet . '/' . 'style.css';
        break;
      }
    }

    $theme_headers = get_file_data( $stylesheet_directory, [
      'Update Owner'  => 'Update Owner',
      'Update Repo'   => 'Update Repo',
    ] );

    if( ! isset( $theme_headers['Update Owner'], $theme_headers['Update Repo'] ) ) {
      continue;
    }

    $form['bitbucket']['row'][implode( '-', [ $theme_headers['Update Owner'], '*', 'password' ] )] = [
      'type'  => 'text',
      'label' => sprintf( __( '%s Password', 'jr_translation' ), $theme_headers['Update Owner'] ),
      'name'  => [ 'bitbucket', $theme_headers['Update Owner'], '*', 'password' ],
    ];

    $form['bitbucket']['row'][implode( '-', [ $theme_headers['Update Owner'], $theme_headers['Update Repo'], 'token' ] )] = [
      'type'  => 'text',
      'label' => sprintf( __( '%s / %s Token', 'jr_translation' ), $theme_headers['Update Owner'], $theme_headers['Update Repo'] ),
      'name'  => [ 'bitbucket', $theme_headers['Update Owner'], $theme_headers['Update Repo'], 'token' ],
    ];

  }
}

// [plugin]
foreach( $plugins as $plugin_file => $plugin_data ) {
  if( ! empty( $plugin_data['UpdateURI'] ) && strpos( parse_url( $plugin_data['UpdateURI'], PHP_URL_HOST ), 'bitbucket' ) !== false ) {

    $plugin_headers = get_file_data( WP_PLUGIN_DIR . '/' . $plugin_file, [
      'Update Owner'  => 'Update Owner',
      'Update Repo'   => 'Update Repo',
    ] );

    if( ! isset( $plugin_headers['Update Owner'], $plugin_headers['Update Repo'] ) ) {
      continue;
    }

    $form['bitbucket']['row'][implode( '-', [ $plugin_headers['Update Owner'], '*', 'password' ] )] = [
      'type'  => 'text',
      'label' => sprintf( __( '%s Password', 'jr_translation' ), $plugin_headers['Update Owner'] ),
      'name'  => [ 'bitbucket', $plugin_headers['Update Owner'], '*', 'password' ],
    ];

    $form['bitbucket']['row'][implode( '-', [ $plugin_headers['Update Owner'], $plugin_headers['Update Repo'], 'token' ] )] = [
      'type'  => 'text',
      'label' => sprintf( __( '%s / %s Token', 'jr_translation' ), $plugin_headers['Update Owner'], $plugin_headers['Update Repo'] ),
      'name'  => [ 'bitbucket', $plugin_headers['Update Owner'], $plugin_headers['Update Repo'], 'token' ],
    ];

  }
}

//--------------------------------------------------------------------------------------------------

if(
  ! empty( $_POST ) &&
  check_admin_referer( $plugin_prefix . 'setting', $plugin_prefix . 'nonce' ) &&
  current_user_can( 'activate_plugins' )
) {

  $post_setting = self::save_admin_post( $form, $plugin_prefix );

  try {
    $option = $this->option;
    $option = wp_parse_args( $post_setting, $option );
    $this->option = $option;
    update_option( self::PLUGIN_OPTION, $option );

    add_settings_error( $plugin_prefix . 'error', $plugin_prefix . 'error', __( 'Saved' ), 'updated' );
  } catch( Exception $e ) {
    add_settings_error( $plugin_prefix . 'error', $plugin_prefix . 'error', $e->getMessage(), 'error' );
  }

  settings_errors( $plugin_prefix . 'error' );

}

//--------------------------------------------------------------------------------------------------
//  Admin Page
//--------------------------------------------------------------------------------------------------

?>
<div class="wrap">
  <h2><?= __( 'Updater Setting', 'jr_translation' ) ?></h2>
  <form action="" method="post">
<?php

  self::render_admin_form( $form, $plugin_prefix );

  wp_nonce_field( $plugin_prefix . 'setting', $plugin_prefix . 'nonce' );
  echo PHP_EOL;

  submit_button();
  echo PHP_EOL;

?>
  </form>
</div>
<?php if( WP_DEBUG ) : ?>

<hr>

<h3><?= __( 'GitHub', 'jr_translation' ) ?></h3>
<p><a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token" target="_blank"><?= __( 'Personal access tokens', 'jr_translation' ) ?></a></p>


<h3><?= __( 'Bitbucket', 'jr_translation' ) ?></h3>
<p><a href="https://support.atlassian.com/bitbucket-cloud/docs/create-an-app-password/" target="_blank"><?= __( 'App password', 'jr_translation' ) ?></a></p>
<p><a href="https://support.atlassian.com/bitbucket-cloud/docs/create-a-repository-access-token/" target="_blank"><?= __( 'Personal access tokens', 'jr_translation' ) ?></a></p>

<?php endif; ?>
