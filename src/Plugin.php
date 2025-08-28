<?php

class TMExpirationDatePlugin
{

    private $units;

    public function init(): void
    {
        add_action('dl_ticket_event_fields_after', [$this, 'eventFieldsAfter']);
        add_action('dl_ticket_save_event_fields', [$this, 'saveEventFields']);
        add_filter('manage_dl-ticket_posts_columns', [$this, 'addCustomColumns'], 50, 1);
        add_action('manage_dl-ticket_posts_custom_column', [$this, 'renderCustomColumns'], 60, 2);
        add_action('admin_head', [$this, 'customColumnStyles']);

        add_filter('dl_ticket_manager_create_ticket', [$this, 'filterCreateTicket']);
        add_filter('dl_ticket_manager_get_ticket_data', [$this, 'filterGetTicket']);
        add_filter('dltm_validate_ticket_data', [$this, 'validateTicket']);
    }

    /**
     * Mostramos los campos para poder configurar
     * la fecha de validez del ticket
     * @return void
     * @author Daniel Lucia
     */
    public function eventFieldsAfter()
    {

        $this->units = apply_filters('dl_ticket_manager_expiration_date_units', [
            'hours' => __('Hours', 'dl-ticket-manager-expiration-date'),
            'days' => __('Days', 'dl-ticket-manager-expiration-date'),
            'weeks' => __('Weeks', 'dl-ticket-manager-expiration-date'),
            'months' => __('Months', 'dl-ticket-manager-expiration-date'),
            'years' => __('Years', 'dl-ticket-manager-expiration-date')
        ]);

        echo '<div class="options_group">';

        woocommerce_wp_text_input([
            'id'  => '_validity_duration',
            'label'  => __('Event validity duration', 'dl-ticket-manager-expiration-date'),
            'placeholder' => __('Duration', 'dl-ticket-manager-expiration-date'),
            'desc_tip' => true,
            'description' => __('Validity duration. Leave zero for indeterminate.', 'dl-ticket-manager-expiration-date'),
            'type'  => 'number',
            'custom_attributes' => [
                'min' => '0',
                'step' => '1'
            ],
        ]);

        woocommerce_wp_select([
            'id' => '_validity_duration_unit',
            'label' => __('Event validity duration unit', 'dl-ticket-manager-expiration-date'),
            'options' => $this->units,
            'desc_tip' => true,
            'description' => __('Select the unit for the validity duration.', 'dl-ticket-manager-expiration-date'),
        ]);

        echo '</div>';
    }

    /**
     * Guardamos los campos de fecha de validez del ticket
     * @param mixed $post_id
     * @return void
     * @author Daniel Lucia
     */
    public function saveEventFields($post_id)
    {
        $duration = isset($_POST['_validity_duration']) ? intval($_POST['_validity_duration']) : 0;
        update_post_meta($post_id, '_validity_duration', $duration);

        $unit = isset($_POST['_validity_duration_unit']) ? sanitize_text_field($_POST['_validity_duration_unit']) : '';
        update_post_meta($post_id, '_validity_duration_unit', $unit);
    }

    /**
     * Guardamos la fecha en el ticket
     * @param array $data
     * @return array
     * @author Daniel Lucia
     */
    public function filterCreateTicket(array $data)
    {
        $post_id = $data['product_id'];
        $duration = get_post_meta($post_id, '_validity_duration', true);
        $unit = get_post_meta($post_id, '_validity_duration_unit', true);

        if ($duration > 0 && $unit) {
            $data['validity_duration'] = $duration . ' ' . $unit;
        }

        return $data;
    }

    /**
     * Añadimos los datos de validez al ticket
     * @param array $data
     * @return array
     * @author Daniel Lucia
     */
    public function filterGetTicket(array $data)
    {
        $product_id = get_post_meta($data['id'], 'product_id', true);
        $data['validity_duration'] = get_post_meta($product_id, 'validity_duration', true);

        return $data;
    }

    /**
     * Añadimos nueva columna en los tickets
     * @param mixed $columns
     * @author Daniel Lucia
     */
    public function addCustomColumns($columns)
    {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'status') {
                $new_columns['validity_duration'] = __('Validity Duration', 'dl-ticket-manager-expiration-date');
            }
        }

        return $new_columns;
    }

    /**
     * Mostramos valor de la duración de validez
     * @param mixed $column
     * @param mixed $post_id
     * @return void
     * @author Daniel Lucia
     */
    public function renderCustomColumns($column, $post_id)
    {
        if ($column === 'validity_duration') {

            //Obtenemos fecha de creacion del post
            $creation_date = get_the_date('Y-m-d', $post_id);
            $validity_duration = get_post_meta($post_id, 'validity_duration', true);

            //Sumamos validity_duration a creation_Date
            $expiration_date = date('Y-m-d', strtotime($creation_date . ' + ' . $validity_duration));
            $expiration_date_formatted = date_i18n(get_option('date_format'), strtotime($expiration_date));

            if ($validity_duration != '') {
                echo '<pre style="margin: 0;font-size: 11px;line-height: 14px;">' . esc_html($validity_duration) . '<br /><strong>' .  esc_html($expiration_date_formatted) . '</strong></pre>';
            } else {
                echo '<pre style="margin: 0;font-size: 11px;line-height: 14px;opacity: 0.5">' . __('n/a', 'dl-ticket-manager-expiration-date') . '</pre>';
            }
        }
    }

    /**
     * Añadimos estilos personalizados a las columnas
     * @return void
     * @author Daniel Lucia
     */
    public function customColumnStyles()
    {
        echo '<style>
            .column-validity_duration { width: 150px; }
        </style>';
    }

    /**
     * Validamos el ticket
     * @param mixed $ticket
     * @return bool
     * @author Daniel Lucia
     */
    public function validateTicket($ticket)
    {
        $creation_date = get_the_date('Y-m-d', $ticket['id']);
        $validity_duration = $ticket['validity_duration'];
        $expiration_date = date('Y-m-d', strtotime($creation_date . ' + ' . $validity_duration));

        if ($expiration_date < date('Y-m-d')) {
            
            return new \WP_Error(
                'ticket_expired', 
                __('The ticket has expired.', 'dl-ticket-manager-expiration-date')
            );
            
        }

        return true;
    }
}
