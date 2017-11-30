<?php

namespace Drupal\isi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class IsiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'isi_config';
  }

  /**
   * Declare what settings this config form will be changing.
   */
  protected function getEditableConfigNames() {
    return ['isi.settings'];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('isi.settings');
    $fs = "isi";
    $node_options = self::isi_settings_get_node_options();
    self::isi_settings_get_field_options();

    $form[$fs] = [
      '#type' => 'fieldset',
      '#title' => "ISI settings",
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $key = "isi_node_nid";
    $data = '1';

    $form['help_add'] = [
      '#type' => 'markup',
      '#markup' => "<h1>Configure mobile settings</h1><p>Tell the module which node hosts the ISI and which field should be used for mobile content.</p>",
    ];

    $form[$fs]['isi_node_nid'] = [
      '#title' => "Node containing ISI content",
      '#type' => 'select',
      '#default_value' => $config->get('isi_node_nid'),
      '#options' => $node_options,
    ];

    $form[$fs]['isi_field_mobile'] = [
      '#title' => "Field containing ISI mobile content",
      '#type' => 'select',
      '#default_value' => $config->get('isi_field_mobile'),
    // array(0 => 'field_accordion_groups (In content types: faq_page)'),.
      '#options' => $this->isi_settings_get_field_options(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get a list of nodes that we can select from for defining the ISI node.
   *
   * @return assoc array
   *   key = node nid
   *   value = node title
   */
  public static function isi_settings_get_node_options() {
    static $options = NULL;
    if (is_array($options)) {
      return $options;
    }
    // Get a list of all node nids and titles, for the form <select> options.
    $query = \Drupal::entityQuery('node');
    $result = $query->execute();
    $nids = [];
    foreach ($result as $key => $value) {
      $nids[] = $value;
    }
    $nodes = node_load_multiple($nids);
    $options = [0 => '-- select --'];
    foreach ($nodes as $nid => $node) {
      $options[$nid] = $node->get('title')->value . ' (Content type: ' . $node->bundle() . ')';
    }
    asort($options);
    return $options;
  }

  /**
   * Get a list of entity fields we can select from for defining which field on
   * a node contains the mobile ISI reference copy.
   *
   * @return assoc array
   *   key = field name
   *   value = field name and node types containing it
   */
  public function isi_settings_get_field_options() {
    static $options = NULL;
    if (is_array($options)) {
      return $options;
    }
    $options = [];
    $fields = \Drupal::entityManager()->getFieldMap();
    foreach ($fields as $field_name => $field_data) {
      foreach ($field_data as $fname => $fvalue) {
        // $entity_type = FieldDefinitionInterface()->getTargetEntityTypeId($fname);
        if (strstr($fname, "field_") || $fname == "body") {
          $options[$fname] = $fname;
        }
      }
    }
    asort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Lets load and set our configuration.
    $config = $this->config('isi.settings');
    $config->set('isi_node_nid', $form_state->getValue('isi_node_nid'));
    $config->set('isi_field_mobile', $form_state->getValue('isi_field_mobile'));
    // Save the config.
    $config->save();
  }

}
