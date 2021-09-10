<?php
/**
 * Plugin Name: Gatsby build hook
 * Description: This hook will update the front end of the website when any change is made to a page. It triggers a build of gatsby and moved the output to the public html.
 * Version: 1.0
 * Author: JakeTalley
 */

add_action('publish_future_post', 'nb_webhook_future_post', 10);
add_action('publish_post', 'debounced_build', 10, 2);
add_action('publish_page', 'debounced_build', 10, 2);
add_action('post_updated', 'nb_webhook_update', 10, 3);
add_action('run_gatsby_build', 'run_gatsby_build', 10, 2);

/*Handle both schedule updates, new posts, and regular updates */
function nb_webhook_future_post( $post_id ) {
  debounced_build($post_id, get_post($post_id));
}

function nb_webhook_update($post_id, $post_after, $post_before) {
  debounced_build($post_id, $post_after);
}

function run_gatsby_build($post_id, $post) {
  if ($post->post_status === 'publish') {
    $old_path = getcwd();
    $file_path = __DIR__;
    $url_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
    write_log('currently working directory: ' . $old_path);
    chdir($old_path . $url_path);
    write_log("location updated to: " . shell_exec('echo $PWD'));
    write_log("whoami: " . shell_exec('whoami'));
    $retval=null;
    $output=null;
    write_log('beginning gatsby build');
    exec('./gatsbyBuild.sh', $output, $retval);
    write_log('shell output:');
    write_log( $output);
    write_log('shell return val:' . $retval);
    chdir($old_path);
    write_log("location returned to: " . shell_exec('echo $PWD'));
  }
}


/* aggregate updates to rebuild site max once every 60 seconds */
function debounced_build($post_id, $post) {
        $build_time = get_transient('build_time');

        if($build_time) {
            write_log('build already running, wait 60 seconds');
            wp_clear_scheduled_hook('run_gatsby_build', array($post_id, $post));
            wp_schedule_single_event($build_time, 'run_gatsby_build', array($post_id, $post));
        } else {
            run_gatsby_build($post_id, $post);
            write_log('setting transient');
            set_transient('build_time', time() + 20, 20);
        }
    }
?>
