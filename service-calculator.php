<?php
/*
Plugin Name: Service calculator
Plugin URI: http://wpressgg.com/
Description: Service calculator is simple plugin that allows your site visitors to calculate your services prices.
Version: 1.0
Author: WPressGG
Author URI: http://wpressgg.com
License: GPLv2 or later
Text Domain: service-calculator
Domain Path: /localization/
*/

/**
 * Shortcode
 * @param unknown $lang
 * @return string
 */
function service_calculator_shortcode($lang) {
	global $wpdb;

	$t1 = __('Name', 'service-calculator');
	$t2 = __('Quantity', 'service-calculator');
	$t3 = __('Price', 'service-calculator');
	$t4 = __('Cauculate', 'service-calculator');
	$t5 = __('Reset', 'service-calculator');
	$t7 = __('Sum', 'service-calculator');

	$html = '<style>
	#calc-table td {
		vertical-align: middle;
	}
	#calc-table .hidden {
		display: none;
	}
	</style>

    <table border="0" width="100%" id="calc-table" rules="groups" cellspacing="0" cellpadding="5">
	<tbody>
	<tr>
	<td align="left" width="430"><b>'.$t1.'</b></td>
	<td align="left" width="100" colspan="2"><b>'.$t2.'</b></td>
	<td align="right" width="70"><b>'.$t3.'</b></td>
	</tr>';
	
	$pageposts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."gg_offer_calculator ORDER BY sort_order ASC", ""), OBJECT);
	
	foreach ($pageposts as $post) {
		$title = $post->name;
		$price = $post->price;
		$value = $post->value;
		
		if ($value) {
			$html .= '<tr>
			<td align="left" width="430">'.esc_attr($title).'</td>
			<td align="right" width="100">
				<input class="servicecalculatorval" name="s1" size="4" min="0" type="number" value="0" style="width:100%;max-width: 100%;" data-price="'.esc_attr($price).'"><span class="hidden"></span>
			</td>
			<td align="left" width="20">
				<span style="">'.esc_attr($value).'</span>
			</td>
			<td align="right" width="70">'.esc_attr($price).'</td>
			</tr>';
		} else {
			$html .= '<tr>
			<td colspan="4" align="left" height="30">
			<h4 style="margin-bottom:0px;"><strong>'.esc_attr($title).'</strong></h4>
			</td>
			</tr>';
		}
	}
	
	$html .= '<tr>
	<tr>
	<td></td>
	<td colspan="3" align="right" valign="top">
	<p style="margin-bottom:10px;"><span style="padding-bottom:5px;border-bottom: solid 1px #000;"><b>'.$t7.':</b> <b id="sum">0</b> <b></b></span></p>
	<input class="formbutt" name="button" onclick="servicecalculator();" type="button" value="'.$t4.'" style="margin-bottom:5px;"><br>
	<input class="formbutt" name="button" onclick="servicecalculatorareset();" type="button" value="'.$t5.'" style="margin-bottom:5px;"><br>
	</td>
	</tr>
		</tbody>
		</table>
	<script>
	jQuery("#calc-table .servicecalculatorval").on("blur", function() {
		servicecalculator();
	});
	function servicecalculator() {
		var sum = 0;
		jQuery("#calc-table .servicecalculatorval").each(function(i, value) {
			sum = sum+jQuery(value).val()*jQuery(value).data("price");
			jQuery(value).next().text(jQuery(value).val());
		});
		jQuery("#sum").text(sum);
	}
	function servicecalculatorareset() {
		jQuery("#calc-table .servicecalculatorval").each(function(i, value) {
			jQuery(value).val("0");
		});
		jQuery("#sum").text("0");
	}
	</script>';
	
	return $html;
}
add_shortcode('service_calculator', 'service_calculator_shortcode');

/**
 * Post data database process
 */
