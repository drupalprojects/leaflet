<?php

namespace Drupal\leaflet;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GeofieldMapFieldTrait.
 *
 * Provide common functions for Leaflet Settings Elements.
 *
 * @package Drupal\leaflet
 */
trait LeafletSettingsElementsTrait {

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $gMapTypesOptions = [
    'roadmap' => 'Roadmap',
    'satellite' => 'Satellite',
    'hybrid' => 'Hybrid',
    'terrain' => 'Terrain',
  ];

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $infowindowFieldTypesOptions = [
    'string_long',
    'string',
    'text',
    'text_long',
    "text_with_summary",
  ];

  protected $customMapStylePlaceholder = '[{"elementType":"geometry","stylers":[{"color":"#1d2c4d"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#8ec3b9"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#1a3646"}]},{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#0e1626"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#4e6d70"}]}]';

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface $this->link
   */

  /**
   * Get the GMap Api Key from the geofield_map settings/configuration.
   *
   * @return string
   *   The GMap Api Key
   */
  private function getGmapApiKey() {
    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config */
    $config = $this->config;
    $geofield_map_settings = $config->getEditable('geofield_map.settings');
    $gmap_api_key = $geofield_map_settings->get('gmap_api_key');

    // In the first release of Geofield_Map the google_api_key was stored in
    // the specific Field Widget settings.
    // So we try and copy into the geofield_map.settings config, in the case.
    if (method_exists(get_class($this), 'getSetting') && !empty($this->getSetting('map_google_api_key')) && empty($gmap_api_key)) {
      $gmap_api_key = $this->getSetting('map_google_api_key');
      $geofield_map_settings->set('gmap_api_key', $gmap_api_key)->save();
    }
    return $gmap_api_key;
  }

  /**
   * Get the Default Settings.
   *
   * @return array
   *   The default settings.
   */
  public static function getDefaultSettings() {
    return [
      'gmap_api_key' => '',
      'map_dimensions' => [
        'width' => '100%',
        'height' => '450px',
      ],
      'map_empty' => [
        'empty_behaviour' => '0',
        'empty_message' => t('No Geofield Value entered for this field'),
      ],
      'map_center' => [
        'lat' => '42',
        'lon' => '12.5',
        'center_force' => 0,
      ],
      'map_zoom_and_pan' => [
        'zoom' => [
          'initial' => 6,
          'force' => 0,
          'min' => 1,
          'max' => 22,
        ],
        'scrollwheel' => 1,
        'draggable' => 1,
        'map_reset' => 0,
      ],
      'map_controls' => [
        'disable_default_ui' => 0,
        'zoom_control' => 1,
        'map_type_id' => 'roadmap',
        'map_type_control' => 1,
        'map_type_control_options_type_ids' => [
          'roadmap' => 'roadmap',
          'satellite' => 'satellite',
          'hybrid' => 'hybrid',
          'terrain' => 'terrain',
        ],
        'scale_control' => 1,
        'street_view_control' => 1,
        'fullscreen_control' => 1,
      ],
      'map_marker_and_infowindow' => [
        'icon_image_mode' => 'icon_file',
        'icon_image_path' => '',
        'icon_file_wrapper' => [
          'icon_file' => '',
        ],
        'infowindow_field' => 'title',
        'multivalue_split' => 0,
        'force_open' => 0,
      ],
      'map_oms' => [
        'map_oms_control' => 1,
        'map_oms_options' => '{"markersWontMove": "true", "markersWontHide": "true", "basicFormatEvents": "true", "nearbyDistance": 3}',
      ],
      'map_additional_options' => '',
      'custom_style_map' => [
        'custom_style_control' => 0,
        'custom_style_name' => '',
        'custom_style_options' => '',
        'custom_style_default' => 0,
      ],
      'map_markercluster' => [
        'markercluster_control' => 0,
        'markercluster_additional_options' => '',
      ],
    ];
  }

  /**
   * Generate the Leaflet Icon Form Element.
   *
   * @param array $icon
   *   The icon array definition.
   *
   * @return array
   *   The Leaflet Icon Form Element.
   */
  public function generateIconFormElement(array $icon) {

    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Icon'),
      'description' => [
        '#markup' => $this->t('For details on the following setup refer to @leaflet_icon_documentation_link', [
          '@leaflet_icon_documentation_link' => $this->leafletService->leafletIconDocumentationLink(),
        ]),
      ],
    ];

    $element['iconUrl'] = [
      '#title' => $this->t('Icon URL'),
      '#description' => $this->t('Can be an absolute or relative URL.'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => isset($icon['iconUrl']) ? $icon['iconUrl'] : NULL,
      '#element_validate' => [[$this, 'validateUrl']],
    ];

    $element['shadowUrl'] = [
      '#title' => $this->t('Icon Shadow URL'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => isset($icon['shadowUrl']) ? $icon['shadowUrl'] : NULL,
      '#element_validate' => [[$this, 'validateUrl']],
    ];

    $element['iconSize'] = [
      '#title' => $this->t('Icon Size'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('Size of the icon image in pixels.'),
    ];
    $element['iconSize']['x'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => isset($icon['iconSize']) ? $icon['iconSize']['x'] : NULL,
    ];
    $element['iconSize']['y'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => isset($icon['iconSize']) ? $icon['iconSize']['y'] : NULL,
    ];
    $element['iconAnchor'] = [
      '#title' => $this->t('Icon Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The coordinates of the "tip" of the icon (relative to its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location.'),
    ];
    $element['iconAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon['iconAnchor']) ? $icon['iconAnchor']['x'] : NULL,
    ];
    $element['iconAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon['iconAnchor']) ? $icon['iconAnchor']['y'] : NULL,
    ];
    $element['shadowAnchor'] = [
      '#title' => $this->t('Shadow Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the shadow is shown.'),
    ];
    $element['shadowAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon['shadowAnchor']) ? $icon['shadowAnchor']['x'] : NULL,
    ];
    $element['shadowAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon['shadowAnchor']) ? $icon['shadowAnchor']['y'] : NULL,
    ];
    $element['popupAnchor'] = [
      '#title' => $this->t('Popup Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the marker popup opens, relative to the anchor point.'),
    ];
    $element['popupAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon['popupAnchor']) ? $icon['popupAnchor']['x'] : NULL,
    ];
    $element['popupAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon['popupAnchor']) ? $icon['popupAnchor']['y'] : NULL,
    ];

    return $element;

  }

  /**
   * Validate Url method.
   *
   * @param array $element
   *   The element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function validateUrl(array $element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !UrlHelper::isValid($element['#value'])) {
      $form_state->setError($element, $this->t("Icon Url is not valid."));
    }
  }

}
