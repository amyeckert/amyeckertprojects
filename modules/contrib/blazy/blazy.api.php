<?php

/**
 * @file
 * Hooks and API provided by the Blazy module.
 */

/**
 * @defgroup blazy_api Blazy API
 * @{
 * Information about the Blazy usages.
 *
 * Modules may implement any of the available hooks to interact with Blazy.
 * Blazy may be configured using the web interface using formatters, or Views.
 * However below is a few sample coded ones as per Blazy RC2+.
 *
 * A single image sample.
 * @code
 * function my_module_render_blazy() {
 *   $settings = [
 *     // URI is stored in #settings property so to allow traveling around video
 *     // and lightboxes before being passed into theme_blazy().
 *     'uri' => 'public://logo.jpg',
 *
 *     // Explicitly request for Blazy.
 *     // This allows Slick lazyLoad to not load Blazy.
 *     'lazy' => 'blazy',
 *
 *     // Optionally provide an image style:
 *     'image_style' => 'thumbnail',
 *   ];
 *
 *   $build = [
 *     '#theme'    => 'blazy',
 *     '#settings' => $settings,
 *
 *     // Or below for clarity:
 *     '#settings' => ['uri' => 'public://logo.jpg', 'lazy' => 'blazy'],
 *
 *     // Pass custom attributes into the same #item_attributes property as
 *     // Blazy formatters so to respect external modules like RDF, etc. without
 *     // extra property. The regular #attributes property is reserved by Blazy
 *     // container which holds either IMG, icons, or iFrame. Meaning Blazy is
 *     // not just IMG.
 *     '#item_attributes' => [
 *       'alt'   => t('Thumbnail'),
 *       'title' => t('Thumbnail title'),
 *       'width' => 120,
 *     ],
 *
 *     // Finally load the library, or include it into a parent container.
 *     '#attached' => ['library' => ['blazy/load']],
 *   ];
 *
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\Blazy::buildAttributes()
 * @see \Drupal\blazy\Dejavu\BlazyDefault::imageSettings()
 *
 * A multiple image sample.
 *
 * For advanced usages with multiple images, and a few Blazy features such as
 * lightboxes, lazyloaded images, or iframes, including CSS background and
 * aspect ratio, etc.:
 *   o Invoke blazy.manager, and or blazy.formatter.manager, services.
 *   o Use \Drupal\blazy\BlazyManager::getImage() method to work with images and
 *     pass relevant settings which request for particular Blazy features
 *     accordingly.
 *   o Use \Drupal\blazy\BlazyManager::attach() to load relevant libraries.
 * @code
 * function my_module_render_blazy_multiple() {
 *   // Invoke the plugin class, or use a DI service container accordingly.
 *   $manager = \Drupal::service('blazy.manager');
 *
 *   $settings = [
 *     // Explicitly request for Blazy library.
 *     // This allows Slick lazyLoad, or text formatter, to not load Blazy.
 *     'blazy' => TRUE,
 *
 *     // Supported media switcher options dependent on available modules:
 *     // colorbox, media (Image to iframe), photobox.
 *     'media_switch' => 'media',
 *   ];
 *
 *   // Build images.
 *   $build = [
 *     // Load images via $manager->getImage().
 *     // See below ...Formatter::buildElements() for consistent samples.
 *   ];
 *
 *   // Finally attach libraries as requested via $settings.
 *   $build['#attached'] = $manager->attach($settings);
 *
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatterTrait::buildElements()
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyVideoFormatter::buildElements()
 * @see \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackFileFormatterBase::buildElements()
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickFileFormatterBase::buildElements()
 * @see \Drupal\blazy\BlazyManager::getImage()
 * @see \Drupal\blazy\Dejavu\BlazyDefault::imageSettings()
 *
 *
 * Pre-render callback sample to modify/ extend Blazy output.
 * @code
 * function my_module_pre_render(array $image) {
 *   $settings = isset($image['#settings']) ? $image['#settings'] : [];
 *
 *   // Video's HREF points to external site, adds URL to local image.
 *   if (!empty($settings['box_url']) && !empty($settings['embed_url'])) {
 *     $image['#url_attributes']['data-box-url'] = $settings['box_url'];
 *   }
 *
 *   return $image;
 * }
 * @endcode
 * @see hook_blazy_alter()
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters Blazy attachments to add own library, drupalSettings, and JS template.
 *
 * @param array $load
 *   The array of loaded library being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_attach_alter(array &$load, array $settings = []) {
  if (!empty($settings['photoswipe'])) {
    $load['library'][] = 'my_module/load';

    $manager = \Drupal::service('blazy.manager');
    $template = ['#theme' => 'photoswipe_container'];
    $load['drupalSettings']['photoswipe'] = [
      'options' => $manager->configLoad('options', 'photoswipe.settings'),
      'container' => $manager->getRenderer()->renderPlain($template),
    ];
  }
}

/**
 * Alters available lightboxes for Media switch select option at Blazy UI.
 *
 * @param array $lightboxes
 *   The array of lightbox options being modified.
 *
 * @see https://www.drupal.org/project/blazy_photoswipe
 *
 * @ingroup blazy_api
 */
