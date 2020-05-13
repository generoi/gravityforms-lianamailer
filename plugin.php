<?php
/*
Plugin Name:        Gravityforms LianaMailer
Plugin URI:         http://genero.fi
Description:        GravityForms integration with LianaMailer
Version:            0.0.1
Author:             Genero
Author URI:         http://genero.fi/
License:            MIT License
License URI:        http://opensource.org/licenses/MIT
*/
namespace GeneroWP\GravityformsLianamailer;

use Puc_v4_Factory;
use GeneroWP\Common\Singleton;
use GeneroWP\Common\Assets;
use GFAddOn;

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

class Plugin
{
    use Singleton;
    use Assets;

    public $version = '0.0.1';
    public $plugin_name = 'gravityforms-lianamailer';
    public $plugin_path;
    public $plugin_url;
    public $github_url = 'https://github.com/generoi/gravityforms-lianamailer';

    public function __construct()
    {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        Puc_v4_Factory::buildUpdateChecker($this->github_url, __FILE__, $this->plugin_name);

        add_action('gform_loaded', [$this, 'init']);
    }

    public function init()
    {
        GFAddOn::register(GformAddOn::class);
    }
}

Plugin::getInstance();
