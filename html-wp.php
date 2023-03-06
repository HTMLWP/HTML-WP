<?php
/**
  * Plugin Name:   HTML WP | A Complete Solution Of Converting Html site to Wordpress Site | Html Page Builder
  * Description: This plugin integrate your HTML to WP Theme.
  * Author: Krishnendu Paul
  * Author URI:       https://html-wp.com/
  * Version: 2.2
  * Copyright 2022 Krishnendu Paul (email :krshpaul@gmail.com)    
*/
   if ( ! defined( 'ABSPATH' ) ) {
   die( 'Invalid request.' );
}
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( ! function_exists( 'wp_handle_upload' ) )
{
require_once( ABSPATH . 'wp-admin/includes/file.php' );
}
require_once( ABSPATH . 'wp-admin/includes/image.php' );
if ( ! class_exists( 'HTMLWP_Plugin' ) ) :
class HTMLWP_Plugin {

   /**
    * Constructor.
    */
   public function __construct() {
      // register_activation_hook( __FILE__, array( $this , 'activate' ) );
   }


   /**
    * Intialize action after plugin loaded.
    */
   public static function init_actions() {
     
      
      add_action('admin_menu', 'HTMLWP_Plugin::setup_menu');
      add_action('admin_enqueue_scripts', 'HTMLWP_Plugin::callback_for_setting_up_scripts');
      //add_action( 'admin_notices', array( 'HTMLWP_Plugin', 'HTML_admin_notices' ) ); 
       if ( is_user_logged_in() ) {
         if ( current_user_can( 'edit_others_posts' ) ) {
       add_action('wp_ajax_htmlwp_upload_file', 'HTMLWP_Plugin::htmlwp_upload_file');
   }
    }
      
      
       add_filter('https_ssl_verify', '__return_false');
      

    }
    /**
    * Attempts to activate the plugin if at least PHP 5.4 & deactivate Woocommerce.
    */
    public static function activate() {
    update_option('plugin_status', 'active');
    }

    public static function deactivate() {
        update_option('plugin_status', 'inactive');
    }

    public static function setup_menu() {
    add_menu_page('HTML WP', 'HTML WP', 'manage_options', 'HTMLwp-setings', 'HTMLWP_Plugin::setting_page', 'dashicons-html');
    }

    public static function setting_page(){
       include( plugin_dir_path( __FILE__ ) . 'template/page_upload.php');
    }


