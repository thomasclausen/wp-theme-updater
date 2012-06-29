<?php /*
Plugin Name: THEME UPDATER
Description: Checks for newer versions of a self hosted theme. If updates are available custom update nags are displayed instead of the standard ones.
Version: 0.4
License: GPLv2
Author: Thomas Clausen
Author URI: http://www.thomasclausen.dk/wordpress/
*/

define( 'THEME_UPDATER_VERSION', '0.4' );
define( 'THEME_UPDATER_PLUGIN_URL', get_stylesheet_directory_uri() . '/' );

function convert_object_to_array( $object ) {
	if ( !is_object( $object ) && !is_array( $object ) ) {
		return $object;
	}
	if ( is_object( $object ) ) {
		$object = get_object_vars( $object );
	}
	return array_map( 'convert_object_to_array', $object );
}

function get_theme_current_data() {
	$current_theme = get_theme_data( get_template_directory() . '/style.css' );
	
	$current_theme_array = array(
		'Name' => $current_theme['Name'],
		'Version' => $current_theme['Version'],
		'Description' => $current_theme['Description'],
		'Author' => $current_theme['Author']
	);
	return $current_theme_array;
}

function get_theme_latest_data() {
	delete_transient( 'hovedspring-forbudt-theme-latest-data' ); // For tests only
	
	if ( !get_transient( 'hovedspring-forbudt-theme-latest-data' ) ) :
		$url = 'http://cdn.thomasclausen.dk/wordpress/themes/hovedspring-forbudt/';
		$response = trim( wp_remote_retrieve_body( wp_remote_get( $url ) ) );
		$response = maybe_unserialize( $response );

		$theme_update_array = array(
			'Name' => $response['name'],
			'Version' => $response['version'],
			'Changelog' => $response['changelog']
		);

		set_transient( 'hovedspring-forbudt-theme-latest-data', $theme_update_array, 60*60*24 );
	endif;
	
	return get_transient( 'hovedspring-forbudt-theme-latest-data' );
}

function theme_updater_admin_notice_theme() {
	if ( false !== strpos( $_SERVER['QUERY_STRING'], 'page=theme-updater.php' ) )
		return;
	if ( !current_user_can( 'read' ) )
		return;
	$theme_latest_data = get_theme_latest_data();
	$message = __( 'En nyere version at temaet "' . $theme_latest_data['Name'] . '" er tilg&aelig;ngelig.', 'theme-updater' );
	$link = sprintf( __( '<a href="%s" class="button-primary">L&aelig;s mere</a>', 'theme-updater' ), esc_url( admin_url( 'index.php?page=' . basename( __FILE__ ) ) ) );
	$dismiss = sprintf( __( '<a href="%s" class="close">Ignorer besked</a>', 'theme-updater' ), esc_url( admin_url( 'index.php?page=' . basename( __FILE__ ) . '&theme_updater_notice_theme_ignore=true' ) ) );
	echo '<div id="theme_updater_theme" class="updated theme-updater-message"><h3>' . $message . '</h3><p class="submit">' . $link . '</p>' . $dismiss . '</div>';
}

function theme_updater_dashboard_widget_theme() {
	if ( !current_user_can( 'read' ) )
		return;
	$theme_latest_data = get_theme_latest_data();
	$message = __( 'En nyere version at temaet "' . $theme_latest_data['Name'] . '" er tilg&aelig;ngelig.', 'theme-updater' );
	$link = sprintf( __( '<a href="%s" class="button-primary">L&aelig;s mere</a>', 'theme-updater' ), esc_url( admin_url( 'index.php?page=' . basename( __FILE__ ) ) ) );
	$dismiss = sprintf( __( '<a href="%s" class="close">Ignorer besked</a>', 'theme-updater' ), esc_url( admin_url( 'index.php?page=' . basename( __FILE__ ) . '&theme_updater_notice_core_ignore=true' ) ) );
	echo '<p>' . $message . '</p><p class="submit">' . $link . '</p>' . $dismiss;
}
function add_theme_updater_dashboard_widget_theme() {
	wp_add_dashboard_widget( 'theme_updater_dashboard_widget_theme', __( 'Theme Updater Dashboard Widget', 'theme-updater' ), 'theme_updater_dashboard_widget_theme' );
}

