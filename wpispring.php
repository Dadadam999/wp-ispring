<?php
/**
 * Plugin Name: wp-ispring
 * Plugin URI: https://github.com/
 * Description: Плагин позволяет получать информаци о пройденных тестах от iSpring.
 * Version: 1.0.0
 * Author: Bogdanov Andrey
 * Author URI: mailto://swarzone2100@yandex.ru
 *
 * @package Кнопка НМО
 * @author Bogdanov Andrey (swarzone2100@yandex.ru)
 * @since 1.0.9
*/
require_once __DIR__.'/wpispring-autoload.php';

use wpispring\TableMananger;
use wpispring\Main;

register_activation_hook(__FILE__, 'Installwpispring');
register_deactivation_hook(__FILE__, 'Uninstallwpispring');

function Installwpispring()
{
  $tables = new TableMananger();
  $tables->Install();
}

function Uninstallwpispring()
{
  $tables = new TableMananger();
  $tables->Uninstall();
}

add_filter( 'plugin_action_links', function($links, $file)
{
  //проверка - наш это плагин или нет
  if ( $file != plugin_basename(__FILE__) )
    return $links;
  // создаем ссылку
  $settings_link = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=settings_ispring'), 'Настройки');

  array_unshift( $links, $settings_link );
  return $links;
}, 10, 2 );

new Main();
