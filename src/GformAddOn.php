<?php

namespace GeneroWP\GravityformsLianamailer;

use Exception;
use GeneroWP\Common\Singleton;
use GeneroWP\GravityformsLianamailer\LianaMailer\LianaMailerApi;
use GeneroWP\GravityformsLianamailer\LianaMailer\LianaMailerApi\RestClientAuthorizationException;
use GFFeedAddOn;
use GFCommon;
use GFForms;

GFForms::include_feed_addon_framework();

class GformAddOn extends GFFeedAddOn
{
    use Singleton;

    protected $_version = '0.1';
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'gravityforms-lianamailer';
    protected $_path = 'gravityforms-lianamailer/plugin.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms LianaMailer Add-On';
    protected $_short_title = 'LianaMailer';

    protected $api;

    const LIANAMAILER_API_VERSION = 1;

    public static function get_instance()
    {
        return self::getInstance();
    }

    public function plugin_settings_fields()
    {
        return [
            [
                'fields' => [
                    [
                        'name' => 'user',
                        'label' => __('User', 'gravityforms-lianamailer'),
                        'type' => 'text',
                        'class' => 'medium',
                    ],
                    [
                        'name' => 'secret',
                        'label' => __('Secret', 'gravityforms-lianamailer'),
                        'type' => 'text',
                        'class' => 'medium',
                    ],
                    [
                        'name' => 'realm',
                        'label' => __('Realm', 'gravityforms-lianamailer'),
                        'type' => 'text',
                        'class' => 'medium',
                    ],
                    [
                        'name' => 'endpoint',
                        'label' => __('Endpoint', 'gravityforms-lianamailer'),
                        'type' => 'text',
                        'class' => 'medium',
                        'default' => 'https://rest.lianamailer.com',
                    ],
                ],
            ],
        ];
    }
    public function feed_settings_fields()
    {
        return [
            [
                'title' => __('MailChimp Feed Settings', 'gravityforms-lianamailer'),
                'fields' => [
                    [
                        'name'     => 'feedName',
                        'label'    => __('Name', 'gravityforms-lianamailer'),
                        'type'     => 'text',
                        'required' => true,
                        'class'    => 'medium',
                        'tooltip'  => sprintf(
                            '<h6>%s</h6>%s',
                            __('Name', 'gravityforms-lianamailer'),
                            __('Enter a feed name to uniquely identify this setup.', 'gravityforms-lianamailer')
                        ),
                    ],
                    [
                        'name'     => 'lianamailerList',
                        'label'    => __('Mailing List', 'gravityforms-lianamailer'),
                        'type'     => 'lianamailer_list',
                        'required' => true,
                        'tooltip'  => sprintf(
                            '<h6>%s</h6>%s',
                            __('LianaMailer Mailing List', 'gravityforms-lianamailer'),
                            __('Select the mailing list you would like to add your contacts to.', 'gravityforms-lianamailer')
                        ),
                    ],
                ],
            ],
            [
                'dependency' => 'lianamailerList',
                'fields'     => [
                    [
                        'name'      => 'mappedFields',
                        'label'     => __('Map Fields', 'gravityforms-lianamailer'),
                        'type'      => 'field_map',
                        'field_map' => $this->field_map(),
                        'tooltip'   => sprintf(
                            '<h6>%s</h6>%s',
                            __('Map Fields', 'gravityforms-lianamailer'),
                            __('Associate your LianaMailer properties to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityforms-lianamailer')
                        ),
                    ],
                    [
                        'name'    => 'optinCondition',
                        'label'   => __('Conditional Logic', 'gravityforms-lianamailer'),
                        'type'    => 'feed_condition',
                        'tooltip' => sprintf(
                            '<h6>%s</h6>%s',
                            __('Conditional Logic', 'gravityforms-lianamailer'),
                            __('When conditional logic is enabled, form submissions will only be exported to LianaMailer when the conditions are met. When disabled all form submissions will be exported.', 'gravityforms-lianamailer')
                        ),
                    ],
                    ['type' => 'save'],
                ],
            ],
        ];
    }