function theme_updater_notice_theme_ajax_update() {
	check_ajax_referer( 'theme_updater_notice_theme', 'security' );
	
	update_user_meta( get_current_user_id(), 'theme_updater_notice_theme', 'true' );
	// Would it be more correct to use the version number instead of just "true"?
	// That would make it possible for them to skip current but show next update notice

	die();
}
add_action( 'wp_ajax_theme_updater_notice_theme', 'theme_updater_notice_theme_ajax_update' );

function theme_updater_page() {
	$theme_latest_data = get_theme_latest_data();
	$theme_data = get_theme_current_data(); ?>
	<div class="wrap theme-updater">
		<h3 class="yellow"><?php _e( 'En nyere version af temaet "' . $theme_latest_data['Name'] . '" er klar.', 'theme-updater' ); ?><div class="nip nip-down-large"></div></h3>
		<div class="theme">
			<div class="image"><img src="<?php echo get_template_directory_uri(); ?>/screenshot.png" /></div>
			<div class="text">
				<h4><?php echo $theme_latest_data['Name']; ?> <?php echo $theme_data['Version']; ?></h4>
				<p><em><?php echo $theme_data['Description']; ?></em></p>
				<p><strong><?php _e( 'Fra:', 'theme-updater' ); ?> <?php echo $theme_data['Author']; ?></strong></p>
			</div>
			<h4><?php _e( 'Hvordan opdaterer jeg?', 'theme-updater' ); ?></h4>
			<p><?php _e( 'Den version af temaet "' . $theme_latest_data['Name'] . '" du har installeret er version ' . $theme_data['Version'] . ', men nu er version ' . $theme_latest_data['Version'] . ' tilg&aelig;ngelig, s&aring; vi anbefaler at du opdaterer til den nyeste version, men sp&oslash;rgsm&aring;let er s&aring; hvordan?', 'theme-updater' ); ?></p>
			<p><?php _e( 'Det er slet ikke s&aring; sv&aelig;rt! Det eneste du skal g&oslash;re er blot at kontakte ' . $theme_data['Author'] . ', som nok skal klare alt det tekniske for dig.', 'theme-updater' ); ?></p>
			<p><?php _e( '<strong>' . $theme_data['Author'] . '</strong><br />T: +45 60 95 34 92<br />E: <a href="mailto:kontakt@thomasclausen.dk">kontakt@thomasclausen.dk</a><br />W: <a href="' . $theme_data['AuthorURI'] . '">www.thomasclausen.dk</a>', 'theme-updater' ); ?></p>
			<h4><?php _e( 'Hvorfor skal jeg opdatere?', 'theme-updater' ); ?></h4>
			<p><?php _e( 'Der er flere vigtige grunde til altid at have den nyeste version installeret:', 'theme-updater' ); ?></p>
			<ul class="clearfix">
				<li><?php _e( '<strong>Fejl i koden</strong><br />' . $theme_data['Author'] . ' st&aring;r bag temaet og kan sit kram, men selvom temaet er testet i mange forskellige browsere (IE7, IE8, IE9, Firefox og Safari) p&aring; b&aring;de MAC og PC, for at fange eventuelle fejl inden teamet tages i brug, kan der alligevel snige sig en lille fejl ind i koden.<br />Det kan ogs&aring; v&aelig;re at temaet bliver brugt p&aring; en m&aring;de der ikke var taget h&oslash;jde for da det blev designet og programmeret.', 'theme-updater' ); ?></li>
				<li><?php _e( '<strong>Nye funktioner</strong><br />WordPress opdateres med j&aelig;vne mellemrum og fors&oslash;ger hele tiden at udvikle nye og smarte funktioner, som g&oslash;r det nemmere for dig som bruger at g&oslash;re hjemmesiden "til din egen". Blandt de funktioner er bl.a. mulighed for nemt at skifte baggrund og topbillede, styre hvilke menupunkter der vises i menuen og meget andet.<br /><em>OBS! Det er ikke n&oslash;dvendigvis funktioner, som dette tema benytter.</em>', 'theme-updater' ); ?></li>
				<li><?php _e( '<strong>Sikkerhed</strong><br />Udviklerne bag WordPress er dygtige og bruger lang tid p&aring; at g&oslash;re systemet s&aring; sikkert som overhovedet muligt, men WordPress er popul&aelig;rt og vil derfor altid v&aelig;re m&aring;l for ondsindede programm&oslash;rer, som vil fors&oslash;ge at finde en m&aring;de at f&aring; tvunget sig adgang til systemet - is&aelig;r &aelig;ldre versioner af WordPress.<br /><em>OBS! Det er v&aelig;sentlig lettere at lukke et evt. sikkerhedshul end at skulle gennemg&aring; hele hjemmesidens kode efter en hacker har haft adgang til at &aelig;ndre den.</em>', 'theme-updater' ); ?></li>
				<li><?php _e( '<strong>Optimeret kode</strong><br />P&aring; nettet g&aring;r udviklingen hurtig og der udvikles hele tiden nye metoder til at lave forskellige ting. Samme udvikling f&oslash;lger WordPress og det opdateres derfor med j&aelig;vne mellemrum for at g&oslash;re systemet hurtigere og mere stabilt.', 'theme-updater' ); ?></li>
				<li><?php _e( '<strong>Sikker opdatering</strong><br />Hver gang vi opdaterer en WordPress installation s&oslash;rger vi altid for at tage en backup i tilf&aelig;lde af at der skulle g&aring; noget galt i forbindelse med opdateringen.', 'theme-updater' ); ?></li>
			</ul>
			<p><?php _e( 'L&aelig;s "Hvad er &aelig;ndret?", hvis du er interesseret i at vide pr&aelig;cis, hvad der er &aelig;ndret i den nye version.', 'theme-updater' ); ?></p>
			<h4><?php _e( 'Hvad er &aelig;ndret?', 'theme-updater' ); ?></h4>
			<p><?php _e( 'I den nye version af "' . $theme_latest_data['Name'] . '" er der lavet f&oslash;lgende rettelser:', 'theme-updater' ); ?></p>
			<?php foreach ( $theme_latest_data['Changelog'] as $key1 => $value1 ) {
				echo '<p>';
				foreach ( $theme_latest_data['Changelog'][$key1] as $key2 => $value2 ) {
					if ( is_array( $value2 ) ) :
						echo '<ol>';
						foreach ( $theme_latest_data['Changelog'][$key1][$key2] as $key3 => $value3 ) {
							echo '<li>' . $value3 . '</li>';
						}
						echo '</ol>';
					elseif ( $key2 == 'change' ) :
						echo '<ol><li>' . $value2 . '</li></ol>';
					elseif ( $key2 == 'date' ) :
						echo ' (' . $value2 . ')';
					else :
						echo '<strong>' . $value2 . '</strong>';
					endif;
				}
				echo '</p>';
			} ?>
			<?php echo '<pre><!--';
			print_r( $theme_latest_data['Changelog'] );
			echo '--></pre>'; ?>
		</div>
	</div>
<?php }