    public static function callback_for_setting_up_scripts() {
        $screen = get_current_screen();
        if(isset($_GET['page'])):
             $page=sanitize_text_field($_GET['page']);
            if ( str_contains(strtolower($page), 'htmlwp') )
           {
            $plugin_url = plugin_dir_url( __FILE__ );
            wp_enqueue_style( 'bootstrapcss', plugins_url( 'template/css/bootstrap.min.css' , __FILE__ ) );
            wp_enqueue_style( 'admin', plugins_url( 'template/css/admin.css' , __FILE__ ) );
           }
        endif;
        
    }
   public static function assetExists($url) {
    //echo $url;
    if (($url == '') || ($url == null)) { return false; }
    $response = wp_remote_head( $url, array( 'timeout' => 65 ) );
    //   print_r($response); echo "<br>";
    $accepted_status_codes = array( 200, 301, 302 );
    if ( ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), $accepted_status_codes ) ) {
        return true;
    }
    return false;
    }
    public static function htmlwp_upload_file(){
        if ( isset( $_POST['action'] )
         && isset( $_POST['nonce'] )
         && 'htmlwp_upload_file' === $_POST['action']
         && wp_verify_nonce( $_POST['nonce'], 'htmlwp_upload_file_action' ) ) {

        if(isset($_POST['theme-name'],$_FILES['file'],$_FILES['file_screenshot']))
        {
            //echo $_FILES['file']['size'];
            //print_r($_FILES['file']);
            $maxsize=wp_max_upload_size();
            $acceptable_zip = array(
        "application/x-rar-compressed", "application/zip", "application/x-zip", "application/octet-stream", "application/x-zip-compressed"
    );
            $acceptable = array(   
        'image/jpeg',
        'image/jpg',
        'image/png'
    );
            if(($_FILES['file']['size'] >= $maxsize) || ($_FILES["file"]["size"] == 0)) {
      //  $errors = 'File too large. File must be less than 2 megabytes.';
        $error=true;
    $error_msg='Zip File too large. File must be less than '.esc_html( size_format( $maxsize ) );
     echo json_encode(array('error'=>esc_html($error),'message'=>esc_html($error_msg)));  
          exit;
    }
    elseif(!in_array($_FILES['file']['type'], $acceptable_zip) && (!empty($_FILES["file"]["type"]))) {
    // $errors[] = 'Invalid file type. Only PDF, JPG, GIF and PNG types are accepted.';
         $error=true;
    $error_msg='Invalid file type. Only Zip file accepted';
     echo json_encode(array('error'=>esc_html($error),'message'=>esc_html($error_msg)));  
          exit;
}
  elseif(!in_array($_FILES['file_screenshot']['type'], $acceptable) && (!empty($_FILES["file_screenshot"]["type"]))) {
    // $errors[] = 'Invalid file type. Only PDF, JPG, GIF and PNG types are accepted.';
         $error=true;
    $error_msg='Invalid file type. Only JPG and PNG types are accepted.';
     echo json_encode(array('error'=>esc_html($error),'message'=>esc_html($error_msg)));  
          exit;
}
    elseif(($_FILES['file_screenshot']['size'] >= $maxsize) || ($_FILES["file_screenshot"]["size"] == 0)) {
      //  $errors = 'File too large. File must be less than 2 megabytes.';
        $error=true;
    $error_msg='Screenshot File too large. File must be less than '.esc_html( size_format( $maxsize ) );
     echo json_encode(array('error'=>esc_html($error),'message'=>esc_html($error_msg)));  
          exit;
    }
    else{
      @session_start();
      $template=sanitize_text_field($_POST['theme-name']);
      // Check Template Exist Or Not 
      $error=false;
      $progress=true;
      $next=true;
      $success=false;
      $arr_html_name=array();
      $header_html=array();
      $footer_html=array();
      $blog=array();


      $template_slug = strtolower(str_replace(' ', '', $template));
     
        function rrmdir($dir) {
        if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
          if ($object != "." && $object != "..") {
            if (filetype($dir."/".$object) == "dir") 
               rrmdir($dir."/".$object); 
            else unlink   ($dir."/".$object);
          }
        }
        reset($objects);
        rmdir($dir);
        }
        }
      // Oeration Start
      // 1st - Template
      $progress_msg='Checking Template';
      $theme_base = dirname(dirname(dirname(__FILE__))).'/themes/';
      if($next==true)
      {
        if(is_dir($theme_base.$template_slug))
        {
          $error=true;
          $error_msg='Theme Already Exists';
        }
        else
        {
          // Create Theme
          $progress_msg='Creating Theme Folder';
          $progress=true;
          $error=false;      
          $progress_msg='Created Theme Folder';      
          $next=true;
          // Upload File
          $uploadedfile = $_FILES['file'];
          $upload_name = sanitize_file_name($_FILES['file']['name']);
          $uploads = dirname(__FILE__).'/OriginalHTMLFiles';
          $filepath = $uploads."/$upload_name";
          // sanitizing file name
          if($upload_name)
          {
          // delete folders first
          if ($dh = opendir($uploads)) {
             while (($file = readdir($dh)) !== false) {
              unlink($uploads.'/'.$file);
             }
            }
          rrmdir(dirname(__FILE__).'/OriginalHTMLFiles'.'/unzip');
        
          move_uploaded_file($_FILES['file']['tmp_name'], $filepath);
          
          mkdir($uploads.'/unzip/'.$template_slug, 0777, true);
          // Unzip
          WP_Filesystem();
          $result = unzip_file( $filepath, $uploads.'/unzip/'.$template_slug );
          $check_html_count=0;
          $check_folder_count=0;

           if (is_dir($uploads.'/unzip/'.$template_slug)) {
            if ($dh = opendir($uploads.'/unzip/'.$template_slug)) {
                while (($file = readdir($dh)) !== false) {
               $file_pth=$uploads.'/unzip/'.$template_slug.'/'.$file;
                  if(is_dir($file_pth) && $file != "." && $file != "..")
                   {
                          $file_pth=$uploads.'/unzip/'.$template_slug.'/'.$file; 
                       $file_pth_new=$uploads.'/unzip/'.$template_slug.'/'.$file;
                    $check_folder_count++;
                         if ($dh1 = opendir($file_pth)) {

                    while (($folder = readdir($dh1)) !== false) {
                    
                     
                         // check any html found or not 
                      if(!is_dir($file_pth.'/'.$folder) && (pathinfo($folder)['extension']=='html') && $folder != "." && $folder != "..")
                       {
                       
                        $arr_html_name[]=$folder;
                        $check_html_count++;
                       }
                       
                     }
                   }
                   closedir($dh1);
                   }
                 }
               }
               closedir($dh);
             }
            
             if($check_folder_count==1)
             {
               $next_again=true;
             }
             else
             {
               $error=true;
            $error_msg='Not Valid Folder.Check Folder and upload again.';
             }
         
          // check any html found or not
          if(($check_html_count>0) && isset($file_pth_new))
          {
            //check header & footer
             $file_pth_html_wh=$file_pth_new.'/'.$arr_html_name[0]; 
             $xml_wh = new DOMDocument(); 
             $xml_wh->loadHTMLFile($file_pth_html_wh); 
             $html_full_wh=$xml_wh->saveHtml();
             preg_match("~<body.*?>(.*?)<\/body>~is", $html, $match);
            //  echo $html_full_wh;
             if((preg_match('/<header(.*?)<\/header>/s', $html_full_wh) ) && (preg_match('/<footer(.*?)<\/footer>/s', $html_full_wh)) )
             {
                if($next_again==true)
              {
               mkdir($theme_base.$template_slug, 0777, true);
               move_uploaded_file($_FILES['file_screenshot']['tmp_name'], $theme_base.$template_slug.'/'.'screenshot.png');



            if (is_dir($uploads.'/unzip/'.$template_slug)) {
                if ($dh = opendir($uploads.'/unzip/'.$template_slug)) {
                    while (($file = readdir($dh)) !== false) {
                      $file_pth=$uploads.'/unzip/'.$template_slug.'/'.$file;
                      if(is_dir($file_pth) && $file != "." && $file != "..")
                       {
                      
                          if ($dh1 = opendir($file_pth)) {

                    while (($folder = readdir($dh1)) !== false) {
                    
                      if(is_dir($file_pth.'/'.$folder) && $folder != "." && $folder != "..")
                       {
                        
                        // Create dir in themes
                        mkdir($theme_base.$template_slug.'/'.$folder, 0777, true);
                        $dest=$theme_base.$template_slug.'/'.$folder;
                        $src = $file_pth.'/'.$folder;
                          if ($dh2 = opendir($src)) {
                    while (($file_of_folder1 = readdir($dh2)) !== false) {
                    
                      if($file_of_folder1 != "." && $file_of_folder1 != "..")
                       {
                          $src2 = $file_pth.'/'.$folder.'/'.$file_of_folder1; 
                          $dest2= $dest.'/'.$file_of_folder1; 
                          copy($src2, $dest2); 
                       }
                       if(is_dir($file_of_folder1) && $file_of_folder1 != "." && $file_of_folder1 != "..")
                       {
                         $src2 = $file_pth.'/'.$folder.'/'.$file_of_folder1; 
                         $dest2= $dest.'/'.$file_of_folder1; 
                          if ($dh3 = opendir($src2)) {
                          while (($file_of_folder2 = readdir($dh3)) !== false) {
                             if($file_of_folder2 != "." && $file_of_folder2 != "..")
                       {
                           $src3 = $src2.'/'.$file_of_folder2; 
                           mkdir($dest2, 0777, true);
                           $dest3= $dest2.'/'.$file_of_folder2; 
                           copy($src3, $dest3); 
                           $next=true; 
                       }  
                            
                          }
                          closedir($dh3);
                      }
                       }

                     
                    }
                    closedir($dh2);
                }
                        
                       

                       }
                      
                     
                       }
                    }
                    closedir($dh1);
                }
                       }

                    closedir($dh);
               
                }
             }

            }
            // get html files 
            $html_c=1;
            mkdir($theme_base.$template_slug.'/'.'templates', 0777, true);
            foreach ($arr_html_name as $key => $arr_html_name_val) {
               $arr_html_name_val; 
               $file_pth_html=$file_pth_new.'/'.$arr_html_name_val; 
               $xml = new DOMDocument(); 
               $xml->loadHTMLFile($file_pth_html); 
               $theme_uri=get_theme_root_uri().'/'.$template_slug.'/';
               foreach($xml->getElementsByTagName('link') as $link) { 
                 $oldLink = $link->getAttribute("href");
                 if (filter_var($oldLink, FILTER_VALIDATE_URL) === FALSE) {
                      $filtered_link=esc_url($theme_uri.$oldLink);
                      if (self::assetExists($filtered_link)) { 
                            $link->setAttribute('href', "<?php echo esc_url(get_template_directory_uri().\"/".$oldLink."\"); ?>");
                        }                    
                  }
                  else
                  {
                     $link->setAttribute('href', "<?php echo esc_url(\"".$oldLink."\"); ?>");
                  }
                 
               }
               foreach($xml->getElementsByTagName('script') as $src) { 
                if($src->hasAttribute("src"))
                {
                 $oldLinksrc = $src->getAttribute("src");
                 if (filter_var($oldLinksrc, FILTER_VALIDATE_URL) === FALSE) {
                     $filtered_linksrc=esc_url($theme_uri.$oldLinksrc);
                      if (self::assetExists($filtered_linksrc)) { 
                             $src->setAttribute('src', "<?php echo esc_url(get_template_directory_uri().\"/".$oldLinksrc."\"); ?>");
                        }  
                
                 }
                 else
                  {
                     $src->setAttribute('src', "<?php echo esc_url(\"".$oldLinksrc."\"); ?>");
                  }
               }
               }
               foreach($xml->getElementsByTagName('img') as $imgsrc) { 
                if($imgsrc->hasAttribute("src"))
                {
                 $oldLinkimgsrc = $imgsrc->getAttribute("src");
                 if (filter_var($oldLinkimgsrc, FILTER_VALIDATE_URL) === FALSE) {
                    $filtered_linkimgsrc=esc_url($theme_uri.$oldLinkimgsrc);
                     if (self::assetExists($filtered_linkimgsrc)) { 
                            $imgsrc->setAttribute('src', "<?php echo esc_url(get_template_directory_uri().\"/".$oldLinkimgsrc."\"); ?>");
                        } 
                  
                 }
                 else
                  {

                     $imgsrc->setAttribute('src', "<?php echo esc_url(\"".$oldLinkimgsrc."\"); ?>");
                  }
               }
               }
               $html_full=$xml->saveHtml();

               
              $html_full=str_replace("</head>","<?php wp_head(); ?> </head>",$html_full);
              $html_full=str_replace("</body>","<?php wp_footer(); ?> </body>",$html_full);



               preg_match('/<!DOCTYPE html>(.*?)<\/header>/s', $html_full, $header);
               preg_match('/<footer(.*?)<\/html>/s', $html_full, $footer);
               

               if($html_c==1)
               {
                $header_html[]=esc_html($header[0]);
                $footer_html[]=esc_html($footer[0]);
               }
               $body=preg_replace('/<footer(.*?)<\/html>/s', '', preg_replace('/<!DOCTYPE html>(.*?)<\/header>/s', '', $html_full));
              
               
               // create templates
               $dest_tpl=$theme_base.$template_slug.'/'.'templates/tpl-'.sanitize_file_name(basename($arr_html_name_val,".html").'.php');
               
               $fp=fopen($dest_tpl,'w');
               $tpl_name=ucfirst(str_replace('-',' ',basename($arr_html_name_val,".html")));

              



               $tpl_data='<?php /* Template Name: Template '.$tpl_name.' 

             
               */ 
               get_header(); ?> '.$body."
               <?php
              // get_sidebar();
               get_footer(); 
               ?>
               ";
               fwrite($fp, urldecode(htmlspecialchars_decode($tpl_data)));
               fclose($fp);

               // create pages and assign templates
              
               $new_page = array(
                    'post_type'     => 'page',        // Post Type Slug eg: 'page', 'post'
                    'post_title'    => $tpl_name, // Title of the Content
                    'post_content'  => '', // Content
                    'post_status'   => 'publish',     // Post Status
                    'post_author'   => 1,         // Post Author ID
                );

                if (get_page_by_title( $tpl_name ) == null) { // Check If Page Not Exits
                    $new_page_id = wp_insert_post($new_page);
                    update_post_meta( $new_page_id, '_wp_page_template', 'templates/tpl-'.basename($arr_html_name_val,".html").'.php' );
                    update_post_meta( $new_page_id, '_html_tpl', $arr_html_name_val );
                }
               $html_c++;
 
              
            }
            $dest_head=$theme_base.$template_slug.'/'.'header.php';
            //  header create
            $fp=fopen($dest_head,'w');
            fwrite($fp, urldecode(htmlspecialchars_decode($header_html[0])));
            fclose($fp);
            $dest_foot=$theme_base.$template_slug.'/'.'footer.php';
            //  footer create
            $fp=fopen($dest_foot,'w');
            fwrite($fp, urldecode(htmlspecialchars_decode($footer_html[0])));
            fclose($fp);
            $dest_style=$theme_base.$template_slug.'/'.'style.css';
            
            global $hd;
            global $ft;
            global $hd_dest;
            global $foot_dest;
            global $theme_name;
            $hd=urldecode(htmlspecialchars_decode($header_html[0]));
            $ft=urldecode(htmlspecialchars_decode($footer_html[0]));
            $hd_dest=$dest_head;
            $foot_dest=$dest_foot;
            $theme_name=$template_slug;


            // style create
            $fp=fopen($dest_style,'w');
            $style_data="/*
            Theme Name: ".$template."
            Author: HTML WP
            Description: Theme ".$template." created by HTML WP.you can change this after theme creation.
            Version: 1.0
            */";
            fwrite($fp, urldecode(htmlspecialchars_decode($style_data)));
            fclose($fp);

            // template-part folder
            mkdir($theme_base.$template_slug.'/'.'template-parts', 0777, true);
            $dest_content=$theme_base.$template_slug.'/'.'template-parts/content.php';
             // content create
            $fp=fopen($dest_content,'w');
            $content_data='<?php
                        /**
                         * Template part for displaying posts
                         */

                        ?>

                        <article id="post-<?php the_ID(); ?>" class="entry">
                          <header class="entry-header">
                            <h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
                          </header>

                          <div class="entry-content">
                            <?php the_content(); ?>
                          </div>
                        </article>';
            fwrite($fp, urldecode(htmlspecialchars_decode($content_data)));
            fclose($fp);

              $dest_index=$theme_base.$template_slug.'/'.'index.php';
               // index create
              $fp=fopen($dest_index,'w');
              
               $index_data='<?php
              /**
               * The main template file
               */

              get_header();
              ?>

              <main id="main" class="site-main" role="main">

              <?php
              if ( have_posts() ) : while ( have_posts() ) : the_post();

                  get_template_part( "template-parts/content", get_post_type() );

                endwhile;

                the_posts_pagination( array(
                  "prev_text" => __( "Previous page" ),
                  "next_text" => __( "Next page" ),
                ) );

              endif;
              ?>

              </main>

              <?php
             // get_sidebar();
              get_footer();';
            
             
              fwrite($fp, urldecode(htmlspecialchars_decode($index_data)));
              fclose($fp);

              $dest_404=$theme_base.$template_slug.'/'.'404.php';
             // 404 create
            $fp=fopen($dest_404,'w');
            $data404='<?php
              /**
               * The template for displaying 404 pages
               *
               */

              get_header();
              ?>

              <main id="main" class="site-main" role="main">

                <section class="page-section">
                  <header class="page-header">
                    <h1>404</h1>
                  </header>

                  <div class="page-content">
                    <p>Page not found.</p>
                  </div>
                </section>

              </main>

              <?php
              get_footer();';
              fwrite($fp, urldecode(htmlspecialchars_decode($data404)));
              fclose($fp);

              $dest_comments=$theme_base.$template_slug.'/'.'comments.php';
             // comments create
            $fp=fopen($dest_comments,'w');
            $comments_data='<?php
            /**
             * The template for displaying comments
             * 
             */

            if ( post_password_required() ) {
              return;
            }
            ?>

            <div id="comments" class="comments-area">

              <?php
              if ( have_comments() ) : ?>
                <h2 class="comments-title">Comments</h2>

                <?php the_comments_navigation(); ?>

                <ul class="comment-list">
                  <?php
                  wp_list_comments( array(
                    \'short_ping\' => true,
                  ) );
                  ?>
                </ul>

                <?php
                the_comments_navigation();

                // If comments are closed and there are comments, let\'s leave a little note, shall we?
                if ( ! comments_open() ) : ?>
                  <p class="no-comments">Comments are closed</p>
                <?php
                endif;

              endif;

              comment_form();
              ?>

            </div>';
            fwrite($fp, urldecode(htmlspecialchars_decode($comments_data)));
            fclose($fp);


            $dest_page=$theme_base.$template_slug.'/'.'page.php';
             // page create
            $fp=fopen($dest_page,'w');
            $page_data='<?php
                /**
                 * The template for displaying all pages
                 *
                 */

                get_header();
                ?>

                <main id="main" class="site-main" role="main">

                  <?php
                  while ( have_posts() ) : the_post();

                    get_template_part( \'template-parts/content\', \'page\' );

                    // If page are open or we have at least one comment, load up the comment template.
                   

                  endwhile; 
                  ?>

                </main>

                <?php

                get_footer();';
            fwrite($fp, urldecode(htmlspecialchars_decode($page_data)));
            fclose($fp);

             $dest_functions=$theme_base.$template_slug.'/'.'functions.php';
             // functions create
            $fp=fopen($dest_functions,'w');
            $functions_data="<?php
                        /**
                         * Functions and definitions
                         *
                         */

                        /*
                         * Let WordPress manage the document title.
                         */
                        add_theme_support( 'title-tag' );

                        /*
                         * Enable support for Post Thumbnails on posts and pages.
                         */
                        add_theme_support( 'post-thumbnails' );

                        /*
                         * Switch default core markup for search form, comment form, and comments
                         * to output valid HTML5.
                         */
                        add_theme_support( 'html5', array(
                          'search-form',
                          'comment-form',
                          'comment-list',
                          'gallery',
                          'caption',
                        ) );

                        /** 
                         * Include primary navigation menu
                         */
                        function htmlwp_nav_init() {
                          register_nav_menus( array(
                            'menu-header' => 'Header Menu',
                            'menu-footer' => 'Footer Menu',
                          ) );
                        }
                        add_action( 'init', 'htmlwp_nav_init' );

                        /**
                         * Register widget area.
                         *
                         */
                        function htmlwp_widgets_init() {
                          register_sidebar( array(
                            'name'          => 'Sidebar',
                            'id'            => 'sidebar-1',
                            'description'   => 'Add widgets',
                            'before_widget' => '<section id=\"%1$s\" class=\"widget %2$s\">',
                            'after_widget'  => '</section>',
                            'before_title'  => '<h2 class=\"widget-title\">',
                            'after_title'   => '</h2>',
                          ) );
                        }
                        add_action( 'widgets_init', 'htmlwp_widgets_init' );

                        /**
                         * Enqueue scripts and styles.
                         */
                        function htmlwp_scripts() {
                          wp_enqueue_style( 'htmlwp-style', get_stylesheet_uri() );
                          
                        }
                        add_action( 'wp_enqueue_scripts', 'htmlwp_scripts' );

                        function htmlwp_create_post_custom_post() {
                          register_post_type('custom_post', 
                            array(
                            'labels' => array(
                              'name' => __('Custom Post', 'htmlwp'),
                            ),
                            'public'       => true,
                            'hierarchical' => true,
                            'supports'     => array(
                              'title',
                              'editor',
                              'excerpt',
                              'custom-fields',
                              'thumbnail',
                            ), 
                            'taxonomies'   => array(
                                'post_tag',
                                'category',
                            ) 
                          ));
                        }
                        add_action('init', 'htmlwp_create_post_custom_post'); // Add our work type";
            fwrite($fp, urldecode(htmlspecialchars_decode($functions_data)));
            fclose($fp);

            $dest_search=$theme_base.$template_slug.'/'.'search.php';
             // search create
            $fp=fopen($dest_search,'w');
            $search_data='<?php
            /**
             * The template for displaying search results pages
             *
             */

            get_header();
            ?>

            <main id="main" class="site-main" role="main">

              <?php 
              if ( have_posts() ) : ?>

                <header class="page-header">
                  <h1>Results: <?php echo get_search_query(); ?></h1>
                </header>

                <?php
                while ( have_posts() ) : the_post();

                  get_template_part( \'template-parts/content\', \'search\' );

                endwhile;
              
              else: ?>

                <p>Sorry, but nothing matched your search terms.</p>
              
              <?php
              endif;
              ?>

            </main>

            <?php

            get_footer();';
            fwrite($fp, urldecode(htmlspecialchars_decode($search_data)));
            fclose($fp);

            $dest_sidebar=$theme_base.$template_slug.'/'.'sidebar.php';
             // sidebar create
            $fp=fopen($dest_sidebar,'w');
            $sidebar_data='<?php
            /**
             * The sidebar containing the main widget area
             *
             */

            if ( ! is_active_sidebar( \'sidebar-1\' ) ) {
              return;
            }
            ?>

            <aside class="widget-area">
              <?php dynamic_sidebar( \'sidebar-1\' ); ?>
            </aside>';
            fwrite($fp, urldecode(htmlspecialchars_decode($sidebar_data)));
            fclose($fp);
           
            $dest_single=$theme_base.$template_slug.'/'.'single.php';
             // single create
            $fp=fopen($dest_single,'w');
            
               $single_data='<?php
            /**
             * The template for displaying all single posts
             *
             */

            get_header();
            ?>

            <main id="main" class="site-main" role="main">

              <?php
              while ( have_posts() ) : the_post();

                get_template_part( \'template-parts/content\', get_post_type() );

                // If single are open or we have at least one comment, load up the comment template.
                if ( comments_open() || get_comments_number() ) :
                  comments_template();
                endif;

              endwhile;
              ?>

            </main>

            <?php

            get_footer();';
            
           
            fwrite($fp, urldecode(htmlspecialchars_decode($single_data)));
            fclose($fp);

            
            do_action('htmlwpaddonfunc');

            $success=true;
            $success_msg='Theme Successfully created';


            // print_r($header_html[0]);
            // print_r($footer_html[0]);
             }
             else
             {
              $error=true;
              $error_msg='you have no header and footer tag in your html file.Html Should have header in header tag and footer in footer tag';
             }

          }
          else
          {
            $error=true;
            $error_msg='Folder not have any Html file';
          }
  
      }
        } // end else theme exists
      }


      if($error==true)
      {
          echo json_encode(array('error'=>esc_html($error),'message'=>esc_html($error_msg)));  
          exit;
      }
      if($success==true)
      {
        unlink($filepath);
        rrmdir(dirname(__FILE__).'/OriginalHTMLFiles'.'/unzip/'.$template_slug);
        echo json_encode(array('success'=>esc_html($success),'message'=>esc_html($success_msg)));  
      }

   
    wp_die();
    } // check file size
   }
   else
   {
    $error=true;
    $error_msg='You have to fill all data';
     echo json_encode(array('error'=>esc_html($error),'message'=>esc_html($error_msg)));  
          exit;
   }
  }

}
}

add_action( 'plugins_loaded', array( 'HTMLWP_Plugin', 'init_actions' ) );
register_activation_hook(__FILE__, 'HTMLWP_Plugin::activate' );
register_deactivation_hook(__FILE__, 'HTMLWP_Plugin::deactivate');

endif;



?>