function service_calculator_post() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . "gg_offer_calculator";
	
	if (check_admin_referer("scnonce")) {
		if (isset($_POST['calc']) && $_POST['calc'] == "1") {
			if (isset($_POST['id'])) {
				$wpdb->update($table_name, array(
						'name' => sanitize_text_field($_POST['name']),
						'price' => sanitize_text_field($_POST['price']),
						'value' => sanitize_text_field($_POST['value'])
				),array(
						'id' => intval($_POST['id']))
				);
				
				?><meta http-equiv="refresh" content="0;url=<?php echo admin_url().'/options-general.php?page=service-calculator'; ?>" /><?php
				exit;
			} else {
				$wpdb->insert($table_name, array(
						'name' => sanitize_text_field($_POST['name']),
						'price' => sanitize_text_field($_POST['price']),
						'value' => sanitize_text_field($_POST['value'])
				),array(
						'%s',
						'%s',
						'%s')
				);
			}
		}
	}

}

/**
 * Add to admin menu
 */
function service_calculator_option_menu() {
	add_options_page( 'Service calculator', 'Service calculator', 'manage_options', 'service-calculator', 'service_calculator_options' );
}
add_action( 'admin_menu', 'service_calculator_option_menu' );

/**
 * Admin menu
 */
function service_calculator_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	if( isset($_POST['submit']) ) service_calculator_post();
	
	global $wpdb;
	$table_name = $wpdb->prefix . "gg_offer_calculator";
	
	wp_enqueue_script( 'service-calculator', plugins_url( "service-calculator.js", __FILE__ ), array('jquery-ui-sortable'), '2.1', true );
	
	$name = null;
	$value = null;
	$price = null;
	
	if (isset($_GET["task"])) {
		if (check_admin_referer("scnonce")) {
			if ($_GET["task"] == "edit") {
				if (isset($_GET['id'])) {
					$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."gg_offer_calculator WHERE id=%d ORDER BY sort_order ASC", intval($_GET["id"]), ""), OBJECT);
					if ($data) {
						$name = $data->name;
						$value = $data->value;
						$price = $data->price;
						if ($price == 0) {
							$price = "";
						}
					}
				}
			}
			if ($_GET["task"] == "delete") {
				$wpdb->delete( $wpdb->prefix."gg_offer_calculator", array("id" => intval($_GET["id"])));
				?><meta http-equiv="refresh" content="0;url=<?php echo admin_url().'/options-general.php?page=service-calculator'; ?>" /><?php
				exit;
			}
		}
	}
	?>
	<div class="wrap">
		<h1><?php echo __('Service calculator', 'service-calculator'); ?></h1>
		<p><?php echo __('Service calculator is wordpress plugin developed by', 'service-calculator'); ?> <a href="http://wpressgg.com/" target="_blank">WPressGG</a> <?php echo __('that allows you to create simple service calculator with service name, price e.t.c.', 'service-calculator'); ?>
		<br/><?php echo __('Use shortcode', 'service-calculator'); ?> <b>[service_calculator]</b> <?php echo __('to attach calculator to your page.', 'service-calculator'); ?></p>
	<?php 
		if ($name) {
			?>
			<h2 class="title"><?php echo __('Edit service pricing', 'service-calculator'); ?></h2>
			<?php
		} else {
			?>
			<h2 class="title"><?php echo __('Add service pricing', 'service-calculator'); ?></h2>
			<?php
		}
		?>
		<form method = "post" action = "">
		<?php 
		wp_nonce_field("scnonce");
		$nonce = wp_create_nonce( 'scnonce' );
		if ($name) {
			?><input type="hidden" name="id" value="<?php echo esc_attr($data->id) ?>"/><?php
		}
		?>
		<input type="hidden" name="calc" value="1"/>
		<table class="form-table">
		<tbody>
		<tr>
		<th scope="row"><label for="blogname"><?php echo __('Name', 'service-calculator'); ?> <span style="color:red;">*</span></label></th>
		<td><input name="name" type="text" id="blogname" value="<?php echo esc_attr($name) ?>" class="regular-text" required></td>
		</tr>
		<tr>
		<th scope="row"><label for="blogname"><?php echo __('Value', 'service-calculator'); ?></label></th>
		<td><input name="value" type="text" id="blogname" value="<?php echo esc_attr($value) ?>" class="regular-text"></td>
		</tr>
		<tr>
		<th scope="row"><label for="blogname"><?php echo __('Price', 'service-calculator'); ?></label></th>
		<td><input name="price" type="text" id="blogname" value="<?php echo esc_attr($price) ?>" class="regular-text"></td>
		</tr>
		</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Save', 'service-calculator'); ?>"></p>
		</form>

		<h3>List</h3>

		<table id="tblbrig" class="wp-list-table widefat fixed striped pages">
			<thead>
				<tr>
					<th><span><?php echo __('Name', 'service-calculator'); ?></span></th>
					<th><span><?php echo __('Value', 'service-calculator'); ?></span></th>
					<th><span><?php echo __('Price', 'service-calculator'); ?></span></th>
					<th><span><?php echo __('Action', 'service-calculator'); ?></span></th>
				</tr>
			</thead>
		
			<tbody id="the-list">
				<?php 
				$pageposts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."gg_offer_calculator ORDER BY sort_order ASC", ""), OBJECT);
				
				foreach ($pageposts as $post) {
					$postPrice = $post->price;
					if ($postPrice == 0) {
						$postPrice= "";
					}
					echo '<tr id="'.esc_attr($post->id).'" class="iedit author-self level-0 post-1064 type-page status-publish hentry">
					<td>
					<input type="hidden" name="id" value="'.esc_attr($post->id).'" />
					'.esc_attr($post->name).'</td>
					<td>'.esc_attr($post->value).'</td>
					<td>'.esc_attr($postPrice).'</td>
					<td><a href="'.admin_url().'/options-general.php?page=service-calculator&task=edit&id='.esc_attr($post->id).'&_wpnonce='.esc_attr($nonce).'">Edit</a> | 
					<a href="'.admin_url().'/options-general.php?page=service-calculator&task=delete&id='.esc_attr($post->id).'&_wpnonce='.esc_attr($nonce).'">Delete</a></td>
					</tr>';
				}
				?>
			</tbody>
		</table>
		</div>
	<?php
	
}

