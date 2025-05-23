<?php

/**
 * Class SampleTest
 *
 * @package Acf_Icon_Picker
 */

/**
 * Sample test case.
 */
class TestPlugin extends \WP_UnitTestCase
{
	/**
	 * The full path to the main plugin file.
	 *
	 * @type string $plugin_file
	 */
	protected $plugin_file;

	/**
	 * Test if the plugin is loaded.
	 */
	public function test_plugin_class()
	{
		$this->assertTrue(class_exists('SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker'));
	}

	/**
	 * Test if field type is active.
	 */
	public function test_is_field_type_active()
	{
		$field_types = acf_get_field_types();
		$this->assertArrayHasKey('svg_icon_picker', $field_types);
	}

	/**
	 * Test if the plugin collects the SVG files from the parent theme.
	 */
	public function test_found_files_in_parent_theme()
	{
		switch_theme('test-theme');

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs   = $plugin->svgs;
		$this->assertNotEmpty($svgs);
		$count = count($svgs);
		$this->assertEquals(5, $count);
	}

	/**
	 * Test if the plugin collects the SVG files from both the parent and child theme.
	 * The parent theme has 4 SVG files, and the child theme has 2 SVG files but one of them is the same as the parent theme.
	 * Thus the child theme icon should be used totalling to 5 icons.
	 */
	public function test_found_files_in_child_theme()
	{
		switch_theme('test-child-theme');

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs   = $plugin->svgs;
		$this->assertNotEmpty($svgs);
		$count = count($svgs);
		$this->assertEquals(6, $count);
	}

	/**
	 * Test if the plugin collects the SVG files from the parent theme and the child theme and if the correct paths are used.
	 */
	public function test_found_files_override()
	{
		switch_theme('test-child-theme');

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs   = $plugin->svgs;

		// exists in both child and parent theme, thus child theme should be used
		$discord = $svgs['discord'];
		$this->assertEquals('http://example.org/wp-content/themes/test-child-theme/icons/discord.svg', $discord['url']);

		// exists only in parent theme, should be used
		$facebook = $svgs['linkedin'];
		$this->assertEquals('http://example.org/wp-content/themes/test-theme/icons/linkedin.svg', $facebook['url']);
	}

	/**
	 * Test if the plugin collects the SVG files from the parent theme when a custom folder is set.
	 */
	public function test_custom_theme_dirs()
	{
		switch_theme('test-child-theme');

		add_filter('acf_svg_icon_picker_folder', function () {
			return 'custom-icons/';
		});

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs   = $plugin->svgs;

		$this->assertNotEmpty($svgs);
		$count = count($svgs);
		$this->assertEquals(4, $count);

		$chain = $svgs['chain'];
		$this->assertEquals('http://example.org/wp-content/themes/test-child-theme/custom-icons/chain.svg', $chain['url']);
	}

	public function test_get_svg_icon_uri_helper_function()
	{
		switch_theme('test-child-theme');
		$icon_uri = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri('amazon');
		$this->assertEquals('http://example.org/wp-content/themes/test-child-theme/icons/amazon.svg', $icon_uri);
	}

	public function test_get_svg_icon_path_helper_function()
	{
		switch_theme('test-child-theme');
		$icon_path = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_path('linkedin');
		$this->assertEquals(WP_CONTENT_DIR . '/themes/test-theme/icons/linkedin.svg', $icon_path);
	}

	public function test_get_svg_icon_uri_non_existent_icon()
	{
		switch_theme('test-child-theme');
		$icon_uri = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon_uri('non-existent-icon');
		$this->assertEquals('', $icon_uri);
	}

	public function test_get_svg_icon_helper_function()
	{
		switch_theme('test-child-theme');
		$icon = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon('amazon');
		$this->assertStringContainsString('<svg', $icon);
	}


	public function test_get_svg_icon_helper_function_custom_path()
	{
		switch_theme('test-child-theme');
		add_filter('acf_svg_icon_picker_custom_location', function () {
			return [
				'path' => WP_CONTENT_DIR . '/random-location-icons/',
				'url'  => content_url() . '/random-location-icons/',
			];
		});

		$icon = SmithfieldStudio\AcfSvgIconPicker\get_svg_icon('bell');
		$this->assertStringContainsString('<svg', $icon);
	}

