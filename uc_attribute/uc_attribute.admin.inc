<?php

/**
 * @file
 * Attribute administration menu items.
 */

/**
 * Formats an attribute and its options.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_uc_attribute_options_form($variables) {
  $form = $variables['form'];

  $header = array(t('Name'), t('Default cost'), t('Default price'), t('Default weight'), array('data' => t('List position'), 'sort' => 'asc'), array('data' => t('Operations'), 'colspan' => 2));

  $rows = array();
  foreach (element_children($form['options']) as $oid) {
    $rows[] = array(
      'data' => array(
        drupal_render($form['options'][$oid]['name']),
        drupal_render($form['options'][$oid]['cost']),
        drupal_render($form['options'][$oid]['price']),
        drupal_render($form['options'][$oid]['weight']),
        drupal_render($form['options'][$oid]['ordering']),
        drupal_render($form['options'][$oid]['edit']),
        drupal_render($form['options'][$oid]['delete']),
      ),
      'class' => array('draggable'),
    );
  }

  drupal_add_tabledrag('uc-attribute-option-table', 'order', 'sibling', 'uc-attribute-option-table-ordering');

  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'uc-attribute-option-table'),
    'empty' => t('No options for this attribute have been added yet.'),
  ));

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Displays the formatted attribute form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_object_attributes_form()
 * @ingroup themeable
 */
