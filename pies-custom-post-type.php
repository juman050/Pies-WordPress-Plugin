<?php
/**
 * Plugin Name: Pies Custom Post Type
 * Description: A custom post type plugin for managing pies.
 * Version: 1.2
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Main class for the plugin
class Pies_Custom_Post_Type {
    
    public function __construct() {
        // Hook into the init action to register the custom post type
        add_action('init', array($this, 'register_pies_post_type'));
        // Hook for inserting initial pies on plugin activation
        //add_action('init', array($this, 'insert_initial_pies'));
        
        // Hook into the admin menu to add a submenu for pie management
        add_action('admin_menu', array($this, 'add_pies_submenu'));
        
        // Add meta boxes for pie type, description, and ingredients
        add_action('add_meta_boxes', array($this, 'add_pies_meta_boxes'));
        
        // Hook into the save post action to handle saving custom fields
        add_action('save_post', array($this, 'save_pie_meta'));
        
        // Hook into the pre_get_posts action to enable searching by custom fields
        add_action('pre_get_posts', array($this, 'search_pies_by_meta'));
        
        // Enqueue scripts and styles for the admin interface
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Hook for the shortcode
        add_shortcode('pies', array($this, 'display_pies_shortcode'));
    }
    
    // Register the custom post type "pies"
    public function register_pies_post_type() {
        $labels = array(
            'name' => 'Pies',
            'singular_name' => 'Pie',
            'menu_name' => 'Pies',
            'name_admin_bar' => 'Pie',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Pie',
            'new_item' => 'New Pie',
            'edit_item' => 'Edit Pie',
            'view_item' => 'View Pie',
            'all_items' => 'All Pies',
            'search_items' => 'Search Pies',
            'not_found' => 'No pies found.',
            'not_found_in_trash' => 'No pies found in Trash.',
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'pies'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor'),
        );
        
        register_post_type('pies', $args);
    }
    
    // Add submenu for managing pies
    public function add_pies_submenu() {
        add_submenu_page(
            'edit.php?post_type=pies',  // Parent slug
            'Manage Pies',              // Page title
            'Manage Pies',              // Menu title
            'manage_options',           // Capability
            'manage_pies',              // Menu slug
            array($this, 'render_pies_page')  // Callback function
        );
    }
    
    // Add meta boxes for pie type, description, and ingredients
    public function add_pies_meta_boxes() {
        add_meta_box(
            'pie_details_meta_box',     // ID
            'Pie Details',              // Title
            array($this, 'render_pie_meta_box'), // Callback
            'pies',                     // Post type
            'normal',                   // Context
            'high'                      // Priority
        );
    }

    // Render the pie meta box (for pie type, description, and ingredients)
    public function render_pie_meta_box($post) {
        // Nonce field for security
        wp_nonce_field('save_pie_meta', 'pies_nonce');

        // Retrieve current values for pie type, description, and ingredients
        $pie_type = get_post_meta($post->ID, '_pie_type', true);
        $description = get_post_meta($post->ID, '_description', true);
        $ingredients = get_post_meta($post->ID, '_ingredients', true);

        // Display fields for pie type, description, and ingredients
        echo '<label for="pie_type">Pie Type:</label>';
        echo '<input type="text" id="pie_type" name="pie_type" value="' . esc_attr($pie_type) . '" size="25" />';
        
        echo '<br><br><label for="description">Description:</label>';
        echo '<textarea id="description" name="description" rows="3" cols="50">' . esc_textarea($description) . '</textarea>';
        
        echo '<br><br><label for="ingredients">Ingredients:</label>';
        echo '<textarea id="ingredients" name="ingredients" rows="5" cols="50">' . esc_textarea($ingredients) . '</textarea>';
    }
    
    // Save custom meta fields for pies
    public function save_pie_meta($post_id) {
        // Verify the nonce before proceeding
        if (!isset($_POST['pies_nonce']) || !wp_verify_nonce($_POST['pies_nonce'], 'save_pie_meta')) {
            return;
        }

        // Verify if this is an auto save routine. If it is, return.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions.
        if (isset($_POST['post_type']) && 'pies' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Save custom fields (e.g., pie type, description, ingredients) here
        if (isset($_POST['pie_type'])) {
            update_post_meta($post_id, '_pie_type', sanitize_text_field($_POST['pie_type']));
        }
        if (isset($_POST['description'])) {
            update_post_meta($post_id, '_description', sanitize_textarea_field($_POST['description']));
        }
        if (isset($_POST['ingredients'])) {
            update_post_meta($post_id, '_ingredients', sanitize_textarea_field($_POST['ingredients']));
        }
    }
    
    // Search pies by custom meta fields
    public function search_pies_by_meta($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'pies') {
            return;
        }
        
        if (!empty($_GET['s'])) {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_pie_type',
                    'value' => sanitize_text_field($_GET['s']),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_description',
                    'value' => sanitize_text_field($_GET['s']),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_ingredients',
                    'value' => sanitize_text_field($_GET['s']),
                    'compare' => 'LIKE'
                )
            );
            $query->set('meta_query', $meta_query);
        }
    }
    
    // Shortcode function to display pies
    public function display_pies_shortcode($atts) {
        $atts = shortcode_atts(array(
            'lookup' => '',
            'ingredients' => '',
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1
        ), $atts, 'pies');
        
        $args = array(
            'post_type' => 'pies',
            'posts_per_page' => 5, // Adjust as needed
            'paged' => $atts['paged'],
        );
        
        if (!empty($atts['lookup'])) {
            $args['meta_query'][] = array(
                'key' => '_pie_type',
                'value' => $atts['lookup'],
                'compare' => 'LIKE'
            );
        }
        
        if (!empty($atts['ingredients'])) {
            $args['meta_query'][] = array(
                'key' => '_ingredients',
                'value' => $atts['ingredients'],
                'compare' => 'LIKE'
            );
        }
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $output = '<div class="pies-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="pie-item">';
                $output .= '<h2>' . get_the_title() . '</h2>';
                $output .= '<p><strong>Description:</strong> ' . esc_html(get_post_meta(get_the_ID(), '_description', true)) . '</p>';
                $output .= '<p><strong>Type:</strong> ' . esc_html(get_post_meta(get_the_ID(), '_pie_type', true)) . '</p>';
                $output .= '<p><strong>Ingredients:</strong> ' . esc_html(get_post_meta(get_the_ID(), '_ingredients', true)) . '</p>';
                $output .= '</div>';
            }
            $output .= '</div>';
            
            // Pagination
            $output .= '<div class="pagination">';
            $output .= paginate_links(array(
                'total' => $query->max_num_pages
            ));
            $output .= '</div>';
        } else {
            $output = '<p>No pies found.</p>';
        }
        
        wp_reset_postdata();
        
        return $output;
    }
    // Enqueue admin scripts and styles
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'pies_page_manage_pies') {
            return;
        }
        // Enqueue custom styles and scripts here
    }
    // Function to insert initial pies into the database
    public function insert_initial_pies() {
        $pies = array(
            array(
                'title' => 'Apple Pie',
                'type' => 'Fruit Pie',
                'description' => 'A classic dessert made with a flaky crust filled with sweet, spiced apples.',
                'ingredients' => 'Apples, Sugar, Cinnamon, Nutmeg, Butter, Flour, Lemon Juice'
            ),
            array(
                'title' => 'Pumpkin Pie',
                'type' => 'Custard Pie',
                'description' => 'A smooth and creamy pie made with spiced pumpkin filling, perfect for autumn.',
                'ingredients' => 'Pumpkin Puree, Eggs, Sugar, Cinnamon, Ginger, Nutmeg, Evaporated Milk, Pie Crust'
            ),
            array(
                'title' => 'Cherry Pie',
                'type' => 'Fruit Pie',
                'description' => 'A delicious pie filled with tart cherries and sweetened with sugar, encased in a golden crust.',
                'ingredients' => 'Cherries, Sugar, Cornstarch, Lemon Juice, Almond Extract, Butter, Pie Crust'
            ),
            array(
                'title' => 'Pecan Pie',
                'type' => 'Nut Pie',
                'description' => 'A rich and buttery pie with a filling made of toasted pecans and a sweet, gooey custard.',
                'ingredients' => 'Pecans, Eggs, Corn Syrup, Sugar, Butter, Vanilla Extract, Pie Crust'
            ),
            array(
                'title' => 'Blueberry Pie',
                'type' => 'Fruit Pie',
                'description' => 'A juicy pie filled with fresh blueberries, sweetened and thickened to create a perfect summer treat.',
                'ingredients' => 'Blueberries, Sugar, Lemon Juice, Cornstarch, Butter, Pie Crust'
            ),
            array(
                'title' => 'Key Lime Pie',
                'type' => 'Citrus Pie',
                'description' => 'A tart and tangy pie made with key lime juice and a creamy filling, topped with whipped cream.',
                'ingredients' => 'Key Lime Juice, Sweetened Condensed Milk, Egg Yolks, Graham Cracker Crust, Whipped Cream'
            ),
            array(
                'title' => 'Banoffee Pie',
                'type' => 'Cream Pie',
                'description' => 'A rich and indulgent pie made with layers of bananas, toffee, and whipped cream on a biscuit base.',
                'ingredients' => 'Bananas, Toffee (Caramel), Whipped Cream, Digestive Biscuits, Butter, Chocolate Shavings'
            ),
            array(
                'title' => 'Sweet Potato Pie',
                'type' => 'Custard Pie',
                'description' => 'A Southern favorite, this pie is made with mashed sweet potatoes, spiced and baked to perfection.',
                'ingredients' => 'Sweet Potatoes, Eggs, Sugar, Butter, Cinnamon, Nutmeg, Evaporated Milk, Pie Crust'
            ),
            array(
                'title' => 'Lemon Meringue Pie',
                'type' => 'Citrus Pie',
                'description' => 'A zesty lemon filling topped with a fluffy, toasted meringue on a crisp pie crust.',
                'ingredients' => 'Lemon Juice, Lemon Zest, Sugar, Egg Yolks, Cornstarch, Butter, Meringue, Pie Crust'
            ),
            array(
                'title' => 'Mince Pie',
                'type' => 'Fruit & Spice Pie',
                'description' => 'A traditional British pie filled with a mixture of dried fruits, spices, and brandy, often enjoyed during the holidays.',
                'ingredients' => 'Mince Meat (Dried Fruits, Spices, Suet), Brandy, Sugar, Pie Crust'
            ),
        );

        foreach ($pies as $pie) {
            // Insert the pie into the database
            $post_id = wp_insert_post(array(
                'post_title' => wp_strip_all_tags($pie['title']),
                'post_type' => 'pies',
                'post_status' => 'publish',
                'meta_input' => array(
                    '_pie_type' => $pie['type'],
                    '_description' => $pie['description'],
                    '_ingredients' => $pie['ingredients']
                ),
            ));
        }
    }
}

// Instantiate the plugin class
$pies_custom_post_type = new Pies_Custom_Post_Type();
?>