    public function can_create_feed()
    {
        try {
            $this->api();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function feed_list_columns()
    {
        return [
            'feedName' => __('Name', 'gravityforms-lianamailer'),
            'lianamailer_list_name' => __('Mailing List', 'gravityforms-lianamailer'),
        ];
    }

    public function get_column_value_lianamailer_list_name($feed)
    {
        $listId = (int) rgars($feed, 'meta/lianamailerList');

        try {
            $list = $this->api()->getMailingList($listId);

            return $list['name'];
        } catch (Exception $e) {
            return $listId;
        }
    }

    public function get_conditional_logic_fields()
    {
        $fields = [];
        $form = $this->get_current_form();

        foreach ($form['fields'] as $field) {
            if (!$field->is_conditional_logic_supported()) {
                continue;
            }

            $inputs = $field->get_entry_inputs();
            if ($inputs && 'checkbox' !== $field->get_input_type()) {
                foreach ($inputs as $input) {
                    if (rgar($input, 'isHidden')) {
                        continue;
                    }

                    $fields[] = [
                        'value' => $input['id'],
                        'label' => GFCommon::get_label($field, $input['id']),
                    ];
                }
            } else {
                $fields[] = [
                  'value' => $field->id,
                  'label' => GFCommon::get_label($field),
                ];
            }
        }

        return $fields;
    }

    public function settings_lianamailer_list($field, $echo = true)
    {
        try {
            $mailingLists = $this->api()->getMailingLists();
        } catch (Exception $e) {
            echo sprintf(__('Could not retrieve mailing lists: %s', 'gravityforms-lianamailer'), $e->getMessage());
            return;
        }

        $options[] = [
            'label' => __('Select mailing list', 'gravityforms-lianamailer'),
            'value' => '',
        ];

        foreach ($mailingLists as $list) {
            $options[] = [
                'label' => $list['name'],
                'value' => $list['id'],
            ];
        }

        $field['type'] = 'select';
        $field['choices']  = $options;
        $field['onchange'] = 'jQuery(this).parents("form").submit();';
        $html = $this->settings_select($field, false);

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    public function process_feed($feed, $entry, $form)
    {
        $email = $this->get_field_value($form, $entry, $feed['meta']['mappedFields_EMAIL']);
        $origin = $_SERVER['HTTP_REFERER'] ?? get_home_url();
        $listId = (int) $feed['meta']['lianamailerList'];
        $this->log_debug(__METHOD__ . "(): Starting process");

        if (GFCommon::is_invalid_or_empty_email($email)) {
            $this->log_debug(__METHOD__ . "(): Invalid email");
            $this->add_feed_error(__('A valid email address must be provided.', 'gravityforms-lianamailer'), $feed, $entry, $form);

            return $entry;
        }

        try {
            $recipientId = $this->api()->existsRecipient($email);

            if (!$recipientId) {
                $recipientId = $this->api()->createRecipient($email, [
                    'autoconfirm' => true,
                    'origin' => $origin,
                ]);
                $this->log_debug(__METHOD__ . "(): Created $email recipient");
            }

            $this->log_debug(__METHOD__ . "(): Recipient ID: $recipientId");
            $recipient = $this->api()->getRecipient($recipientId);

            foreach ($recipient['membership'] as $membership) {
                if ($membership[0] === $listId) {
                    $this->log_debug(__METHOD__ . "(): $email was already on list");
                    return $entry;
                }
            }

            // Renable
            if (!$recipient['recipient']['enabled']) {
                $this->api()->enableRecipient($recipientId, sprintf('Signed up for mailing list: %s', $listId));
                $this->log_debug(__METHOD__ . "(): $email recipient was enabled");
            }
        } catch (Exception $e) {
            $this->add_feed_error(sprintf(__('Unable to check if email address is already used by a member: %s', 'gravityforms-lianamailer'), $e->getMessage()), $feed, $entry, $form);

            return $entry;
        }

        $this->log_debug(__METHOD__ . "(): Successfully found recipient");

        try {
            $this->api()->joinMailingList($listId, [$recipientId], [
                'admin' => 0,
                'origin' => $origin,
                'reason' => sprintf('Signed up for newsletter'),
            ]);

            $this->log_debug(__METHOD__ . "(): $email successfully added to mailing list");
        } catch (Exception $e) {
            $this->add_feed_error(sprintf(__('Unable to add recipient to list: %s', 'gravityforms-lianamailer'), $e->getMessage()), $feed, $entry, $form);

            return $entry;
        }
        return $entry;
    }

    protected function field_map()
    {
        $fields = [
            'EMAIL' => [
                'name' => 'EMAIL',
                'label' => __('Email address', 'gravityforms-lianamailer'),
                'required' => true,
                'field_type' => ['email', 'hidden'],
            ],
        ];

        return $fields;
    }

    protected function api(): LianaMailerApi
    {
        if (!$this->api) {
            $user = $this->get_plugin_setting('user');
            $secret = $this->get_plugin_setting('secret');
            $realm = $this->get_plugin_setting('realm');
            $endpoint = $this->get_plugin_setting('endpoint');
            $version = self::LIANAMAILER_API_VERSION;

            if (!$user || !$secret || !$realm || !$endpoint) {
                throw new RestClientAuthorizationException('Missing required settings');
            }

            try {
                $this->api = new LianaMailerApi($user, $secret, $endpoint, $version, $realm);
            } catch (Exception $e) {
                $this->log_error(__METHOD__ . '(): Unable to authenticate with LianaMailer; '. $e->getMessage());

                throw $e;
            }
        }

        return $this->api;
    }
}
