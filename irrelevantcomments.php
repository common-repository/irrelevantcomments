<?php
/*
Plugin Name: irrelevantComments
Plugin URI: http://www.kosmonauten.cc/wordpress/irrelevantcomments
Description: This plugin adds the ability to mark a comment as irrelevant.
Author: Christian Klein
Version: 1.0.1
Author URI: http://www.kosmonauten.cc/
*/

if (!defined('WP_CONTENT_URL'))
    define('WP_CONTENT_URL', get_option('siteurl' ).'/wp-content');
if (!defined('WP_PLUGIN_URL'))
    define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');
if (!defined('WP_CONTENT_DIR' ))
  define('WP_CONTENT_DIR', ABSPATH.'wp-content');
if (!defined('WP_PLUGIN_DIR' ))
  define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');

if (!class_exists('IrrelevantComments')) {
  class IrrelevantComments {
    
    var $meta_comment = '_irrcomments_marked';
    var $meta_post = '_irrcomments_deny'; // former '_irrcomments_allow'
    var $meta_option = '_irrcomments_version';
    var $version = '1.0';
    
    function IrrelevantComments() {
      $this->__construct();
    }
    
    function __construct() {
      if (!is_admin()) {
        add_action('comment_form', array(&$this, 'addCheckbox'));
        add_action('comment_post', array(&$this, 'saveComment'));
        add_action('wp_print_scripts', array(&$this, 'enqueueScripts'));
        add_action('wp_head', array(&$this, 'addWPHead'));
        
        add_filter('get_comment_text', array(&$this, 'manipulateComment'));
        add_filter('edit_comment_link', array(&$this, 'addEditLink'));
      }
      else {
        add_action('admin_head', array(&$this, 'adminHandleComment'));
        add_action('admin_notices', array(&$this, 'adminAddMessage'));
        add_action('admin_menu', array(&$this, 'adminAddMetaBox'));
        add_action('save_post', array(&$this, 'adminSavePost'));
        add_action('delete_post', array(&$this, 'adminDeletePost'));
        add_action('delete_comment', array(&$this, 'adminDeleteComment'));
        
        add_filter('comment_text', array(&$this, 'adminAddLink'));
               
        if (version_compare(get_option($this->meta_option), '1.0', '<'))
          add_action('admin_notices', array(&$this, 'plugin_upgrade'));
        
        register_deactivation_hook(__FILE__, array(&$this, 'deactivatePlugin'));
      }
      
      /*add_action('irrcomments_cleanup_cron', array(&$this, 'meta_cleanup'));
      remove_action('irrcomments_cleanup_cron', array(&$this, 'meta_cleanup_deactivate'));
        
      if (!wp_next_scheduled('irrcomments_cleanup_cron')) {
        wp_schedule_event( time(), 'hourly', 'irrcomments_cleanup_cron');
      }*/
      
      load_plugin_textdomain('irrelevantcomments', false, dirname(plugin_basename(__FILE__)).'/lang');
    }



    /* Meta Functions ----------------------------------------------------------------------- */ 
    function comment_irrelevant($commentID) {
      $get = get_comment_meta($commentID, $this->meta_comment, true);
      return !empty($get);
    }
    
    function add_comment($commentID) {
      add_comment_meta($commentID, $this->meta_comment, 1);
    }
    
    function delete_comment($commentID) {
      delete_comment_meta($commentID, $this->meta_comment);
    }
    
    function allow_irrcomments($postID) {
      $get = get_post_meta($postID, $this->meta_post, true);
      return empty($get);
    }
    
    function plugin_upgrade() {
      if (!function_exists('update_comment_meta')) {
        echo '<div id="message" class="updated fade"><p>'.__('Requires Wordpress 2.9 or higher!', 'irrelevantcomments').'</p></div>';
        return;
      }
      global $wpdb;

      $posts = $wpdb->get_col('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key LIKE \''.$this->meta_comment.'\'');
      foreach ($posts as $postid) {
        $meta = get_post_meta($postid, $this->meta_comment);
        $comments = (array) $meta[0];
        foreach ($comments as $commentid) {
          update_comment_meta($commentid, $this->meta_comment, 1);
        }
        delete_post_meta($postid, $this->meta_comment);
      }
      
      $postmeta = $wpdb->get_col('SELECT ID FROM '.$wpdb->posts.' WHERE post_status=\'publish\' AND post_type=\'post\'');
      foreach ($postmeta as $postid) {
        $d = get_post_meta($postid, '_irrcomments_allow');
        if (empty($d))
          update_post_meta($postid, $this->meta_post, 1);
      }
      $postmeta = $wpdb->get_col('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key LIKE \'_irrcomments_allow\'');
      foreach ($postmeta as $postid)
        delete_post_meta($postid, '_irrcomments_allow', 1);
      
      update_option($this->meta_option, $this->version);
      
      echo '<div id="message" class="updated fade"><p>'.sprintf(__('Upgraded successfully to irrelevantComments %s!', 'irrelevantcomments'), $this->version).'</p></div>';
    }
    
    /* -------------------------------------------------------------------------------------- */
    function deactivatePlugin() {
      global $wpdb;
      $posts = $wpdb->get_col('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key LIKE \''.$this->meta_post.'\'');
      foreach($posts as $postid)
        delete_post_meta($postid, $this->meta_post);
      
      $comments = $wpdb->get_col('SELECT comment_id FROM '.$wpdb->commentmeta.' WHERE meta_key LIKE \''.$this->meta_comment.'\'');
      foreach($comments as $commentid)
        delete_comment_meta($commentid, $this->meta_comment);
      
      delete_option($this->meta_option);
    }
    
    
    /*function meta_cleanup() {
      global $wpdb;
      
      $comments = $wpdb->get_col('SELECT comment_ID FROM '.$wpdb->commentmeta.' WHERE meta_key LIKE \''.$this->meta_comment.'\'');
      foreach($comments as $commentid) {
        if (!wp_get_comment_status($commentid))
          delete_comment_meta($commentid, $this->meta_comment);  
      }
    }
    function meta_cleanup_deactivate() {
        wp_clear_scheduled_hook('my_task_hook');
    }*/
    
        
    /* Comment page functions --------------------------------------------------------------- */
    function addCheckbox() {
      global $post;
      if ($this->allow_irrcomments($post->ID)) {
        ?>
        <p class="irrComments_form" style="clear: both;">
          <input type="checkbox" name="irrComments_markAsIrrelevant" id="irrComments_markAsIrrelevant" value="1" style="width: auto;" />
          <label for="irrComments_markAsIrrelevant">
            <?php _e('Mark comment as irrelevant', 'irrelevantcomments'); ?>
          </label>
        </p>
        <?php
      }
    }
    
    function saveComment($commentID) {
      if (!empty($_POST['irrComments_markAsIrrelevant']))
        $this->add_comment($commentID);
    }
    
    function manipulateComment($comment_text) {
      if (is_feed() || !have_comments() || (get_comment_type() != 'comment'))
        return $comment_text;

      global $post;
      $comment_ID = get_comment_ID();
      if ($this->comment_irrelevant($comment_ID) && $this->allow_irrcomments($post->ID)) {
        $comment_text = str_replace('<blockquote>', '<blockquote><p>', $comment_text);
        $output = '<p class="irrComments_link" id="irrCommentLink'.$comment_ID.'">'.__('Irrelevant comment', 'irrelevantcomments').' (<a href="#" onclick="irrComments_toggle(\''.$comment_ID.'\');return false;">'.__('show', 'irrelevantcomments').'</a>)</p>';
        $output .= '<div class="irrComments_marked" id="irrComment'.$comment_ID.'" style="display: none;"><p>'.$comment_text.'</p></div>';
        return $output;
      }
      else {
        return $comment_text;
      }
    }
    
    function addEditLink($link) {
      global $post;
      $output = $link;
      if ($this->allow_irrcomments($post->ID)) {
        $comment_ID = get_comment_ID();
        $statusText = ($this->comment_irrelevant($comment_ID)) ? __('mark as relevant', 'irrelevantcomments') : __('mark as irrelevant', 'irrelevantcomments');
        $output .= ' (<a href="'.admin_url('edit-comments.php?doaction=irrelevantcomments&amp;id=').$comment_ID.'&amp;p='.$post->ID.'" class="irrComments_adminlink">'.$statusText.'</a>)';
      }
      return $output;
    }
    
    function enqueueScripts() {
      if (!is_single() || is_feed())
        return;
      
      global $post;
      if ($this->allow_irrcomments($post->ID)) {
        //wp_enqueue_script('jquery');
        wp_enqueue_script('irrelevantcommentsjs', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/irrelevantcomments.js', array('jquery'));
        wp_localize_script('irrelevantcommentsjs', 'irrcomments_lang', array('hide' => __('hide', 'irrelevantcomments'), 'show' => __('show', 'irrelevantcomments') ));
      }
    }
    
    function addWPHead() {
      if (!is_single() || is_feed())
        return;
        
      global $post;
      if ($this->allow_irrcomments($post->ID)) {
        $relpath = '/'.dirname(plugin_basename(__FILE__)).'/irrelevantcomments.css';
        if (file_exists(WP_PLUGIN_DIR.$relpath))
          echo '<link rel="stylesheet" type="text/css" href="'.WP_PLUGIN_URL.$relpath.'" />';
      }
    }


    /* Admin functions ---------------------------------------------------------------------- */
    function adminAddMessage() {
      echo '<div id="message" class="updated fade" style="display: none;"></div>';
    }
    function adminHandleComment() {
      if ((basename($_SERVER['PHP_SELF']) != 'edit-comments.php') || ($_REQUEST['doaction'] != 'irrelevantcomments'))
        return;
      if (empty($_REQUEST['id']))
        return;
      
      $comment_ID = (int) $_REQUEST['id'];
      $statusMsg = '';
      
      $the_comment = get_comment($comment_ID);
      if (empty($the_comment)) {
        $statusMsg = __('Error: Could not find comment.', 'irrelevantcomments');
      }
      else {     
        if ($this->comment_irrelevant($comment_ID)) {
          $statusMsg = sprintf(__('Comment #%s was marked as relevant.', 'irrelevantcomments'), '<a href=\"#comment-'.$comment_ID.'\">'.$comment_ID.'</a>');
          $this->delete_comment($comment_ID);
        }
        else {
          $statusMsg = sprintf(__('Comment #%s was marked as irrelevant.', 'irrelevantcomments'), '<a href=\"#comment-'.$comment_ID.'\">'.$comment_ID.'</a>');
          $this->add_comment($comment_ID);
        }
      }
      ?>
      <script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready(function($) {
          $("#message").html("<p><?php echo $statusMsg; ?></p>");
          $("#message").fadeIn("normal");
        });
        //]]>
      </script>
      <?php
    }
    
    function adminAddLink($comment_text) {
      global $comment;
      if ((basename($_SERVER['PHP_SELF']) == 'edit-comments.php') && (get_comment_type() == 'comment') && $this->allow_irrcomments($comment->comment_post_ID)) {
        
        $urlquery = (!empty($_GET['p'])) ? '&amp;p='.$_GET['p'] : '';
        if ($this->comment_irrelevant($comment->comment_ID)) {
          $output = '<p><strong style="text-decoration: underline;">'.__('Irrelevant comment', 'irrelevantcomments').':</strong> <a href="?doaction=irrelevantcomments&amp;id='.$comment->comment_ID.'" class="buton-primary">'.__('mark as relevant', 'irrelevantcomments').'</a></p>';
          $output = $comment_text;
          $output .= '<p>&nbsp;</p><p><a href="?doaction=irrelevantcomments&amp;id='.$comment->comment_ID.$urlquery.'" class="button-primary">'.__('mark as relevant', 'irrelevantcomments').'</a></p>';
        }
        else {
          $output = $comment_text;
          $output .= '<p>&nbsp;</p><p><a href="?doaction=irrelevantcomments&amp;id='.$comment->comment_ID.$urlquery.'" class="button"><b>'.__('mark as irrelevant', 'irrelevantcomments').'</b></a></p><br />';
        }
        return $output;
      }
      else {
        return $comment_text;
      }
    }
    
    function adminAddMetaBox() {
      add_meta_box('irrcomments_metabox', __('irrelevant Comments', 'irrelevantcomments'), array(&$this, 'irrComments_add_meta_box'), 'post', 'side');
    }
    
    function irrComments_add_meta_box() {
      global $post_ID, $temp_ID;
      $thisID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
      ?>
      <p>
        <input type="hidden" name="irrcomments_nonce" id="irrcomments_nonce" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>" />
        <input type="checkbox" name="irrComments_allow" id="irrComments_allow" value="1" <?php echo ($this->allow_irrcomments($thisID)) ? 'checked="checked"' : ''; ?>/>
        <label for="irrComments_allow"><?php _e('Allow to mark comments as irrelevant', 'irrelevantcomments'); ?></label>
      </p>
      <?php
    }
    
    function adminDeleteComment($comment_ID) {
      delete_comment_meta($comment_ID, $this->meta_comment);
    }
    
    function adminDeletePost($postID) {
      global $wpdb;
      delete_post_meta($postID, $this->meta_post);
      
      $comments = $wpdb->get_col('SELECT comment_ID FROM '.$wpdb->comments.' WHERE comment_post_ID=\''.$postID.'\'');
      foreach($comments as $commentid)
        delete_comment_meta($commentid, $this->meta_comment);
    }
    
    
    function adminSavePost($postID) {
      if (isset($_POST['_inline_edit']) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE))
        return $postID;
        
      if (!wp_verify_nonce($_POST['irrcomments_nonce'], plugin_basename(__FILE__)))
        return $postID;
        
      if (isset($_POST['irrComments_allow'])) {
        delete_post_meta($postID, $this->meta_post );
      }
      else {
        add_post_meta($postID, $this->meta_post, 1, true) or
          update_post_meta($postID, $this->meta_post, 1);
      }
    }
    

  }
}

new IrrelevantComments();

?>