function theme_uc_object_attributes_form($variables) {
  $form = $variables['form'];

  $output = '';

  if ($form['view']['#value'] == 'overview') {
    $header = array(t('Remove'), t('Name'), t('Label'), t('Default'), t('Required'), t('List position'), t('Display'));

    $rows = array();
    foreach (element_children($form['attributes']) as $aid) {
      $rows[] = array(
        'data' => array(
          drupal_render($form['attributes'][$aid]['remove']),
          drupal_render($form['attributes'][$aid]['name']),
          drupal_render($form['attributes'][$aid]['label']),
          drupal_render($form['attributes'][$aid]['option']),
          drupal_render($form['attributes'][$aid]['required']),
          drupal_render($form['attributes'][$aid]['ordering']),
          drupal_render($form['attributes'][$aid]['display']),
        ),
        'class' => array('draggable'),
      );
    }

    drupal_add_tabledrag('uc-attribute-table', 'order', 'sibling', 'uc-attribute-table-ordering');

    $output = theme('table', array(
      'header' => $header,
      'rows' => $rows,
      'attributes' => array('id' => 'uc-attribute-table'),
      'empty' => t('No attributes available.'),
    ));
  }
  else {
    $output = '<div class="uc-attributes-add-link">';
    $output .= t('You may add more attributes <a href="!url">here</a>.', array('!url' => url('admin/store/products/attributes/add')));
    $output .= '</div>';
  }

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Form to assign and modify attribute options on products or classes.
 *
 * @see uc_object_options_form_validate()
 * @see uc_object_options_form_submit()
 * @see theme_uc_object_options_form()
 * @ingroup forms
 */
function uc_object_options_form($form, &$form_state, $object, $type) {
  if ($type == 'product') {
    $product = $object;
    $id = $product->id();
    drupal_set_title($product->label());
    $attributes = uc_product_get_attributes($id);
    $table = 'uc_product_options';
    $id_type = 'nid';
  }
  elseif ($type == 'class') {
    $class = $object;
    $id = $class->pcid;
    drupal_set_title($class->name);
    $attributes = uc_class_get_attributes($id);
    $table = 'uc_class_attribute_options';
    $id_type = 'pcid';
  }

  foreach ($attributes as $aid => $attribute) {
    $form['attributes'][$aid]['name'] = array(
      '#markup' => check_plain($attribute->name),
    );
    $form['attributes'][$aid]['aid'] = array(
      '#type' => 'hidden',
      '#value' => $attribute->aid,
    );
    $form['attributes'][$aid]['ordering'] = array(
      '#type' => 'value',
      '#value' => $attribute->ordering,
    );

    $form['attributes'][$aid]['options'] = array('#weight' => 2);

    $base_attr = uc_attribute_load($attribute->aid);

    if ($base_attr->options) {
      $options = array();

      $query = db_select('uc_attribute_options', 'ao')
        ->fields('ao', array(
          'aid',
          'oid',
          'name',
        ));
      $query->leftJoin($table, 'po', "ao.oid = po.oid AND po.$id_type = :id", array(':id' => $id));

      $query->addField('ao', 'cost', 'default_cost');
      $query->addField('ao', 'price', 'default_price');
      $query->addField('ao', 'weight', 'default_weight');
      $query->addField('ao', 'ordering', 'default_ordering');

      $query->fields('po', array(
          'cost',
          'price',
          'weight',
          'ordering',
        ))
        ->addExpression('CASE WHEN po.ordering IS NULL THEN 1 ELSE 0 END', 'null_order');

      $query->condition('aid', $attribute->aid)
        ->orderBy('null_order')
        ->orderBy('po.ordering')
        ->orderBy('default_ordering')
        ->orderBy('ao.name');

      $result = $query->execute();
      foreach ($result as $option) {
        $oid = $option->oid;
        $options[$oid] = '';

        $form['attributes'][$aid]['options'][$oid]['select'] = array(
          '#type' => 'checkbox',
          '#default_value' => isset($attribute->options[$oid]) ? TRUE : FALSE,
          '#title' => check_plain($option->name),
        );
        $form['attributes'][$aid]['options'][$oid]['cost'] = array(
          '#type' => 'uc_price',
          '#title' => t('Cost'),
          '#title_display' => 'invisible',
          '#default_value' => is_null($option->cost) ? $option->default_cost : $option->cost,
          '#size' => 6,
          '#allow_negative' => TRUE,
        );
        $form['attributes'][$aid]['options'][$oid]['price'] = array(
          '#type' => 'uc_price',
          '#title' => t('Price'),
          '#title_display' => 'invisible',
          '#default_value' => is_null($option->price) ? $option->default_price : $option->price,
          '#size' => 6,
          '#allow_negative' => TRUE,
        );
        $form['attributes'][$aid]['options'][$oid]['weight'] = array(
          '#type' => 'textfield',
          '#title' => t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => is_null($option->weight) ? $option->default_weight : $option->weight,
          '#size' => 5,
        );
        $form['attributes'][$aid]['options'][$oid]['ordering'] = array(
          '#type' => 'weight',
          '#title' => t('List position'),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => is_null($option->ordering) ? $option->default_ordering : $option->ordering,
          '#attributes' => array('class' => array('uc-attribute-option-table-ordering')),
        );
      }

      $form['attributes'][$aid]['default'] = array(
        '#type' => 'radios',
        '#title' => t('Default'),
        '#title_display' => 'invisible',
        '#options' => $options,
        '#default_value' => $attribute->default_option,
      );
    }
  }

  if (!empty($form['attributes'])) {
    $form['attributes']['#tree'] = TRUE;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 10,
    );
  }

  $form['id'] = array(
    '#type' => 'value',
    '#value' => $id,
  );
  $form['type'] = array(
    '#type' => 'value',
    '#value' => $type,
  );

  return $form;
}

/**
 * Displays the option form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see uc_object_options_form()
 * @ingroup themeable
 */
