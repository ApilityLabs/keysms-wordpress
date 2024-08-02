<?php

namespace KeySMS;

if (!defined('ABSPATH')) {
    exit;
}

function init_settings()
{
    register_setting('keysms', 'keysms_options');

    $options = get_option('keysms_options');

    add_settings_section(
        'keysms_section_credentials',
        __('Credentials', 'keysms'),
        'KeySMS\\section_credentials_callback',
        'keysms',
        [
            'keysms_credentials' => isset($options['username']) && isset($options['api_key'])
        ]
    );

    add_settings_field(
        'keysms_field_username',
        __('Username', 'keysms'),
        'KeySMS\\field_username_callback',
        'keysms',
        'keysms_section_credentials',
        [
            'label_for' => 'username',
            'class' => 'keysms_row',
            'keysms_username' => isset($options['username']) ? $options['username'] : ''
        ]
    );

    add_settings_field(
        'keysms_field_api_key',
        __('API key', 'keysms'),
        'KeySMS\\field_api_key_callback',
        'keysms',
        'keysms_section_credentials',
        [
            'label_for' => 'api_key',
            'class' => 'keysms_row',
            'keysms_api_key' => isset($options['api_key']) ? $options['api_key'] : ''
        ]
    );

    add_settings_section(
        'keysms_section_sender',
        __('Sender', 'keysms'),
        'KeySMS\\section_sender_callback',
        'keysms'
    );

    add_settings_field(
        'keysms_field_sender',
        __('Default Sender', 'keysms'),
        'KeySMS\\field_sender_callback',
        'keysms',
        'keysms_section_sender',
        [
            'label_for' => 'default_sender',
            'class' => 'keysms_row',
            'keysms_default_sender' => isset($options['default_sender']) ? $options['default_sender'] : '',
            'keysms_senders' => isset($senders) ? $senders : []
        ]
    );

    add_filter('plugin_action_links_keysms/keysms.php', 'KeySMS\\keysms_settings_link');

    function keysms_settings_link($links)
    {
        $url = esc_url(add_query_arg(
            'page',
            'keysms_settings',
            get_admin_url() . 'options-general.php'
        ));

        $settings_link = "<a href='$url'>" . __('Settings') . '</a>';

        array_push(
            $links,
            $settings_link
        );
        return $links;
    }
}

add_action('admin_init', 'KeySMS\\init_settings');

/**
 * @param array $args
 */
function section_credentials_callback($args)
{
    if ((!$args['keysms_credentials'])) {
?>
        <p id="<?php echo esc_attr($args['id']); ?>">
            <?php echo esc_html(__("Don't have an account?", 'keysms')); ?> <a href="https://app.keysms.no/demo" target="_blank"><?php echo esc_html(__("Sign up for a free trial", 'keysms')); ?></a>.
        </p>
    <?php
    }
}

/**
 * @param array $args
 */
function section_sender_callback($args)
{
    //
}

/**
 * @param array $args
 */
function field_username_callback($args)
{
    ?>
    <input type="text" id="<?php echo esc_attr($args['label_for']); ?>" name="keysms_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($args['keysms_username']); ?>">
<?php
}

/**
 * @param array $args
 */
function field_api_key_callback($args)
{
    $options = get_option('keysms_options');
?>
    <input type="text" id="<?php echo esc_attr($args['label_for']); ?>" name="keysms_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($args['keysms_api_key']); ?>">
<?php
}

/**
 * @param array $args
 */
function field_sender_callback($args)
{
    if ($keysms = KeySMS::get_instance()) {
        $keysms->get_senders(true);
    }

    $senders = get_option('keysms_senders');

    if ($senders === false) {
        $senders = [];
    }
?>
    <select id="<?php echo esc_attr($args['label_for']); ?>" name="keysms_options[<?php echo esc_attr($args['label_for']); ?>]">
        <option value=""><?php echo esc_attr(__('Select sender', 'keysms')); ?></option>
        <?php foreach ($senders as $sender) { ?>
            <option value="<?php echo esc_attr($sender); ?>" <?php echo esc_attr($sender === $args['keysms_default_sender'] ? 'selected' : ''); ?>><?php echo esc_attr($sender); ?></option>
        <?php } ?>
    </select>
<?php
}

function options_page()
{
    add_submenu_page(
        'options-general.php',
        'KeySMS',
        'KeySMS',
        'manage_options',
        'keysms_settings',
        'KeySMS\\options_page_html'
    );
}

// Hook into the admin menu
add_action('admin_menu', 'KeySMS\\options_page');

function options_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $invalid_credentials = false;

    if ($keysms = KeySMS::get_instance()) {
        $result = $keysms->get_account_info();
        if (isset($result['error']) && $result['error'] === 'not_authed') {

            $invalid_credentials = true;
        }
    }
?>
    <div class="wrap">
        <img src="<?php echo esc_attr(plugin_dir_url(dirname(__FILE__)) . 'assets/logo.png'); ?>" alt="<?php echo esc_attr(__('KeySMS')); ?>" style="max-width: 100%; height: auto;">
        <?php if ($invalid_credentials) { ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php echo esc_html(__('Invalid KeySMS credentials.', 'keysms')); ?></strong>
                </p>
            </div>
        <?php } ?>
        <form action="options.php" method="post">
            <?php
            settings_fields('keysms');
            do_settings_sections('keysms');
            submit_button(__('Save Settings', 'keysms'));
            ?>
        </form>
    </div>
<?php
}
