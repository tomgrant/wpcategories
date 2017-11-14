<?php
/*
Plugin Name: Wp Categories
Description: Plugin exercise for https://ffxblue.github.io/interview-tests/test/wordpress-categories/
Version: 0.1
Author: Tom Grant
*/

defined( 'ABSPATH' ) or die( 'No script' );

Class WpCategories{

    public function __construct(){

        add_action( 'wp_ajax_handle_external_categories', array( $this, 'handle_external_categories') );
        add_action( 'wp_ajax_nopriv_handle_external_categories', array( $this, 'handle_external_categories') );

        add_filter('cron_schedules', array( $this, 'add_cron_schedules') );

        add_action( 'schedule_external_categories', array( $this, 'handle_external_categories') );

        add_filter( 'admin_init' , array( &$this , 'register_fields' ) );

        //Schedule Cron Job
        if ( ! wp_next_scheduled( 'schedule_external_categories') ) {
            wp_schedule_event( time(), '30min', 'schedule_external_categories' );
        }

    }    

    public function register_fields() {
        register_setting( 'general', 'update_now', 'esc_attr' );
        add_settings_field('update_now', '<label for="update_external_categories">'.__('Update categories' , 'update_now' ).'</label>' , array($this, 'fields_html') , 'general' );
    }
    public function fields_html() {
        ?>
        <input type="button" id="update_external_categories" name="update_external_categories" value="Update categories now" />
        <script>
            jQuery(function($) {
                $(document).ready(function () {
                    $('body').on('click', '#update_external_categories', function () {
                        $.ajax({
                            url: ajaxurl,
                            type: 'post',
                            dataType: 'json',
                            data: {
                                action: 'handle_external_categories'
                            },
                            beforeSend: function() {
                                $('#update_external_categories').attr('value', 'Loading...');
                            },
                            success: function (res) {
                                $('#update_external_categories').attr('value', 'Update categories now');
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                console.log(textStatus, errorThrown);
                            }
                        });
                    });

              
                });
            });
        </script>
        <?php
    }
    
    //Create a 30 minute Cron schedule
    public function add_cron_schedules($schedules){
        if(!isset($schedules["30min"])){
            $schedules["30min"] = array(
                'interval' => 30*60,
                'display' => __('Once every 30 minutes'));
        }
        return $schedules;
    }    

    public function handle_external_categories(){
        
        $url = "https://tomgrant.me/projects/wpcategories/db.json";        
        $response = file_get_contents($url);
        $categories = json_decode($response);

        $foreign_ids = [];

        //Loop through json resonse and handle lines
        foreach($categories->categories as $category){
            
            $term_id = $this->get_id_by_foreign_id( $category->id );

            if($term_id){          
                $this->handle_exisiting_category($term_id, $category);
            }else{
                $this->handle_new_category($category);
            }
            
            //Add to found ids for comparrison against exisiting category ID's
            array_push($foreign_ids, $category->id);
        }
        
        $this->delete_external_categories($foreign_ids);

        die();
    }

    private function get_id_by_foreign_id( $foreign_id ){
        global $wpdb;

        if($foreign_id != null){
            $sql = "SELECT `term_id` FROM `" . $wpdb->prefix . "termmeta`
            WHERE `meta_key` = '_foreign_id'
            AND `meta_value` = %d";
            $result = $wpdb->get_results($wpdb->prepare( $sql, $foreign_id) );

            return $result[0]->term_id;
        }
        return false;
    }

    //Category id already exisits in term meta, write any changes to exisiting external categories
    private function handle_exisiting_category($term_id, $category){

        $args = array(
            'name' => $category->name,                
        );
        $foreign_parent_id = $category->parent_id;

        if($foreign_parent_id){
            $parent_id = $this->get_id_by_foreign_id($foreign_parent_id);
            $args['parent'] = (int)$parent_id;                
        }else{
            $args['parent'] = 0;
        }
        wp_update_term($term_id, 'category', $args);
        update_term_meta($term_id, '_foreign_parent_id', $foreign_parent_id);

    }

    //Category ID doesn't exisi in term meta
    private function handle_new_category($category){
        $foreign_parent_id = $category->parent_id;
        $term_id = wp_create_category( $category->name );
        update_term_meta($term_id, '_foreign_id', $category->id);

        //Assign parent ID to New Category
        if($foreign_parent_id){
            $parent_id = $this->get_id_by_foreign_id( $foreign_parent_id );
            $args['parent'] = (int)$parent_id;  
            wp_update_term($term_id, 'category', $args);
            update_term_meta($term_id, '_foreign_parent_id', $foreign_parent_id);
        }
    }

    //Check for any ID's that ahve been removed from the JSON response and delete them
    private function delete_external_categories($foreign_ids){
        global $wpdb;
        $sql = "SELECT `meta_value` FROM `" . $wpdb->prefix . "termmeta`
        WHERE `meta_key` = '_foreign_id'
        GROUP BY `meta_value`";
        $results = $wpdb->get_results( $sql );
        $results = json_decode(json_encode($results), True);
        foreach($results as $result){
            if(!in_array((int)$result['meta_value'], $foreign_ids)){
                $term_id = $this->get_id_by_foreign_id((int)$result['meta_value']);
                wp_delete_category( $term_id );
            }
        }
    }

}

new WpCategories;

//wp_create_category( $cat_name, $parent );
//https://codex.wordpress.org/Function_Reference/wp_create_category