function theme_uc_object_options_form($variables) {
  $form = $variables['form'];
  $output = '';

  drupal_add_js('misc/tableselect.js');
  $header = array(array('data' => '&nbsp;&nbsp;' . t('Options'), 'class' => array('select-all')), t('Default'), t('Cost'), t('Price'), t('Weight'), t('List position'));
  $tables = 0;

  if (isset($form['attributes'])) {
    foreach (element_children($form['attributes']) as $key) {
      $rows = array();
      foreach (element_children($form['attributes'][$key]['options']) as $oid) {
        $rows[] = array(
          'data' => array(
            drupal_render($form['attributes'][$key]['options'][$oid]['select']),
            drupal_render($form['attributes'][$key]['default'][$oid]),
            drupal_render($form['attributes'][$key]['options'][$oid]['cost']),
            drupal_render($form['attributes'][$key]['options'][$oid]['price']),
            drupal_render($form['attributes'][$key]['options'][$oid]['weight']),
            drupal_render($form['attributes'][$key]['options'][$oid]['ordering']),
          ),
          'class' => array('draggable'),
        );
      }

      $table_id = 'uc-attribute-option-table-' . $tables++;
      drupal_add_tabledrag($table_id, 'order', 'sibling', 'uc-attribute-option-table-ordering');

      $output .= theme('table', array(
        'header' => $header,
        'rows' => $rows,
        'attributes' => array(
          'class' => array('product_attributes'),
          'id' => $table_id,
        ),
        'caption' => '<h2>' . drupal_render($form['attributes'][$key]['name']) . '</h2>',
        'empty' => t('This attribute does not have any options.'),
      ));
    }
  }

  if (!$tables) {
    if ($form['type']['#value'] == 'product') {
      drupal_set_message(t('This product does not have any attributes.'), 'warning');
    }
    else {
      drupal_set_message(t('This product class does not have any attributes.'), 'warning');
    }
  }

  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Returns a themed set of attribute options for use in order displays.
 *
 * @param $variables
 *   An associative array containing:
 *   - attributes: An associative array containing the set of attributes,
 *     with each element keyed by attribute ID:
 *     - <aid>: An associative array containing:
 *       - #attribute_name: Attribute name.
 *       - #options: Array of option names.
 *
 * @return
 *   Themed set of attribute options.
 *
 * @ingroup themeable
 */
function theme_uc_product_attributes($variables) {
  $attributes = $variables['attributes'];

  $option_rows = array();

  foreach (element_children($attributes) as $key) {
    $optionstr = '';

    foreach ((array)$attributes[$key]['#options'] as $option) {
      // We only need to allow translation from the second option onward
      if (empty($optionstr)) {
        $optionstr .= $option;
      }
      else {
        $optionstr .= t(', !option', array('!option' => $option));
      }
    }

    if ($optionstr != '') {
      $option_rows[$key] = t('@attribute: @option', array('@attribute' => $attributes[$key]['#attribute_name'], '@option' => $optionstr));
    }
  }

  if (!empty($option_rows)) {
    return theme('item_list', array('items' => array_values($option_rows), 'attributes' => array('class' => array('product-description'))));
  }

  return '';
}

/**
 * Makes sure that all selected default options are enabled.
 *
 * @see uc_object_options_form()
 * @see uc_object_options_form_submit()
 */
function uc_object_options_form_validate($form, &$form_state) {
  $error = FALSE;

  if (isset($form_state['values']['attributes'])) {
    foreach ($form_state['values']['attributes'] as $aid => $attribute) {
      $selected_opts = array();
      if (isset($attribute['options'])) {
        foreach ($attribute['options'] as $oid => $option) {
          if ($option['select'] == 1) {
            $selected_opts[] = $oid;
          }
        }
      }
      if (!empty($selected_opts) && !isset($form['attributes'][$aid]['default']['#disabled']) && !in_array($attribute['default'], $selected_opts)) {
        form_set_error($attribute['default']);
        $error = TRUE;
      }
    }
  }

  if ($error) {
    drupal_set_message(t('All attributes with enabled options must specify an enabled option as default.'), 'error');
  }
}

/**
 * Form submission handler for uc_object_options_form().
 *
 * @see uc_object_options_form()
 * @see uc_object_options_form_validate()
 */
function uc_object_options_form_submit($form, &$form_state) {
  if ($form_state['values']['type'] == 'product') {
    $attr_table = 'uc_product_attributes';
    $opt_table = 'uc_product_options';
    $id = 'nid';
  }
  elseif ($form_state['values']['type'] == 'class') {
    $attr_table = 'uc_class_attributes';
    $opt_table = 'uc_class_attribute_options';
    $id = 'pcid';
  }

  foreach ($form_state['values']['attributes'] as $attribute) {
    if (isset($attribute['default'])) {
      db_update($attr_table)
        ->fields(array(
          'default_option' => $attribute['default'],
        ))
        ->condition($id, $form_state['values']['id'])
        ->condition('aid', $attribute['aid'])
        ->execute();
    }

    if (isset($attribute['options'])) {
      db_delete($opt_table)
        ->condition($id, $form_state['values']['id'])
        ->condition('oid', array_keys($attribute['options']), 'IN')
        ->execute();

      foreach ($attribute['options'] as $oid => $option) {
        if ($option['select']) {
          $option[$id] = $form_state['values']['id'];
          $option['oid'] = $oid;

          drupal_write_record($opt_table, $option);
        }
        elseif ($form_state['values']['type'] == 'product') {
          $aid = $attribute['aid'];
          $match = 'i:' . $aid . ';s:' . strlen($oid) . ':"' . $oid . '";';
          db_delete('uc_product_adjustments')
            ->condition('nid', $form_state['values']['id'])
            ->condition('combination', '%' . db_like($match) . '%', 'LIKE')
            ->execute();
        }
      }
    }
  }

  drupal_set_message(t('The @type options have been saved.', array('@type' => $form_state['values']['type'] == 'product' ? t('product') : t('product class'))));

  if ($form_state['values']['type'] == 'product') {
    // Clear the page and block caches.
    cache_clear_all();
  }
}

/**
 * Form builder: associate option combinations with a product variant's SKU.
 *
 * @see uc_product_adjustments_form_submit()
 * @ingroup forms
 */
function uc_product_adjustments_form($form, &$form_state, $node) {
  drupal_set_title($node->label());
  $nid = $node->id();

  // Populate table and such.
  $model = $node->model;
  $query = db_select('uc_product_attributes', 'pa');
  $query->leftJoin('uc_attributes', 'a', 'pa.aid = a.aid');
  $query->leftJoin('uc_attribute_options', 'ao', 'a.aid = ao.aid');
  $query->leftJoin('uc_product_options', 'po', 'ao.oid = po.oid AND po.nid = :po_nid', array(':po_nid' => $nid));
  $result = $query->fields('pa', array('nid', 'aid', 'ordering', 'display'))
    ->fields('a', array('name', 'ordering', 'aid'))
    ->fields('ao', array('aid'))
    ->condition('pa.nid', $nid)
    ->having('COUNT(po.oid) > 0')
    ->groupBy('ao.aid')
    ->groupBy('pa.aid')
    ->groupBy('pa.display')
    ->groupBy('a.name')
    ->groupBy('pa.ordering')
    ->groupBy('a.ordering')
    ->groupBy('pa.nid')
    ->addTag('uc_product_adjustments_form')
    ->execute();

  $i = 1;
  $attribute_names = '';
  $full_attributes = array();
  $values = array();

  $query = db_select('uc_product_options', "po$i")
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->limit(20);

  $attribute_ids = array();
  foreach ($result as $prod_attr) {
    if ($i > 1) {
      $query->join('uc_product_options', "po$i");
    }
    $query->leftJoin('uc_attribute_options', "ao$i", "po$i.oid = ao$i.oid AND po$i.nid = :nid", array(':nid' => $nid));
    $query->addField("ao$i", 'aid', "aid$i");
    $query->addField("ao$i", 'name', "name$i");
    $query->addField("ao$i", 'oid', "oid$i");
    $query->addField("po$i", 'ordering', "ordering$i");

    $query->condition("ao$i.aid", $prod_attr->aid)
      ->orderBy("po$i.ordering")
      ->orderBy("ao$i.name");

    ++$i;
    $attribute_names .= '<th>' . check_plain($prod_attr->name) . '</th>';
    $attribute_ids[] = $prod_attr->aid;
  }
  $num_prod_attr = count($attribute_ids);

  if ($num_prod_attr) {
    // Get previous values
    $old_vals = db_query("SELECT * FROM {uc_product_adjustments} WHERE nid = :nid", array(':nid' => $nid))->fetchAll();

    $result = $query->execute();

    $form['original'] = array(
      '#markup' => '<p><b>' . t('Default product SKU: @sku', array('@sku' => $model)) . '</b></p>',
    );
    $form['default'] = array(
      '#type' => 'value',
      '#value' => $model,
    );
    $form['table'] = array(
      '#prefix' => '<table class="combinations">',
      '#suffix' => '</table>',
    );
    $form['table']['head'] = array(
      '#markup' => '<thead><tr>' . $attribute_names . '<th>' . t('Alternate SKU') . '</th></tr></thead>',
      '#weight' => 0,
    );
    $form['table']['body'] = array(
      '#prefix' => '<tbody>',
      '#suffix' => '</tbody>',
      '#weight' => 1,
      '#tree' => TRUE,
    );

    $i = 0;
    while ($combo = $result->fetchObject()) {
      $cells = '';
      $row_title = '';
      $comb_array = array();
      for ($j = 1; $j <= $num_prod_attr; ++$j) {
        $cells .= '<td>' . check_plain($combo->{'name' . $j}) . '</td>';
        $row_title .= check_plain($combo->{'name' . $j}) . ', ';
        $comb_array[$combo->{'aid' . $j}] = $combo->{'oid' . $j};
      }
      ksort($comb_array);
      $row_title = substr($row_title, 0, strlen($row_title) - 2);
      $default_model = $model;
      foreach ($old_vals as $ov) {
        if ($ov->combination == serialize($comb_array)) {
          $default_model = $ov->model;
          break;
        }
      }

      $form['table']['body'][$i] = array(
        '#prefix' => '<tr title="' . $row_title . '">',
        '#suffix' => '</tr>',
      );
      $form['table']['body'][$i]['combo'] = array(
        '#markup' => $cells,
      );
      $form['table']['body'][$i]['combo_array'] = array(
        '#type' => 'value',
        '#value' => serialize($comb_array),
      );
      $form['table']['body'][$i]['model'] = array(
        '#type' => 'textfield',
        '#default_value' => $default_model,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );
      ++$i;
    }

    $form['nid'] = array(
      '#type' => 'hidden',
      '#value' => $nid,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
  }
  else {
    $form['error'] = array(
      '#markup' => '<div><br />' . t('This product does not have any attributes that can be used for SKU adjustments.') . '</div>',
    );
  }

  $form['pager'] = array(
    '#theme' => 'pager',
  );

  return $form;
}

/**
 * Form builder for uc_product_adjustments_form().
 *
 * @see uc_product_adjustments_form()
 */
function uc_product_adjustments_form_submit($form, &$form_state) {
  foreach ($form_state['values']['body'] as $value) {
    if (!empty($value['model']) && $value['model'] != $form_state['values']['default']) {
      db_merge('uc_product_adjustments')
        ->key(array(
          'nid' => $form_state['values']['nid'],
          'combination' => $value['combo_array'],
        ))
        ->fields(array(
          'model' => $value['model'],
        ))
        ->execute();
    }
    else {
      db_delete('uc_product_adjustments')
        ->condition('nid', $form_state['values']['nid'])
        ->condition('combination', $value['combo_array'])
        ->execute();
    }
  }
  drupal_set_message(t('Product adjustments have been saved.'));
}