function theme_updater_admin_css_and_js() {
	wp_register_style( 'theme-updater', THEME_UPDATER_PLUGIN_URL . 'theme-updater.css', array(), THEME_UPDATER_VERSION );
	wp_enqueue_style( 'theme-updater' );

	wp_register_script( 'theme-updater-script', THEME_UPDATER_PLUGIN_URL . 'theme-updater.js', array( 'jquery' ), THEME_UPDATER_VERSION );
	wp_enqueue_script( 'theme-updater-script' );
	wp_localize_script( 'theme-updater-script', 'themeupdaterAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'core_nonce' => wp_create_nonce( 'theme_updater_notice_core' ), 'theme_nonce' => wp_create_nonce( 'theme_updater_notice_theme' ) ) );
}

function theme_updater_init() {
	$theme_latest_data = get_theme_latest_data();
	$theme_data = get_theme_current_data();

	if ( version_compare( $theme_data['Version'], $theme_latest_data['Version'] ) == -1 ) {
		$hook = add_dashboard_page( $theme_latest_data['Name'] . ' Theme Updater', $theme_latest_data['Name'] . '<span class="update-plugins count-1"><span class="update-count">' . $theme_latest_data['Version'] . '</span></span>', 'read', basename( __FILE__ ), 'theme_updater_page' ); // capabilities: update_themes

		add_action( 'admin_enqueue_scripts', 'theme_updater_admin_css_and_js' );
	}

	$theme_updater_widget_theme = get_user_meta( get_current_user_id(), 'theme_updater_notice_theme', true );
	if ( version_compare( $theme_data['Version'], $theme_latest_data['Version'] ) == -1 && empty( $theme_updater_widget_theme ) ) {
		add_action( 'admin_notices', 'theme_updater_admin_notice_theme' );
		add_action( 'wp_dashboard_setup', 'add_theme_updater_dashboard_widget_theme' );
	}

	remove_action( 'admin_notices', 'update_nag', 3 );
}
add_action( 'admin_menu', 'theme_updater_init' ); ?>