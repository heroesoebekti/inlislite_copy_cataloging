<?php
/**
 * Plugin Name: Inlislite Copy Cataloging
 * Plugin URI: https://github.com/heroesoebekti/inlislite_copy_cataloging
 * Description: Digunakan untuk saln katalog dari Aplikasi Inlislite.
 * Version: 1 beta
 * Author: Heru Subekti
 * Author URI: https://www.facebook.com/heroe.soebekti
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// registering menus
$plugin->registerMenu('bibliography', 'Inlislite Copy Cataloging', __DIR__ . '/index.php');
