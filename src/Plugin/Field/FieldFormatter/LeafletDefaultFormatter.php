<?php

namespace Drupal\leaflet\Plugin\Field\FieldFormatter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Leaflet\LeafletService;
use Drupal\leaflet\LeafletSettingsElementsTrait;

/**
 * Plugin implementation of the 'leaflet_default' formatter.
 *
 * @FieldFormatter(
 *   id = "leaflet_formatter_default",
 *   label = @Translation("Leaflet Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class LeafletDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use LeafletSettingsElementsTrait;

  /**
   * Leaflet service.
   *
   * @var \Drupal\Leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * LeafletDefaultFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LeafletService $leaflet_service
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->leafletService = $leaflet_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('leaflet.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'multiple_map' => 0,
      'leaflet_map' => 'OSM Mapnik',
      'height' => 400,
      'zoom' => 10,
      'minPossibleZoom' => 0,
      'maxPossibleZoom' => 18,
      'minZoom' => 0,
      'maxZoom' => 18,
      'popup' => FALSE,
      'icon' => [
        'iconUrl' => '',
        'shadowUrl' => '',
        'iconSize' => ['x' => NULL, 'y' => NULL],
        'iconAnchor' => ['x' => NULL, 'y' => NULL],
        'shadowAnchor' => ['x' => NULL, 'y' => NULL],
        'popupAnchor' => ['x' => NULL, 'y' => NULL],
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Get the Cardinality set for the Formatter Field.
    $field_cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();

    $elements = parent::settingsForm($form, $form_state);

    $leaflet_map_options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $leaflet_map_options[$key] = $map['label'];
    }

    if ($field_cardinality !== 1) {
      $elements['multiple_map'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Multiple Maps'),
        '#description' => $this->t('Check this option if you want to render a single Map for every single Geo Point.'),
        '#default_value' => $this->getSetting('multiple_map'),
        '#return_value' => 1,
      ];
    }
    else {
      $elements['multiple_map'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
    }

    $elements['leaflet_map'] = [
      '#title' => $this->t('Leaflet Map'),
      '#type' => 'select',
      '#options' => $leaflet_map_options,
      '#default_value' => $this->getSetting('leaflet_map'),
      '#required' => TRUE,
    ];

    $zoom_options = [];

    for ($i = $this->getSetting('minPossibleZoom'); $i <= $this->getSetting('maxPossibleZoom'); $i++) {
      $zoom_options[$i] = $i;
    }
    $elements['zoom'] = [
      '#title' => $this->t('Zoom'),
      '#type' => 'select',
      '#options' => $zoom_options,
      '#default_value' => $this->getSetting('zoom'),
      '#required' => TRUE,
    ];
    $elements['minZoom'] = [
      '#title' => $this->t('Min. Zoom'),
      '#type' => 'select',
      '#options' => $zoom_options,
      '#default_value' => $this->getSetting('minZoom'),
      '#required' => TRUE,
    ];
    $elements['maxZoom'] = [
      '#title' => $this->t('Max. Zoom'),
      '#type' => 'select',
      '#options' => $zoom_options,
      '#default_value' => $this->getSetting('maxZoom'),
      '#required' => TRUE,
    ];
    $elements['height'] = [
      '#title' => $this->t('Map Height'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('height'),
      '#field_suffix' => $this->t('px'),
    ];
    $elements['popup'] = [
      '#title' => $this->t('Popup'),
      '#description' => $this->t('Show a popup for single location fields.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('popup'),
    ];

    // Generate Icon form element.
    $icon = $this->getSetting('icon');
    $elements['icon'] = $this->generateIconFormElement($icon);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Leaflet Map: @map', ['@map' => $this->getSetting('leaflet_map')]);
    $summary[] = $this->t('Map height: @height px', ['@height' => $this->getSetting('height')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This function is called from parent::view().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    // Take the entity translation, if existing.
    /* @var \Drupal\Core\TypedData\TranslatableInterface $entity */
    if ($entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }

    $settings = $this->getSettings();
    $icon_url = $settings['icon']['iconUrl'];

    $map = leaflet_map_get_info($settings['leaflet_map']);
    $map['settings']['zoom'] = isset($settings['zoom']) ? $settings['zoom'] : NULL;
    $map['settings']['minZoom'] = isset($settings['minZoom']) ? $settings['minZoom'] : NULL;
    $map['settings']['maxZoom'] = isset($settings['zoom']) ? $settings['maxZoom'] : NULL;

    // Performs some preprocess on the leaflet map settings.
    $this->leafletService->preProcessMapSettings($settings);

    $features = [];
    foreach ($items as $delta => $item) {

      $points = $this->leafletService->leafletProcessGeofield($item->value);
      $feature = $points[0];

      // Eventually set the popup content to the entity title.
      if ($settings['popup']) {
        $feature['popup'] = $entity->label();
      }

      // Eventually set the custom icon.
      if (!empty($icon_url)) {
        $feature['icon'] = $settings['icon'];
      }

      $features[] = $feature;
    }

    $results = [];
    if (!empty($settings['multiple_map'])) {
      foreach ($features as $feature) {
        $results[] = $this->leafletService->leafletRenderMap($map, [$feature], $settings['height'] . 'px');
      }
    }
    else {
      $results[] = $this->leafletService->leafletRenderMap($map, $features, $settings['height'] . 'px');
    }

    return $results;
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
