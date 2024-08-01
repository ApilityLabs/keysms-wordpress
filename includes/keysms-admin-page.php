<?php

namespace KeySMS;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

function admin_menu()
{
    add_menu_page(
        __('KeySMS', 'keysms'),
        __('KeySMS', 'keysms'),
        'manage_options',
        'keysms',
        'KeySMS\\admin_page',
        'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDIwMDEwOTA0Ly9FTiIKICJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4wIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiB3aWR0aD0iNDguMDAwMDAwcHQiIGhlaWdodD0iNDguMDAwMDAwcHQiIHZpZXdCb3g9IjAgMCA0OC4wMDAwMDAgNDguMDAwMDAwIgogcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQgbWVldCI+Cgo8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLjAwMDAwMCw0OC4wMDAwMDApIHNjYWxlKDAuMTAwMDAwLC0wLjEwMDAwMCkiCmZpbGw9IiMwMDAwMDAiIHN0cm9rZT0ibm9uZSI+CjxwYXRoIGQ9Ik0xNDggMzg5IGwtODggLTUxIDAgLTEwMyAwIC0xMDMgODkgLTUyIDkwIC01MSA5MCA1MSA5MSA1MSAwIDEwNCAwCjEwNCAtOTAgNTAgYy00OSAyOCAtOTEgNTEgLTkyIDUxIC0yIDAgLTQyIC0yMyAtOTAgLTUxeiBtMTc1IC04OSBjNTkgLTY2IDE3Ci0xNDMgLTg4IC0xNjAgLTkyIC0xNSAtOTQgLTE1IC05MyAyMyAwIDE3IC0zIDQ0IC03IDYwIC0yNiA5NyAxMTUgMTU1IDE4OCA3N3oiLz4KPC9nPgo8L3N2Zz4K',
    );

    add_submenu_page(
        'keysms',
        __('Send SMS', 'keysms'),
        __('Send SMS', 'keysms'),
        'manage_options',
        'keysms',
        'KeySMS\\admin_page'
    );

    add_support_link();
}

function add_support_link()
{
    global $submenu;
    $url = 'http://www.keysms.no/kontakt-oss';
    $submenu['keysms'] = !isset($submenu['keysms']) ? [] : $submenu['keysms'];
    $submenu['keysms'][] = [__('Support', 'keysms'), 'manage_options', $url];
}

add_action('admin_menu', 'KeySMS\admin_menu');

function admin_page()
{
    $keysms = KeySMS::get_instance();
    $error = false;

    if ($keysms === null) {
        $url = esc_url(add_query_arg(
            'page',
            'keysms_settings',
            get_admin_url() . 'options-general.php'
        ));

        $message = __('Please configure your KeySMS credentials.', 'keysms');

        add_settings_error('keysms', 'keysms_missing_credentials', "<a href=\"" . $url . "\">" . $message . "</a>", 'keysms', 'error');
        $error = true;
    }

    if (!$error) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nonce = $_REQUEST['_wpnonce'];

            if (!wp_verify_nonce($nonce, 'keysms_send_sms')) {
                exit;
            }

            $recipient = sanitize_text_field($_POST['keysms_recipient']);
            $message = sanitize_text_field($_POST['keysms_message']);
            $sender = null;
            $options = [];

            $response = KeySMS::get_instance()
                ->send_sms($recipient, $message, $sender, $options);

            if (is_wp_error($response)) {
                /** @var WP_Error $response */
                add_settings_error('keysms', $response->get_error_code(), $response->get_error_message(), 'error');
            } else {
                add_settings_error('keysms', 'keysms_success', __('The SMS has been sent successfully.', 'keysms'), 'success');
            }
        }
    }

    $senders = [];

    if ($keysms !== null) {
        $senders = $keysms->get_senders();
    }

    $sender = null;
    $options = get_option('keysms_options');

    if (isset($options['default_sender'])) {
        $sender = $options['default_sender'];
    }

    $nonce = wp_create_nonce('keysms_send_sms');
?>
    <div class="wrap">
        <img src="<?php echo esc_attr(plugin_dir_url(dirname(__FILE__)) . 'assets/logo.png'); ?>" alt="KeySMS" style="max-width: 100%; height: auto;">
        <h1><?php echo esc_html(__('Send SMS', 'keysms')); ?></h1>
        <?php settings_errors() ?>
        <form method="post">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <table class="form-table">
                <?php if (count($senders)) { ?>
                    <tr>
                        <th scope="row"><label for="keysms_sender"><?php echo esc_html(__('Sender', 'keysms')); ?></label></th>
                        <td>
                            <select name="keysms_sender" id="keysms_sender" <?php echo esc_attr(!$error ?: 'disabled'); ?>>
                                <option value=""><?php echo esc_html(__('Select sender', 'keysms')); ?></option>
                                <?php foreach ($senders as $alias) { ?>
                                    <option value="<?php echo esc_attr($alias) ?>" <?php echo esc_attr($alias === $sender ? 'selected' : ''); ?>><?php echo esc_html($alias); ?></option>
                                <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <th scope="row"><label for="keysms_recipient"><?php echo esc_html(__('Recipient', 'keysms')); ?></label></th>
                    <td><input required type="tel" name="keysms_recipient" id="keysms_recipient" <?php echo esc_attr(!$error ?: 'disabled'); ?>></td>
                </tr>
                <tr>
                    <th scope="row"><label for="keysms_message"><?php echo esc_html(__('Message', 'keysms')); ?></label></th>
                    <td><textarea required name="keysms_message" id="keysms_message" rows="5" cols="50" <?php echo esc_attr(!$error ?: 'disabled'); ?>></textarea></td>
                </tr>
            </table>
            <?php submit_button(__('Send SMS', 'keysms'), 'primary', 'submit', true, ['disabled' => esc_attr(!$error ?: 'disabled')]); ?>
        </form>

        </table>
    </div>
<?php
}
