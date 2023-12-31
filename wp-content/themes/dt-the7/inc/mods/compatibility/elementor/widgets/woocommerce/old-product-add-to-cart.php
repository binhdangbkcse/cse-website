<?php
/*
 * The7 elements product add to cart widget for Elementor.
 *
 * @package The7
 */

namespace The7\Mods\Compatibility\Elementor\Widgets\Woocommerce;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use The7\Mods\Compatibility\Elementor\The7_Elementor_Widget_Base;

defined( 'ABSPATH' ) || exit;

class Old_Product_Add_To_Cart extends The7_Elementor_Widget_Base {

	public function get_name() {
		return 'the7-woocommerce-product-add-to-cart';
	}

	protected function the7_title() {
		return esc_html__( 'Old Add To Cart', 'the7mk2' );
	}

	protected function the7_icon() {
		return 'eicon-product-add-to-cart';
	}

	protected function the7_keywords() {
		return [ 'woocommerce', 'shop', 'store', 'cart', 'product', 'button', 'add to cart'];
	}

	/**
	 * Get the7 widget categories.
	 *
	 * @return string[]
	 */
	protected function the7_categories() {
		return [ 'woocommerce-elements', 'woocommerce-elements-single' ];
	}

	public function render_plain_content() {
	}


	protected function register_controls() {
		$this->start_controls_section( 'section_product_tabs_style', [
			'label' => esc_html__( 'Styles', 'the7mk2' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'wc_style_warning', [
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => esc_html__( 'The style of this widget is often can be affected by thirdparty plugins. If you experience any such issue, try to deactivate related plugins.', 'the7mk2' ),
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		] );
		$this->end_controls_section();
	}

	protected function render() {
		global $product;

		$product = wc_get_product();

		if ( empty( $product ) ) {
			return;
		}
		?>

		<div class="the7-elementor-widget the7-elementor-product-<?php echo esc_attr( wc_get_product()->get_type() ); ?>">
			<?php woocommerce_template_single_add_to_cart(); ?>
		</div>

		<?php
	}
}
