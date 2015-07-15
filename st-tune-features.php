<?php
/**
 * Plugin Name: St. Tune features
 * Plugin URI: http://www.softrest.ru/
 * Description: Switch off and switch on some features for security and speedup reason.
 * Version: 1.5
 * Author: softrest
 * Author URI: http://www.softrest.ru/
 */

/*
  Copyright 2015 Softrest ltd. (email: info@softrest.eu)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * on
 */

add_post_type_support('page', 'excerpt');
add_theme_support('menus');

add_filter('widget_text', 'do_shortcode', 11);
//add_filter('widget_execphp_st', 'do_shortcode', 11);

/**
 * off
 */

add_filter('login_errors', create_function('$a', "return 'Error! Incorrect data. Try again.';"));

add_filter('show_admin_bar', '__return_false');

remove_action('wp_head', 'wp_generator');
add_filter('the_generator', create_function('$a', 'return "";'));

remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');

remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'parent_post_rel_link');
remove_action('wp_head', 'start_post_rel_link');
remove_action('wp_head', 'adjacent_posts_rel_link');
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);

/**
 * disable xml rpc
 */

add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', 'st_remove_x_pingback');
add_filter('bloginfo_url', 'st_remove_pingback_url', 1, 2);
add_filter('bloginfo', 'st_remove_pingback_url', 1, 2);

function st_remove_x_pingback($headers)
{
    unset($headers['X-Pingback']);
    return $headers;
}

function st_remove_pingback_url($output, $show)
{
    if ($show == 'pingback_url') {
        $output = '';
    }
    return $output;
}

add_filter('xmlrpc_methods', 'st_Remove_Pingback_Method');

function st_Remove_Pingback_Method($methods)
{
    unset($methods['pingback.ping']);
    unset($methods['pingback.extensions.getPingbacks']);
    return $methods;
}

/*
  Executable PHP widget /mod/
  http://wordpress.org/extend/plugins/php-code-widget/
  Like the Text widget, but it will take PHP code as well. Heavily derived from the Text widget code in WordPress.
  Otto
  2.1//mod.2
  http://ottodestruct.com
  License: GPL2

  Copyright 2009  Samuel Wood  (email : otto@ottodestruct.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2,
  as published by the Free Software Foundation.

  You may NOT assume that you can use any other version of the GPL.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  The license for this software can likely be found here:
  http://www.gnu.org/licenses/gpl-2.0.html

 */

class PHP_Code_Widget_st extends WP_Widget
{

    function PHP_Code_Widget_st()
    {
        $widget_ops = array(
            'classname' => 'widget_execphp_st',
            'description' => 'Arbitrary text, HTML, or PHP Code'
        );
        $control_ops = array('width' => 400, 'height' => 350);
        $this->WP_Widget('execphp', 'PHP Code', $widget_ops, $control_ops);
    }

    function widget($args, $instance)
    {
        extract($args);
        $cssclass = empty($instance['cssclass']) ? '' : $instance['cssclass'];
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance);
        $text = apply_filters('widget_execphp_st', $instance['text'], $instance);

        if ($cssclass != '')
            $before_widget = preg_replace('/class="([^"]*)"/', 'class="\\1 ' . $cssclass . '"', $before_widget);

        echo $before_widget;
        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }
        ob_start();
        eval('?>' . $text);
        $text = ob_get_contents();
        ob_end_clean();
        ?>			
        <div class="execwidget"><?php echo $instance['filter'] ? wpautop($text) : $text; ?></div>
        <?php
        echo $after_widget;
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        if (current_user_can('unfiltered_html'))
            $instance['text'] = $new_instance['text'];
        else
            $instance['text'] = stripslashes(wp_filter_post_kses($new_instance['text']));
        $instance['filter'] = isset($new_instance['filter']);
        $instance['cssclass'] = $new_instance['cssclass'];
        //$instance['cssclass'] = 'widget_execphp_st '.$new_instance['cssclass'];
        return $instance;
    }

    function form($instance)
    {
        $instance = wp_parse_args((array) $instance, array('title' => '', 'text' => '', 'cssclass' => ''));
        $title = strip_tags($instance['title']);
        $text = format_to_edit($instance['text']);
        $cssclass = $instance['cssclass'];
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

        <textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

        <p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs.'); ?></label></p>

        <p><label for="<?php echo $this->get_field_id('cssclass'); ?>"><?php _e('CSS class:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('cssclass'); ?>" name="<?php echo $this->get_field_name('cssclass'); ?>" type="text" value="<?php echo esc_attr($cssclass); ?>" /></p>
        <?php
    }

}

add_action('widgets_init', create_function('', 'return register_widget("PHP_Code_Widget_st");'));

/**/

function admin_alert_errors($errno, $errstr, $errfile, $errline)
{
    $errorType = array(
        E_ERROR => 'ERROR',
        E_CORE_ERROR => 'CORE ERROR',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_USER_ERROR => 'USER ERROR',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_WARNING => 'WARNING',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_WARNING => 'USER WARNING',
        E_NOTICE => 'NOTICE',
        E_USER_NOTICE => 'USER NOTICE',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED',
        E_PARSE => 'PARSING ERROR'
    );

    if (array_key_exists($errno, $errorType)) {
        $errname = $errorType[$errno];
    } else {
        $errname = 'UNKNOWN ERROR';
    }
    if (!in_array($errno, array(E_NOTICE))) {
        ob_start();
        ?>
        <div class="error">
            <p>
                <strong><?php echo $errname; ?> Error: [<?php echo $errno; ?>] </strong><?php echo $errstr; ?><strong> <?php echo $errfile; ?></strong> on line <strong><?php echo $errline; ?></strong>
            <p/>
        </div>
        <?php
        echo ob_get_clean();
    }
}

//set_error_handler("admin_alert_errors", E_ERROR ^ E_CORE_ERROR ^ E_COMPILE_ERROR ^ E_USER_ERROR ^ E_RECOVERABLE_ERROR ^ E_WARNING ^ E_CORE_WARNING ^ E_COMPILE_WARNING ^ E_USER_WARNING ^ E_NOTICE ^ E_USER_NOTICE ^ E_DEPRECATED ^ E_USER_DEPRECATED ^ E_PARSE);

//eof