	/**
	 * Test if the deprecated filters are correctly forwarded to the new filter.
	 *
	 * @expectedDeprecated acf_icon_path_suffix
	 */
	public function test_deprecated_filters()
	{
		switch_theme('test-child-theme');

		add_filter('acf_icon_path_suffix', function () {
			return 'custom-icons/';
		});

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs   = $plugin->svgs;

		$this->assertNotEmpty($svgs);
		$count = count($svgs);
		$this->assertEquals(4, $count);

		remove_filter('acf_icon_path_suffix', function () {
			return 'custom-icons/';
		});
	}

	/**
	 * Test if the _doing_it_wrong() function is called when the custom location filter is not used correctly.
	 */
	public function test_custom_dir_override_wrong_filter_usage()
	{
		switch_theme('test-child-theme');

		add_filter('acf_svg_icon_picker_custom_location', function () {
			return 'custom-icons/';
		});

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs   = $plugin->svgs;
		$this->setExpectedIncorrectUsage('check_priority_dir');
	}

	/**
	 * Test if the plugin collects the SVG files from a custom location.
	 */
	public function test_custom_dir_override()
	{
		switch_theme('test-child-theme');

		add_filter('acf_svg_icon_picker_custom_location', function () {
			return [
				'path' => WP_CONTENT_DIR . '/random-location-icons/',
				'url'  => content_url() . '/random-location-icons/',
			];
		});

		$plugin = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$svgs   = $plugin->svgs;

		$this->assertNotEmpty($svgs);
		$count = count($svgs);
		$this->assertEquals(1, $count);

		$bell = $svgs['bell'];
		$this->assertEquals('http://example.org/wp-content/random-location-icons/bell.svg', $bell['url']);
	}

	/**
	 * Test if the plugin finds an icon using the sanitised and legacy (unsanitised) acf value
	 */
	public function test_legacy_keys_still_find_icons()
	{
		switch_theme('test-theme');

		$plugin          = new SmithfieldStudio\AcfSvgIconPicker\ACF_Field_Svg_Icon_Picker();
		$icon_key        = 'thunder-storm';
		$legacy_icon_key = 'thunder storm';

		$icon = $plugin->get_icon_data($icon_key);
		$this->assertNotEmpty($icon);

		$legacy_icon = $plugin->get_icon_data($legacy_icon_key);
		$this->assertNotEmpty($legacy_icon);
	}

	public function testACFFieldSaveAndReturnValue()
	{
		// create a new field group
		acf_add_local_field_group([
			'key'      => 'group_svg_icon_picker',
			'title'    => 'SVG Icon Picker',
			'fields'   => [
				[
					'key'           => 'field_svg_icon_picker',
					'label'         => 'SVG Icon Picker',
					'name'          => 'svg_icon_picker',
					'return_format' => 'value',
					'type'          => 'svg_icon_picker',
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					],
				],
			],
		]);

		// create a new post
		$post_id = $this->factory->post->create();

		// set the field value
		update_field('svg_icon_picker', 'bell', $post_id);

		// get the field value
		$field_value = get_field('svg_icon_picker', $post_id);
		$this->assertEquals('bell', $field_value);
	}

	public function testACFFieldSaveAndReturnSVG()
	{
		switch_theme('test-theme');
		// create a new field group
		acf_add_local_field_group([
			'key'      => 'group_svg_icon_picker_2',
			'title'    => 'SVG Icon Picker',
			'fields'   => [
				[
					'key'           => 'field_svg_icon_picker_svg_test',
					'label'         => 'SVG Icon Picker',
					'name'          => 'svg_icon_picker',
					'return_format' => 'icon',
					'type'          => 'svg_icon_picker',
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					],
				],
			],
		]);

		// create a new post
		$post_id = $this->factory->post->create();

		// set the field value
		update_field('field_svg_icon_picker_svg_test', 'discord', $post_id);

		// get file in /assets/themes/test-theme/icons/discord.svg
		$discord_svg = file_get_contents(WP_CONTENT_DIR . '/themes/test-theme/icons/discord.svg');

		$field_value = get_field('field_svg_icon_picker_svg_test', $post_id);
		$this->assertEquals($discord_svg, $field_value);
	}
}
