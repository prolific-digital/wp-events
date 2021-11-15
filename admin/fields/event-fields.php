<?php
if (function_exists('acf_add_local_field_group')) :

  acf_add_local_field_group(array(
    'key' => 'group_61906f0093e35',
    'title' => 'Event Details',
    'fields' => array(
      array(
        'key' => 'field_61906f08deec7',
        'label' => 'Start Date',
        'name' => 'start_date',
        'type' => 'date_picker',
        'instructions' => '',
        'required' => 1,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '33',
          'class' => '',
          'id' => '',
        ),
        'display_format' => 'l, F j, Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
      ),
      array(
        'key' => 'field_619073a382078',
        'label' => 'Start Time',
        'name' => 'start_time',
        'type' => 'time_picker',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '33',
          'class' => '',
          'id' => '',
        ),
        'display_format' => 'g:i a',
        'return_format' => 'g:i a',
      ),
      array(
        'key' => 'field_619073bf82079',
        'label' => 'End Time',
        'name' => 'end_time',
        'type' => 'time_picker',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '33',
          'class' => '',
          'id' => '',
        ),
        'display_format' => 'g:i a',
        'return_format' => 'g:i a',
      ),
      array(
        'key' => 'field_6191ae91bb5b5',
        'label' => 'Repeating Events',
        'name' => '',
        'type' => 'message',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => array(
          array(
            array(
              'field' => 'field_61906f35eabe8',
              'operator' => '!=',
              'value' => '0',
            ),
          ),
        ),
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'message' => 'Updating an existing event series in any way will delete all items from the series and recreate them.',
        'new_lines' => 'wpautop',
        'esc_html' => 0,
      ),
      array(
        'key' => 'field_61906f35eabe8',
        'label' => 'Series Repeat',
        'name' => 'series_repeat',
        'type' => 'select',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '33',
          'class' => '',
          'id' => '',
        ),
        'choices' => array(
          0 => 'Does not repeat',
          'DAILY' => 'Daily',
          'WEEKLY' => 'Weekly',
          'MONTHLY' => 'Monthly',
          'YEARLY' => 'Yearly',
        ),
        'default_value' => false,
        'allow_null' => 0,
        'multiple' => 0,
        'ui' => 0,
        'return_format' => 'value',
        'ajax' => 0,
        'placeholder' => '',
      ),
      array(
        'key' => 'field_619074b0cca49',
        'label' => 'End Series',
        'name' => 'end_series',
        'type' => 'date_picker',
        'instructions' => '',
        'required' => 1,
        'conditional_logic' => array(
          array(
            array(
              'field' => 'field_61906f35eabe8',
              'operator' => '!=',
              'value' => '0',
            ),
          ),
        ),
        'wrapper' => array(
          'width' => '33',
          'class' => '',
          'id' => '',
        ),
        'display_format' => 'l, F j, Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
      ),
      array(
        'key' => 'field_619070e138d3a',
        'label' => 'Repeats on',
        'name' => 'repeats_on',
        'type' => 'checkbox',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => array(
          array(
            array(
              'field' => 'field_61906f35eabe8',
              'operator' => '==',
              'value' => 'MONTHLY',
            ),
          ),
          array(
            array(
              'field' => 'field_61906f35eabe8',
              'operator' => '==',
              'value' => 'YEARLY',
            ),
          ),
        ),
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'choices' => array(
          'MO' => 'Monday',
          'TU' => 'Tuesday',
          'WE' => 'Wednesday',
          'TH' => 'Thursday',
          'FR' => 'Friday',
          'SA' => 'Saturday',
          'SU' => 'Sunday',
        ),
        'allow_custom' => 0,
        'default_value' => array(),
        'layout' => 'horizontal',
        'toggle' => 1,
        'return_format' => 'value',
        'save_custom' => 0,
      ),
      array(
        'key' => 'field_6190758f45e86',
        'label' => 'Notify Registrants',
        'name' => 'notify_registrants',
        'type' => 'true_false',
        'instructions' => 'An automated email will be sent from this site with the event details to the registrants 1 week before the event and 2 days before the event.',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'message' => '',
        'default_value' => 0,
        'ui' => 1,
        'ui_on_text' => '',
        'ui_off_text' => '',
      ),
      array(
        'key' => 'field_619076110f2fa',
        'label' => 'Registration Link',
        'name' => 'registration_link',
        'type' => 'url',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'default_value' => '',
        'placeholder' => '',
      ),
      array(
        'key' => 'field_619076240f2fb',
        'label' => 'Zoom ID',
        'name' => 'zoom_id',
        'type' => 'text',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'default_value' => '',
        'placeholder' => '',
        'prepend' => '',
        'append' => '',
        'maxlength' => '',
      ),
      array(
        'key' => 'field_619076580f2fe',
        'label' => 'Zoom URL',
        'name' => 'zoom_url',
        'type' => 'url',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'default_value' => '',
        'placeholder' => '',
      ),
      array(
        'key' => 'field_619076400f2fc',
        'label' => 'Parent ID',
        'name' => 'parent_id',
        'type' => 'text',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'default_value' => '',
        'placeholder' => '',
        'prepend' => '',
        'append' => '',
        'maxlength' => '',
        'readonly' => 1,
        'disabled' => true,
      ),
      array(
        'key' => 'field_6190764c0f2fd',
        'label' => 'Series ID',
        'name' => 'series_id',
        'type' => 'text',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'default_value' => '',
        'placeholder' => '',
        'prepend' => '',
        'append' => '',
        'maxlength' => '',
        'readonly' => 1,
        'disabled' => true,
      ),
      array(
        'key' => 'field_619076670f2ff',
        'label' => 'Registrants',
        'name' => 'registrants',
        'type' => 'textarea',
        'instructions' => '',
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => array(
          'width' => '',
          'class' => '',
          'id' => '',
        ),
        'default_value' => '',
        'placeholder' => '',
        'maxlength' => '',
        'rows' => '',
        'new_lines' => '',
        'readonly' => 1,
        'disabled' => true,
      ),
    ),
    'location' => array(
      array(
        array(
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'events',
        ),
      ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => '',
    'active' => true,
    'description' => '',
    'show_in_rest' => 0,
  ));

endif;