function hook_blazy_lightboxes_alter(array &$lightboxes) {
  $lightboxes[] = 'photoswipe';
}

/**
 * Alters Blazy individual item output to support a custom lightbox.
 *
 * @param array $image
 *   The renderable array of image being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_alter(array &$image, array $settings = []) {
  if (!empty($settings['media_switch']) && $settings['media_switch'] == 'photoswipe') {
    $image['#pre_render'][] = 'my_module_pre_render';
  }
}

/**
 * Alters blazy-related formatter form options to make site-builders happier.
 *
 * A less robust alternative to third party settings to pass the options to
 * blazy-related formatters within the designated compact form.
 * While third party settings offer more fine-grained control over a specific
 * formatter, this offers a swap to various blazy-related formatters at one go.
 * Any class extending \Drupal\blazy\Dejavu\BlazyDefault will be capable
 * to modify both form and UI options at one go.
 *
 * This requires 4 things: option definitions (this alter), schema, extended
 * forms, and front-end implementation of the provided options which can be done
 * via regular hook_preprocess().
 *
 * Accordingly update the schema via core hook_config_schema_info_alter(), or
 * regular module.schema.yml file to have a valid schema.
 * @code
 * function hook_config_schema_info_alter(array &$definitions) {
 *   $settings = ['color' => '', 'arrowpos' => '', 'dotpos' => ''];
 *   Blazy::configSchemaInfoAlter($definitions,
 *     'slick_base', SlickDefault::extendedSettings() + $settings);
 * }
 * @endcode
 *
 * In addition to the schema, implement hook_blazy_complete_form_element_alter()
 * to provide the actual extended forms, see far below. And lastly, implement
 * the options at fron-end via hook_preprocess().
 *
 * @param array $settings
 *   The settings being modified.
 * @param array $context
 *   The array containing class which defines or limit the scope of the options.
 *
 * @ingroup blazy_api
 */
function hook_blazy_base_settings_alter(array &$settings, $context = []) {
  // One override for both various Slick field formatters and Slick views style.
  // SlickDefault extends BlazyDefault, hence capable to modify/ extend options.
  // These options will be available at many Slick formatters at one go.
  if ($context['class'] == 'Drupal\slick\SlickDefault') {
    $settings += ['color' => '', 'arrowpos' => '', 'dotpos' => ''];
  }
}

/**
 * Alters blazy-related formatter form elements.
 *
 * @param array $form
 *   The $form being modified.
 * @param array $definition
 *   The array defining the scope of form elements.
 *
 * @ingroup blazy_api
 */
function hook_blazy_complete_form_element_alter(array &$form, $definition = []) {
  // Limit the scope to Slick formatters, blazy, gridstack, etc. Or swap em all.
  if (isset($definition['namespace']) && $definition['namespace'] == 'slick') {
    // Extend the formatter form elements as needed.
    // SlickExtended::slickFormElementAlter($form, $definition);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
