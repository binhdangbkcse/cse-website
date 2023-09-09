<?php
/**
 * The7 "Image" widget for Elementor.
 *
 * @package The7
 */

namespace The7\Mods\Compatibility\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Utils;
use The7\Mods\Compatibility\Elementor\The7_Elementor_Widget_Base;
use The7\Mods\Compatibility\Elementor\Widget_Templates\Overlay_Template;
use The7\Mods\Compatibility\Elementor\Widget_Templates\Image_Aspect_Ratio;
use The7\Mods\Compatibility\Elementor\Widget_Templates\Image_Size;

defined( 'ABSPATH' ) || exit;

/**
 * Image class.
 */
class Image extends The7_Elementor_Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'the7-image-widget';
	}

	/**
	 * @return string
	 */
	protected function the7_title() {
		return esc_html__( 'Image', 'the7mk2' );
	}

	/**
	 * @return string[]
	 */
	protected function the7_keywords() {
		return [ 'image' ];
	}

	/**
	 * @return string
	 */
	protected function the7_icon() {
		return 'eicon-image';
	}

	/**
	 * @return string[]
	 */
	public function get_style_depends() {
		return [ 'the7-image-box-widget' ];
	}

	/**
	 * @return string[]
	 */
	public function get_script_depends() {
		return [ 'the7-image-box-widget' ];
	}

	/**
	 * @return void
	 */
	protected function register_controls() {
		// Content.
		$this->add_content_controls();
		$this->template( Overlay_Template::class )->add_controls();

		// Style.
		$this->add_image_style_controls();
		$this->add_transform_style_controls();
	}

	/**
	 * @return void
	 */
	protected function add_content_controls() {
		$this->start_controls_section(
			'section_image',
			[
				'label' => esc_html__( 'Content', 'the7mk2' ),
			]
		);

		$this->add_control(
			'image',
			[
				'label'   => esc_html__( 'Image', 'the7mk2' ),
				'type'    => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
				],
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$this->template( Image_Size::class )->add_style_controls();

		$this->add_responsive_control(
			'image_align',
			[
				'label'                => esc_html__( 'Alignment', 'the7mk2' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => [
					'left'   => [
						'title' => esc_html__( 'Left', 'the7mk2' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'the7mk2' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'the7mk2' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'prefix_class'         => 'content-align%s-',
				'default'              => 'left',
				'selectors_dictionary' => [
					'left'   => 'align-items: flex-start; text-align: left;',
					'center' => 'align-items: center; text-align: center;',
					'right'  => 'align-items: flex-end; text-align: right;',
				],
				'selectors'            => [
					'{{WRAPPER}} .the7-image-container' => ' {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_link_heading',
			[
				'label'     => esc_html__( 'Link & Hover', 'the7mk2' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'link_note',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				'raw'             => esc_html__( 'Don’t add links to The7 Overlay Template used for this widget - it will break the layout.', 'the7mk2' ),
				'condition'       => [
					Overlay_Template::TEMPLATE_CONTROL_KEY . '!' => '',
					'link_to!' => 'none',
				],
			]
		);

		$this->add_control(
			'link_to',
			[
				'label'   => esc_html__( 'Link', 'the7mk2' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'   => esc_html__( 'None', 'the7mk2' ),
					'file'   => esc_html__( 'Media File', 'the7mk2' ),
					'custom' => esc_html__( 'Custom URL', 'the7mk2' ),
				],
			]
		);

		$this->add_control(
			'image_link',
			[
				'label'       => esc_html__( 'Link', 'the7mk2' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => [
					'active' => true,
				],
				'condition'   => [
					'link_to' => 'custom',
				],
				'placeholder' => esc_html__( 'https://your-link.com', 'the7mk2' ),
			]
		);

		$this->add_control(
			'open_lightbox',
			[
				'label'       => esc_html__( 'Lightbox', 'the7mk2' ),
				'type'        => Controls_Manager::SELECT,
				'description' => sprintf(
					/* translators: 1: Link open tag, 2: Link close tag. */
					esc_html__( 'Manage your site’s lightbox settings in the %1$sLightbox panel%2$s.', 'the7mk2' ),
					'<a href="javascript: $e.run( \'panel/global/open\' ).then( () => $e.route( \'panel/global/settings-lightbox\' ) )">',
					'</a>'
				),
				'default'     => 'default',
				'options'     => [
					'default' => esc_html__( 'Default', 'the7mk2' ),
					'yes'     => esc_html__( 'Yes', 'the7mk2' ),
					'no'      => esc_html__( 'No', 'the7mk2' ),
				],
				'condition'   => [
					'link_to' => 'file',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function add_image_style_controls() {
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => esc_html__( 'Image', 'the7mk2' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'image_size',
			[
				'label'      => esc_html__( 'Max Width', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 5,
						'max' => 1030,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .the7-image-wrapper' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->template( Image_Aspect_Ratio::class )->add_style_controls();

		$this->add_control(
			'image_style_title',
			[
				'label'     => esc_html__( 'Style', 'the7mk2' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'image_padding',
			[
				'label'      => esc_html__( 'Padding', 'the7mk2' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .the7-image-wrapper img' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'image_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'the7mk2' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .the7-image-wrapper img' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; border-style:solid;',
				],
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'the7mk2' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .the7-image-wrapper, {{WRAPPER}} .post-thumbnail-rollover, {{WRAPPER}} .post-thumbnail-rollover img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'image_overlay_colors' );

		$this->start_controls_tab(
			'image_overlay_colors_normal',
			[
				'label' => esc_html__( 'Normal', 'the7mk2' ),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'overlay_background',
				'types'          => [ 'classic', 'gradient' ],
				'exclude'        => [ 'image' ],
				'fields_options' => [
					'background' => [
						'label' => esc_html__( 'Overlay', 'the7mk2' ),
					],
				],
				'selector'       => '{{WRAPPER}} .post-thumbnail-rollover:before, {{WRAPPER}} .post-thumbnail-rollover:after { transition: none; }
				{{WRAPPER}} .post-thumbnail-rollover:before,
				{{WRAPPER}} .post-thumbnail-rollover:after
				',
			]
		);

		$this->add_control(
			'border_color',
			[
				'label'     => esc_html__( 'Border Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .the7-image-wrapper img' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .the7-image-wrapper img' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_shadow',
				'selector' => '{{WRAPPER}} .the7-image-wrapper img',
			]
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'     => 'image_filters',
				'selector' => '
				{{WRAPPER}} .post-thumbnail-rollover img
				',
			]
		);

		$this->add_control(
			'image_opacity',
			[
				'label'      => esc_html__( 'Image opacity', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => '%',
					'size' => '100',
				],
				'size_units' => [ '%' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .post-thumbnail-rollover img' => 'opacity: calc({{SIZE}}/100)',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'image_overlay_colors_hover',
			[
				'label' => esc_html__( 'Hover', 'the7mk2' ),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'overlay_hover_background',
				'types'          => [ 'classic', 'gradient' ],
				'exclude'        => [ 'image' ],
				'fields_options' => [
					'background' => [
						'label' => esc_html__( 'Overlay', 'the7mk2' ),
					],
					'color'      => [
						'selectors' => [
							'
							{{SELECTOR}},
							{{WRAPPER}} .post-thumbnail-rollover:before { transition: all var(--transition-overlay-duration-out, 0.3s) var(--transition-overlay-timing, ease); } {{WRAPPER}} .post-thumbnail-rollover:after { transition: all var(--transition-overlay-duration, 0.3s) var(--transition-overlay-timing, ease); } {{SELECTOR}}' => 'background: {{VALUE}};',
						],
					],

				],
				'selector'       => '{{WRAPPER}} .post-thumbnail-rollover:after',
			]
		);

		$this->add_control(
			'hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .the7-image-wrapper:hover img' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_hover_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'the7mk2' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .the7-image-wrapper:hover img' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_hover_shadow',
				'selector' => '{{WRAPPER}} .the7-image-wrapper:hover img',
			]
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name'     => 'image_hover_filters',
				'selector' => '{{WRAPPER}} .the7-image-wrapper:hover img
				',
			]
		);

		$this->add_control(
			'image_hover_opacity',
			[
				'label'      => esc_html__( 'Image opacity', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => '%',
					'size' => '100',
				],
				'size_units' => [ '%' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'
					{{WRAPPER}} .the7-image-wrapper:hover img ' => 'opacity: calc({{SIZE}}/100)',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
	}

	/**
	 * Add transform style controls.
	 *
	 * @return void
	 */
	protected function add_transform_style_controls() {
		$this->start_controls_section(
			'section_transform',
			[
				'label' => esc_html__( 'Transform', 'the7mk2' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'image_overflow',
			[
				'label'        => esc_html__( 'Exceed Image Frame', 'the7mk2' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'the7mk2' ),
				'label_off'    => esc_html__( 'Off', 'the7mk2' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'selectors'    => [
					'{{WRAPPER}} .post-thumbnail-rollover, {{WRAPPER}} .the7-image-wrapper' => 'overflow: visible;',
				],
				'prefix_class' => 'exceeding-frame-',
			]
		);

		$this->add_control(
			'transform_overlay',
			[
				'label'        => esc_html__( 'Transform Overlay Template', 'the7mk2' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'the7mk2' ),
				'label_off'    => esc_html__( 'Off', 'the7mk2' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'prefix_class' => 'transform-overlay-',
			]
		);
		$this->add_control(
			'image_overlay_transition_duration',
			[
				'label'      => esc_html__( 'Overly Fade In Duration', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'ms',
					'size' => '300',
				],
				'size_units' => [ 'ms' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--transition-overlay-duration: {{SIZE}}{{UNIT}}',
				],
			]
		);
		$this->add_control(
			'image_overlay_transition_duration_out',
			[
				'label'      => esc_html__( 'Overlay Fade Out Duration', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'ms',
					'size' => '300',
				],
				'size_units' => [ 'ms' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--transition-overlay-duration-out: {{SIZE}}{{UNIT}}',
				],
			]
		);
		$this->add_control(
			'image_forward_transition_duration',
			[
				'label'      => esc_html__( 'Forward Transition Duration', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'ms',
					'size' => '300',
				],
				'size_units' => [ 'ms' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--transition-img-forward-duration: {{SIZE}}{{UNIT}}',
				],
			]
		);
		$this->add_control(
			'image_back_transition_duration',
			[
				'label'      => esc_html__( 'Back Transition Duration', 'the7mk2' ),
				'type'       => Controls_Manager::SLIDER,
				'default'    => [
					'unit' => 'ms',
					'size' => '300',
				],
				'size_units' => [ 'ms' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}' => '--transition-img-back-duration: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'image_overlay_transition_function',
			[
				'label'     => esc_html__( 'Transition timing function', 'the7mk2' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ease-out',
				'options'   => [
					'ease'        => esc_html__( 'Ease', 'the7mk2' ),
					'ease-in'     => esc_html__( 'Ease in', 'the7mk2' ),
					'ease-out'    => esc_html__( 'Ease out', 'the7mk2' ),
					'ease-in-out' => esc_html__( 'Ease in out', 'the7mk2' ),
					'linear'      => esc_html__( 'Linear', 'the7mk2' ),
				],
				'selectors' => [
					'{{WRAPPER}}' => '--transition-overlay-timing: {{VALUE}}',
				],
			]
		);

		$this->start_controls_tabs( 'image_colors' );

		foreach ( [ '', '_hover' ] as $tab ) {
			$state = $tab === '_hover' ? ':hover' : '';

			$this->start_controls_tab(
				"image_tab_positioning{$tab}",
				[
					'label' => $tab === '' ? esc_html__( 'Normal', 'the7mk2' ) : esc_html__( 'Hover', 'the7mk2' ),
				]
			);

			$this->add_control(
				"image_transform_rotate_popover{$tab}",
				[
					'label' => esc_html__( 'Rotate', 'the7mk2' ),
					'type'  => Controls_Manager::POPOVER_TOGGLE,
				]
			);

			$this->start_popover();

			$this->add_responsive_control(
				"image_transform_rotateZ_effect{$tab}",
				[
					'label'              => esc_html__( 'Rotate', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min' => -360,
							'max' => 360,
						],
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-rotateZ: {{SIZE}}deg',
					],
					'condition'          => [
						"image_transform_rotate_popover{$tab}!" => '',
					],
					'frontend_available' => true,
				]
			);

			$this->add_control(
				"image_transform_rotate_3d{$tab}",
				[
					'label'     => esc_html__( '3D Rotate', 'the7mk2' ),
					'type'      => Controls_Manager::SWITCHER,
					'label_on'  => esc_html__( 'On', 'the7mk2' ),
					'label_off' => esc_html__( 'Off', 'the7mk2' ),
					'selectors' => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-rotateX: 1{{UNIT}};  --the7-transform-perspective: 20px;',
					],
					'condition' => [
						"image_transform_rotate_popover{$tab}!" => '',
					],
				]
			);

			$this->add_responsive_control(
				"image_transform_rotateX_effect{$tab}",
				[
					'label'              => esc_html__( 'Rotate X', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min' => -360,
							'max' => 360,
						],
					],
					'condition'          => [
						"image_transform_rotate_3d{$tab}!" => '',
						"image_transform_rotate_popover{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-rotateX: {{SIZE}}deg;',
					],
					'frontend_available' => true,
				]
			);

			$this->add_responsive_control(
				"image_transform_rotateY_effect{$tab}",
				[
					'label'              => esc_html__( 'Rotate Y', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min' => -360,
							'max' => 360,
						],
					],
					'condition'          => [
						"image_transform_rotate_3d{$tab}!" => '',
						"image_transform_rotate_popover{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-rotateY: {{SIZE}}deg;',
					],
					'frontend_available' => true,
				]
			);

			$this->add_responsive_control(
				"image_transform_perspective_effect{$tab}",
				[
					'label'              => esc_html__( 'Perspective', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min' => 0,
							'max' => 1000,
						],
					],
					'condition'          => [
						"image_transform_rotate_popover{$tab}!" => '',
						"image_transform_rotate_3d{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-perspective: {{SIZE}}px',
					],
					'frontend_available' => true,
				]
			);

			$this->end_popover();

			$this->add_control(
				"image_transform_translate_popover{$tab}",
				[
					'label' => esc_html__( 'Offset', 'the7mk2' ),
					'type'  => Controls_Manager::POPOVER_TOGGLE,
				]
			);

			$this->start_popover();

			$this->add_responsive_control(
				"image_transform_translateX_effect{$tab}",
				[
					'label'              => esc_html__( 'Offset X', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'size_units'         => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
					'range'              => [
						'%'  => [
							'min' => -100,
							'max' => 100,
						],
						'px' => [
							'min' => -1000,
							'max' => 1000,
						],
					],
					'condition'          => [
						"image_transform_translate_popover{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-translateX: {{SIZE}}{{UNIT}};',
					],
					'frontend_available' => true,
				]
			);

			$this->add_responsive_control(
				"image_transform_translateY_effect{$tab}",
				[
					'label'              => esc_html__( 'Offset Y', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'size_units'         => [ 'px', '%', 'em', 'rem', 'vh', 'custom' ],
					'range'              => [
						'%'  => [
							'min' => -100,
							'max' => 100,
						],
						'px' => [
							'min' => -1000,
							'max' => 1000,
						],
					],
					'condition'          => [
						"image_transform_translate_popover{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-translateY: {{SIZE}}{{UNIT}};',
					],
					'frontend_available' => true,
				]
			);

			$this->end_popover();

			$this->add_control(
				"image_transform_scale_popover{$tab}",
				[
					'label' => esc_html__( 'Scale', 'the7mk2' ),
					'type'  => Controls_Manager::POPOVER_TOGGLE,
				]
			);

			$this->start_popover();

			$this->add_control(
				"image_transform_keep_proportions{$tab}",
				[
					'label'     => esc_html__( 'Keep Proportions', 'the7mk2' ),
					'type'      => Controls_Manager::SWITCHER,
					'label_on'  => esc_html__( 'On', 'the7mk2' ),
					'label_off' => esc_html__( 'Off', 'the7mk2' ),
					'default'   => 'yes',
				]
			);

			$this->add_responsive_control(
				"image_transform_scale_effect{$tab}",
				[
					'label'              => esc_html__( 'Scale', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min'  => 0,
							'max'  => 2,
							'step' => 0.1,
						],
					],
					'condition'          => [
						"image_transform_scale_popover{$tab}!" => '',
						"image_transform_keep_proportions{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-scale: {{SIZE}};',
					],
					'frontend_available' => true,
				]
			);

			$this->add_responsive_control(
				"image_transform_scaleX_effect{$tab}",
				[
					'label'              => esc_html__( 'Scale X', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min'  => 0,
							'max'  => 2,
							'step' => 0.1,
						],
					],
					'condition'          => [
						"image_transform_scale_popover{$tab}!" => '',
						"image_transform_keep_proportions{$tab}" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-scaleX: {{SIZE}};',
					],
					'frontend_available' => true,
				]
			);

			$this->add_responsive_control(
				"image_transform_scaleY_effect{$tab}",
				[
					'label'              => esc_html__( 'Scale Y', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min'  => 0,
							'max'  => 2,
							'step' => 0.1,
						],
					],
					'condition'          => [
						"image_transform_scale_popover{$tab}!" => '',
						"image_transform_keep_proportions{$tab}" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-scaleY: {{SIZE}};',
					],
					'frontend_available' => true,
				]
			);

			$this->end_popover();

			$this->add_control(
				"image_transform_skew_popover{$tab}",
				[
					'label' => esc_html__( 'Skew', 'the7mk2' ),
					'type'  => Controls_Manager::POPOVER_TOGGLE,
				]
			);

			$this->start_popover();

			$this->add_responsive_control(
				"image_transform_skewX_effect{$tab}",
				[
					'label'              => esc_html__( 'Skew X', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min' => -360,
							'max' => 360,
						],
					],
					'condition'          => [
						"image_transform_skew_popover{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-skewX: {{SIZE}}deg;',
					],
					'frontend_available' => true,
				]
			);

			$this->add_responsive_control(
				"image_transform_skewY_effect{$tab}",
				[
					'label'              => esc_html__( 'Skew Y', 'the7mk2' ),
					'type'               => Controls_Manager::SLIDER,
					'range'              => [
						'px' => [
							'min' => -360,
							'max' => 360,
						],
					],
					'condition'          => [
						"image_transform_skew_popover{$tab}!" => '',
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-skewY: {{SIZE}}deg;',
					],
					'frontend_available' => true,
				]
			);

			$this->end_popover();

			$this->add_control(
				"image_transform_flipX_effect{$tab}",
				[
					'label'              => esc_html__( 'Flip Horizontal', 'the7mk2' ),
					'type'               => Controls_Manager::CHOOSE,
					'options'            => [
						'transform' => [
							'title' => esc_html__( 'Flip Horizontal', 'the7mk2' ),
							'icon'  => 'eicon-flip eicon-tilted',
						],
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-flipX: -1',
					],
					'frontend_available' => true,
				]
			);

			$this->add_control(
				"image_transform_flipY_effect{$tab}",
				[
					'label'              => esc_html__( 'Flip Vertical', 'the7mk2' ),
					'type'               => Controls_Manager::CHOOSE,
					'options'            => [
						'transform' => [
							'title' => esc_html__( 'Flip Vertical', 'the7mk2' ),
							'icon'  => 'eicon-flip',
						],
					],
					'selectors'          => [
						"{{WRAPPER}} .the7-image-wrapper{$state}" => '--the7-transform-flipY: -1',
					],
					'frontend_available' => true,
				]
			);

			$this->end_controls_tab();
		}

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$overlay_template_id = $this->template( Overlay_Template::class )->get_template_id();

		$img_wrapper_class = implode(
			' ',
			array_filter(
				[
					'post-thumbnail-rollover',
					$this->template( Image_Size::class )->get_wrapper_class(),
					$this->template( Image_Aspect_Ratio::class )->get_wrapper_class(),
				]
			)
		);

		if ( $overlay_template_id ) {
			$img_wrapper_class .= ' ' . $this->template( Overlay_Template::class )->get_wrapper_class();
		}

		$link = $this->get_link_url( $settings );
		if ( $link ) {
			$this->add_link_attributes( 'link', $link );
			if ( $settings['link_to'] === 'file' && ! empty( $settings['image']['id'] ) ) {
				$this->add_lightbox_data_attributes( 'link', $settings['image']['id'], $settings['open_lightbox'] );
			}
			$image_wrapper       = '<a class="' . esc_attr( $img_wrapper_class ) . '" ' . $this->get_render_attribute_string( 'link' ) . '>';
			$image_wrapper_close = '</a>';
		} else {
			$image_wrapper       = '<div class="' . esc_attr( $img_wrapper_class ) . '">';
			$image_wrapper_close = '</div>';
		}

		echo '<div class="the7-image-container">';
		echo '<div class="the7-image-wrapper the7-elementor-widget">';

		if ( ! empty( $settings['image']['id'] ) ) {
			echo $image_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->template( Image_Size::class )->get_image( $settings['image']['id'] );

			if ( $overlay_template_id ) {
				wp_enqueue_script( 'the7-overlay-template' );
				echo $this->template( Overlay_Template::class )->get_render( (int) $settings['image']['id'] );
			}

			echo $image_wrapper_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Retrieve image widget link URL.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @return array|string|false An array/string containing the link URL, or false if no link.
	 */
	protected function get_link_url( $settings ) {
		if ( ! $settings['link_to'] || $settings['link_to'] === 'none' ) {
			return false;
		}

		if ( $settings['link_to'] === 'custom' ) {
			if ( empty( $settings['image_link']['url'] ) ) {
				return false;
			}

			return $settings['image_link'];
		}

		return [
			'url' => $settings['image']['url'],
		];
	}
}