/**
 * Activate hook
 */
function service_calcuator_activate() {
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE `".$wpdb->prefix."gg_offer_calculator` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(250) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `value` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL
) ".$charset_collate.";";
	
	$path = str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, get_admin_url() );
	
	require_once($path.'includes/upgrade.php' );
	dbDelta( $sql );
}
register_activation_hook( __FILE__, 'service_calcuator_activate' );

/**
 * Deactiveate hook
 */
function service_calcuator_deactivate() {
	$sql = "DROP TABLE `".$wpdb->prefix."gg_offer_calculator`;";
	
	require_once($path.'includes/upgrade.php' );
	dbDelta( $sql );
}
register_deactivation_hook( __FILE__, 'service_calcuator_deactivate' );


if ( ! class_exists( 'service_calculator_ordering' ) ) :
class service_calculator_ordering {

	public static function get_instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
			self::_add_actions();
		}

		return $instance;
	}

	public function __construct() {  }

	public static function _add_actions() {
		add_action( 'load-edit.php', array( __CLASS__, 'load_edit_screen' ) );
		add_action( 'wp_ajax_service_calculator_ordering', array( __CLASS__, 'ajax_service_calculator_ordering' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
	}

	
	public static function load_textdomain() {
		load_plugin_textdomain( 'service-calculator', false, dirname( plugin_basename( __FILE__ ) ) . '/localization/' ); 
	}

	
	public static function load_edit_screen() {
		$screen = get_current_screen();
		$post_type = $screen->post_type;

		$sortable = ( post_type_supports( $post_type, 'page-attributes' ) || is_post_type_hierarchical( $post_type ) );		// check permission
		if ( ! $sortable = apply_filters( 'service_calculator_ordering_is_sortable', $sortable, $post_type ) ) {
			return;
		}

		if ( ! self::check_edit_others_caps( $post_type ) ) {
			return;
		}
	}

	public static function ajax_service_calculator_ordering() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . "gg_offer_calculator";
		
		$i = 0;
		foreach ($_POST["data"] as $id) {
			$i++;
			
			$wpdb->update($table_name, array(
					'sort_order' => $i,
			),array(
					'id' => $id)
			);
		}
		
		die();
	}

	private static function check_edit_others_caps( $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		$edit_others_cap = empty( $post_type_object ) ? 'edit_others_' . $post_type . 's' : $post_type_object->cap->edit_others_posts;
		return apply_filters( 'service_calculator_ordering_edit_rights', current_user_can( $edit_others_cap ), $post_type );
	}
}

service_calculator_ordering::get_instance();

endif;

