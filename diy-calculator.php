<?php
/*
Plugin Name: DIY Calculator for Vaping
Description: A DIY calculator for vaping e-liquid made with Bootstrap and JQuery.
Version: 1.0
Author: Vaping Post
Text Domain: diy-calculator
Domain Path: /languages
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Empêche l'accès direct
}

// Journaliser la langue active
/*add_action('init', function() {
    //error_log('Langue active détectée : ' . get_locale());
});*/

// Charger les traductions
add_action('plugins_loaded', function() {
    $current_locale = apply_filters('wpml_current_language', null);
    
    // Si WPML est actif, charger les traductions pour la langue courante
    if ($current_locale && $current_locale !== get_locale()) {
        load_plugin_textdomain('diy-calculator', false, plugin_dir_path(__FILE__) . 'languages');
    }

    // Fallback pour les environnements sans WPML
    if (!$current_locale) {
        load_plugin_textdomain('diy-calculator', false, plugin_dir_path(__FILE__) . 'languages');
    }
});

// Journaliser le fichier .mo chargé
/*add_filter('load_textdomain_mofile', function($mofile, $domain) {
    if ($domain === 'diy-calculator') {
        //error_log("Chargement du fichier MO : $mofile");
    }
    return $mofile;
}, 10, 2);*/



// ADMIN SIDE
// Ajouter une page d'administration pour le plugin
add_action('admin_menu', 'diy_add_admin_menu');
function diy_add_admin_menu() {
    add_menu_page(
        'DIY Calculator settings', // Titre de la page
        'DIY Calculator',          // Nom dans le menu
        'manage_options',          // Capacité requise
        'diy-calculator-settings', // Slug de la page
        'diy_render_settings_page', // Fonction de callback pour afficher la page
        '', // Icône laissée vide, car on la personnalise dans le style
        99 // Position dans le menu
    );
}

add_action('admin_head', 'diy_custom_admin_menu_icon');
function diy_custom_admin_menu_icon() {
    $svg_icon_base64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents(plugin_dir_path(__FILE__) . 'img/diy-admin.svg'));
    echo '<style>
        /* Applique l’icône SVG encodée */
        #toplevel_page_diy-calculator-settings .wp-menu-image {
            background-image: url(' . $svg_icon_base64 . ') !important;
           width: 28px !important;
		  margin-left: 5px !important;
		  margin-right: 4px !important;
		  background-repeat: no-repeat !important;
		  position: absolute;
		  top: 9px;
        }

        /* Masque le pseudo-élément ::before généré par WordPress */
        #toplevel_page_diy-calculator-settings .wp-menu-image:before {
            content: none !important;
        }

        /* Optionnel : Masquer une image par défaut si elle est ajoutée */
        #toplevel_page_diy-calculator-settings .wp-menu-image img {
            display: none;
        }
    </style>';
}/////////
//Ressouces admin

add_action('admin_enqueue_scripts', 'diy_admin_enqueue_styles');
function diy_admin_enqueue_styles($hook) {
    // Vérifiez que nous sommes sur la page de votre plugin
    if ($hook !== 'toplevel_page_diy-calculator-settings') {
        return;
    }
	
    // Charger le CSS Bootstrap
    wp_enqueue_style(
        'bootstrap-admin-style',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        array(),
        '5.3.2'
    );
    // Custom css
    wp_enqueue_style(
        'diy-admin-custom-style', // Handle unique pour votre CSS
        plugin_dir_url(__FILE__) . 'admin/diy-admin.css',
        array(), // Dépendances (aucune ici)
        filemtime(plugin_dir_path(__FILE__) . 'admin/diy-admin.css') // Version basée sur l'horodatage
    );

    //admin.js
    wp_enqueue_script(
	    'diy-admin-script',
	    plugin_dir_url(__FILE__) . 'admin/diy-admin.js',
	    ['jquery'],
	    filemtime(plugin_dir_path(__FILE__) . 'admin/diy-admin.js'), // Version basée sur l'horodatage
	    true
	);
     
     // Passer les données PHP vers JavaScript
    wp_localize_script(
        'diy-admin-script',
        'diyAdminData',
        array(
            'defaultColors' => diy_get_default_colors(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            '_diy_calculator_nonce' => wp_create_nonce('diy_calculator_settings'),
			'errorEmptyName' => __('Theme name cannot be empty', 'diy-calculator'),
			'deleteConfirm' => __('Are you sure you want to delete this theme?', 'diy-calculator'),
        )
    );

    // Charger le JS Bootstrap (si nécessaire)
    wp_enqueue_script(
        'bootstrap-admin-script',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
        array('jquery'),
        '5.3.2',
        true // Charger dans le footer
    );
    
    ////////// Ressouces clients
    // Charger les styles du client (CSS côté public) pour le Live Preview
    wp_enqueue_style(
        'diy-calculator-style',
        plugin_dir_url(__FILE__) . 'diy-calculator.css',
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'diy-calculator.css')
    );

    // Charger le JS client (JS côté public) pour le Live Preview
    wp_enqueue_script(
        'diy-calculator-script',
        plugin_dir_url(__FILE__) . 'diy-calculator.js',
        ['jquery'],
        filemtime(plugin_dir_path(__FILE__) . 'diy-calculator.js'),
        true
    );
	// Associer les chaînes localisées Client
	wp_localize_script(
	    'diy-calculator-script',
	    'diyCalculatorStrings',
	    array(
	        'warningMessage' => __('Not enough space for flavors with current nicotine level.', 'diy-calculator'),
	        'bottlesLabel' => __('bottles', 'diy-calculator'),
	        'bottlesOfLabel' => __('bottles of 10ml each', 'diy-calculator'),
	    )
	);
    
    
}


add_action('admin_enqueue_scripts', 'diy_apply_active_theme_styles');
function diy_apply_active_theme_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_diy-calculator-settings') {
        $colors = get_option('diy_colors', diy_get_default_colors());
        $custom_css = "
             
             #nicotine-level-display.range-nicotine { background-color: {$colors['diy_color_nicotine']} !important; }
             #aroma-percent-display.range-aroma { background-color: {$colors['diy_color_aroma']} !important; }
            .legend-base, #base-amount .res-label, #base-visual { background-color: {$colors['diy_color_base']} !important; }
            .legend-nicotine, #booster-amount .res-label, #nicotine-visual { background-color: {$colors['diy_color_nicotine']} !important; }
            .legend-aroma, #aroma-amount .res-label, #aroma-visual { background-color: {$colors['diy_color_aroma']} !important; }
        ";
        wp_add_inline_style('diy-calculator-style', $custom_css);
    }
}



// Verification du none
function diy_verify_nonce() {
    if (!isset($_POST['_diy_calculator_nonce']) || !wp_verify_nonce($_POST['_diy_calculator_nonce'], 'diy_calculator_settings')) {
        wp_send_json_error(['message' => __('Nonce verification failed.', 'diy-calculator')]);
    }
}

// Enregistrer les options dans la base de données
add_action('admin_init', 'diy_register_settings');
function diy_register_settings() {
    register_setting('diy_calculator_settings', 'diy_colors');
    register_setting('diy_calculator_settings', 'diy_color_themes');
	
	// Initialiser le thème actif si l'option n'existe pas
    if (get_option('diy_active_theme') === false) {
        update_option('diy_active_theme', 'Default');
    }
    
    add_settings_section(
        'diy_colors_section', 
        'DIY Calculator Customizer', 
        null, 
        'diy-calculator-settings'
    );

	// Champs pour les couleurs
	$fields = [
	    'diy_color_base' => __('Base color', 'diy-calculator'),
	    'diy_color_nicotine' => __('Nicotine color', 'diy-calculator'),
	    'diy_color_aroma' => __('Flavors color', 'diy-calculator'),
	];

    foreach ($fields as $field_id => $label) {
        add_settings_field(
            $field_id, 
            $label, 
            'diy_color_field_callback', 
            'diy-calculator-settings', 
            'diy_colors_section', 
            array('label_for' => $field_id)
        );
    }
}

// Couleurs par défaut
function diy_get_default_colors() {
    return [
        'diy_color_base' => '#6e5dc6',
        'diy_color_nicotine' => '#aa1a8c',
        'diy_color_aroma' => '#bf0000',
    ];
}

// Vérifiez si l'utilisateur veut appliquer un thème existant
$apply_theme_name = sanitize_text_field($_POST['apply_theme_name'] ?? '');
if (!empty($apply_theme_name)) {
    $themes = get_option('diy_color_themes', []);

    // Assurez-vous que le thème Default est présent
    if (!isset($themes['Default'])) {
        $themes['Default'] = diy_get_default_colors();
        update_option('diy_color_themes', $themes);
    }

    // Validation du nom de thème
    if (isset($themes[$apply_theme_name])) {
        update_option('diy_colors', $themes[$apply_theme_name]); // Applique les couleurs du thème
        update_option('diy_active_theme', $apply_theme_name); // Définit le thème comme actif
    } else {
        ////error_log('Error: Theme not found - ' . $apply_theme_name); // Log pour diagnostic
        throw new Exception(__('The selected theme does not exist.', 'diy-calculator'));
    }
}



//THEME FORM ACTIONS
// Fonction pour sauvegarder les paramètres et, éventuellement, un thème
//add_action('admin_post_diy_save_settings', 'diy_save_settings_and_theme');


add_action('wp_ajax_diy_manage_themes', 'diy_manage_themes');
function diy_manage_themes() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized action.', 'diy-calculator')]);
    }

    diy_verify_nonce();

    try {
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');
        $themes = diy_ensure_default_theme_exists();
		////error_log('Raw $_POST[\'diy_colors\']: ' . print_r($_POST['diy_colors'], true));
		
		
        switch ($action_type) {
            case 'save_settings':
			    ////error_log('Processing save_settings action');
			    $colors = $_POST['diy_colors'] ?? [];
			    //error_log('Colors received: ' . print_r($colors, true));
			
			    // Décodage des couleurs si elles sont fournies en JSON
			    if (is_string($colors)) {
			        $colors = stripslashes($colors); // Supprime les antislashs
			        $colors = json_decode($colors, true);
			        if (json_last_error() !== JSON_ERROR_NONE) {
			            //error_log('JSON decode error: ' . json_last_error_msg());
			            throw new Exception(__('Invalid JSON data for colors.', 'diy-calculator'));
			        }
			    }
			
			    // Validation des couleurs
			    if (!is_array($colors)) {
			        throw new Exception(__('Invalid colors data.', 'diy-calculator'));
			    }
			    $colors = array_filter(array_map('sanitize_hex_color', $colors), function ($color) {
			        return $color !== null;
			    });
			    //error_log('Sanitized colors: ' . print_r($colors, true));
			
			    if (empty($colors)) {
			        throw new Exception(__('Invalid colors data.', 'diy-calculator'));
			    }
			
			    // Vérification si un nouveau thème est demandé
			    $theme_name = sanitize_text_field($_POST['new_theme_name'] ?? '');
			    //error_log('Theme name provided: ' . $theme_name);
			
			    if (!empty($theme_name)) {
				    // Récupérer tous les thèmes existants
				    $themes = get_option('diy_color_themes', []);
				
				    // Vérifier si le thème existe déjà pour éviter les doublons
				    if (array_key_exists($theme_name, $themes)) {
				        wp_send_json_error([
				            'message' => sprintf(__('Theme "%s" already exists. Please choose a different name.', 'diy-calculator'), $theme_name),
				        ]);
				    }
				
				    // Enregistrer le nouveau thème
				    $themes[$theme_name] = $colors;
				    update_option('diy_color_themes', $themes);
				
				    // Définir le nouveau thème comme actif
				    update_option('diy_colors', $colors);
				    update_option('diy_active_theme', $theme_name);
				
				    // Répondre avec un message de succès
				    wp_send_json_success([
				        'message' => sprintf(__('Theme "%s" saved and activated successfully.', 'diy-calculator'), $theme_name),
				    ]);
				}
			
			    // Appliquer les couleurs au thème actif si aucun nouveau thème n’est fourni
			    update_option('diy_colors', $colors);
			
			    // Synchroniser les couleurs avec le thème actif
			    $active_theme = get_option('diy_active_theme', 'Default');
			    $themes = get_option('diy_color_themes', []);
			
			    if (!empty($active_theme)) {
			        if (isset($themes[$active_theme])) {
			            $themes[$active_theme] = $colors; // Mise à jour des couleurs du thème actif
			        } else {
			            $themes[$active_theme] = $colors; // Ajouter le thème actif si absent
			        }
			
			        update_option('diy_color_themes', $themes);
			    }
			
			    //error_log('Colors applied successfully to active theme.');
			    wp_send_json_success([
			        'message' => __('Colors applied to the active theme successfully.', 'diy-calculator'),
			    ]);
			break;
    

            case 'apply_theme':
                $theme_name = sanitize_text_field($_POST['theme_name'] ?? '');
                if (isset($themes[$theme_name])) {
                    update_option('diy_colors', $themes[$theme_name]);
                    update_option('diy_active_theme', $theme_name);
                    wp_send_json_success(['message' => sprintf(__('Theme "%s" applied successfully.', 'diy-calculator'), $theme_name)]);
                }
                throw new Exception(__('Theme not found.', 'diy-calculator'));

            case 'delete_theme':
                $theme_name = sanitize_text_field($_POST['theme_name'] ?? '');
                if (isset($themes[$theme_name]) && $theme_name !== 'Default') {
                    unset($themes[$theme_name]);
                    update_option('diy_color_themes', $themes);
                    wp_send_json_success(['message' => sprintf(__('Theme "%s" deleted successfully.', 'diy-calculator'), $theme_name)]);
                }
                throw new Exception(__('Cannot delete the Default theme.', 'diy-calculator'));

            default:
                throw new Exception(__('Invalid action.', 'diy-calculator'));
        }

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}



// Fonction utilitaire pour garantir que le thème "Default" existe
function diy_ensure_default_theme_exists() {
    $themes = get_option('diy_color_themes', []);
    if (!isset($themes['Default'])) {
        $themes['Default'] = diy_get_default_colors();
        update_option('diy_color_themes', $themes);
    }
    return $themes;
}

// supression theme via Ajax
add_action('wp_ajax_diy_delete_theme', 'diy_delete_theme');
function diy_delete_theme() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'status' => 'danger',
            'message' => __('Unauthorized action.', 'diy-calculator')
        ]);
    }

    $theme_name = sanitize_text_field($_POST['theme_name']);
    if (empty($theme_name)) {
        wp_send_json_error([
            'status' => 'danger',
            'message' => __('Invalid theme name.', 'diy-calculator')
        ]);
    }

    $themes = get_option('diy_color_themes', []);
    if (is_array($themes) && isset($themes[$theme_name])) {
        unset($themes[$theme_name]);
        update_option('diy_color_themes', $themes);
        wp_send_json_success([
            'status' => 'success',
            'message' => __('Theme deleted successfully.', 'diy-calculator')
        ]);
    } else {
        wp_send_json_error([
            'status' => 'danger',
            'message' => __('Theme not found.', 'diy-calculator')
        ]);
    }
}


// Fonction pour afficher un champ de couleur avec la valeur hexadécimale
function diy_color_field_callback($args) {
    $options = get_option('diy_colors', diy_get_default_colors());
    $default_colors = diy_get_default_colors();
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $default_colors[$args['label_for']];
    ?>
    <div class="d-flex align-items-center gap-2">
        <input 
            type="color" 
            id="<?php echo esc_attr($args['label_for']); ?>" 
            name="diy_colors[<?php echo esc_attr($args['label_for']); ?>]" 
            value="<?php echo esc_attr($value); ?>" 
            class="form-control-color"
        >
        <input 
            type="text" 
            id="<?php echo esc_attr($args['label_for']); ?>-hex" 
            value="<?php echo esc_attr($value); ?>" 
            class="form-control form-control-sm color-hex-input"
        >
    </div>
    <?php
}

// Live preview

// Live preview
add_action('wp_ajax_diy_live_preview', 'diy_live_preview');
function diy_live_preview() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized action.', 'diy-calculator')]);
    }

    $colors = $_POST['diy_colors'] ?? [];
    if (!is_array($colors)) {
        wp_send_json_error(['message' => __('Invalid colors data.', 'diy-calculator')]);
    }

    // Récupérer les valeurs par défaut du shortcode
    $totalVolume = 10; // Volume par défaut défini dans le shortcode
    $nicotineLevel = 3; // Taux de nicotine par défaut
    $aromaPercent = 1.2; // Pourcentage d'arôme par défaut

    // Générer les styles dynamiques
   $dynamic_css = "
        #nicotine-level-display.range-nicotine { background-color: {$colors['diy_color_nicotine']} }
        #aroma-percent-display.range-aroma { background-color: {$colors['diy_color_aroma']} }
        .legend-base, #base-amount .res-label, #base-visual { background-color: {$colors['diy_color_base']} }
        .legend-nicotine, #booster-amount .res-label, #nicotine-visual { background-color: {$colors['diy_color_nicotine']} }
        .legend-aroma, #aroma-amount .res-label, #aroma-visual { background-color: {$colors['diy_color_aroma']}  }
    ";
	
    ob_start();
    echo do_shortcode('[diy_calculator]');
    $content = ob_get_clean();

    wp_send_json_success([
        'preview' => $content,
        'styles' => $dynamic_css,
        'defaultValues' => [
            'totalVolume' => $totalVolume,
            'nicotineLevel' => $nicotineLevel,
            'aromaPercent' => $aromaPercent,
        ],
    ]);
}





//// RENDER////
// Fonction pour afficher la page d'administration
function diy_render_settings_page() {
    $themes = get_option('diy_color_themes', []);
    $default_colors = diy_get_default_colors();
	$active_theme = get_option('diy_active_theme', '');
	//$current_theme_name = get_option('diy_active_theme', 'Default'); 
	
    // Récupérer les messages d'état
    $settings_updated = isset($_GET['settings-updated']);
    $error_message = isset($_GET['error-message']) ? esc_html($_GET['error-message']) : '';

    ?>
    <div class="wrap">
        <div class="container mt-4">
            <h1 class="mb-4"><?php _e('DIY Calculator Settings', 'diy-calculator'); ?></h1>
            <p class="lead"><?php _e('Customize the colors and manage your themes.', 'diy-calculator'); ?></p>

            <!-- Messages de notification -->
            <?php if ($settings_updated): ?>
                <div class="alert alert-success" role="alert">
                    <?php _e('Settings have been saved successfully!', 'diy-calculator'); ?>
                </div>
            <?php elseif (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php //echo $error_message; ?>
                    <div class="alert alert-danger" role="alert">
					    <?php echo sprintf(__('An error occurred: %s', 'diy-calculator'), $error_message); ?>
					</div>
                </div>
            <?php endif; ?>
            
            <?php 
			if (!isset($themes['Default'])) {
			    $themes['Default'] = diy_get_default_colors(); // Assurez-vous que le thème Default est présent
			}
			?>
			
			<?php $active_colors = get_option('diy_colors', diy_get_default_colors()); ?>
			<div id="theme-management" 
			     data-active-theme-name="<?php echo esc_attr($active_theme); ?>" 
			     data-active-colors='<?php echo wp_json_encode($active_colors); ?>'>
			</div>

			<div class="row">
			    <div class="col-4 shadow-sm bg-light p-4 rounded">
			        <form method="post" class="diy-settings-form">
			            <input type="hidden" name="action" value="diy_save_settings">
			            <?php wp_nonce_field('diy_calculator_settings', '_diy_calculator_nonce'); ?>
			
			            <?php
			            //settings_fields('diy_calculator_settings');
			            do_settings_sections('diy-calculator-settings');
			            ?>
			            <div class="row">
				            
				           
							<div class="col-6 mb-3">
							    <h6><?php _e('Apply to current theme', 'diy-calculator'); ?></h6>
							    <small class="text-muted">
							        <?php 
							        // Inclure le nom du thème actif entre parenthèses
							        printf(
							            __('Apply these settings to the current theme (%s).', 'diy-calculator'), 
							            esc_html($active_theme)
							        ); 
							        ?>
							    </small><br>
							    <button type="button" id="apply-current-theme" class="btn btn-primary" disabled>
							        <?php _e('Apply', 'diy-calculator'); ?>
							    </button>
							</div>
				            
			                <div class="col-6 mb-3">
			                    <label for="new-theme-name">
			                        <h6><?php _e('Save as new theme (optional)', 'diy-calculator'); ?></h6>
			                    </label><br>
			                    <small class="text-muted"><?php _e('Enter a name to save your current settings as a theme.', 'diy-calculator'); ?></small>
			                    <input type="text" id="new-theme-name" class="form-control" name="new_theme_name">
			                    <button 
			                        type="submit" 
			                        class="btn btn-outline-primary mb-3">
			                        <?php _e('Save theme', 'diy-calculator'); ?>
			                    </button>
			                </div>
			                
			               <div class="col-12 mt-3">
							    <h6><?php _e('Shortcode usage', 'diy-calculator'); ?></h6>
							    <div class="input-group">
							        <input 
							            type="text" 
							            class="form-control" 
							            value="[diy_calculator]" 
							            readonly 
							            id="shortcode-input" 
							        >
							        <button 
							            class="btn btn-outline-primary" 
							            type="button" 
							            onclick="copyShortcode()"
							        >
							            <?php _e('Copy', 'diy-calculator'); ?>
							        </button>
							    </div>
							    <p class="text-muted">
							        <?php _e('Simply copy and paste this shortcode into your page or article to display the DIY calculator to your visitors.', 'diy-calculator'); ?>
							    </p>
							</div>
							<script>
							function copyShortcode() {
							    const input = document.getElementById('shortcode-input');
							    input.select();
							    document.execCommand('copy');
							    alert('<?php echo esc_js(__('Shortcode copied to clipboard!', 'diy-calculator')); ?>');
							}
							</script>
			                
			                
			            </div>
			        </form>
			    </div>
			
			    <div class="col-8 shadow-sm bg-light p-4 rounded">
			        <p><strong><?php _e('Live Preview', 'diy-calculator'); ?></strong></p>
			        <div id="diy-live-preview" class="border p-3 rounded bg-white">
			            <?php echo do_shortcode('[diy_calculator]'); ?>
			        </div>
			    </div>
			</div>

            <hr class="my-4">

            <h2><?php _e('Saved Themes', 'diy-calculator'); ?></h2>
            
            
           
			<?php 
				
				//error_log('Themes from DB: ' . print_r($themes, true));
				//error_log('Active Theme Colors: ' . print_r(get_option('diy_colors'), true));
				
			?>

				<ul class="list-group mt-4">
				    <?php if (is_array($themes) && !empty($themes)): ?>
				        <?php foreach ($themes as $name => $colors): ?>
				            <li class="list-group-item d-flex justify-content-between align-items-center">
				                <div>
				                    <!-- Titre du thème -->
				                    <div class="theme_name"><?php echo esc_html($name); ?></div>
				                    
				                    <!-- Rectangle des couleurs -->
				                    <div class="theme-colors d-flex ms-2">
				                        <div class="color-block" style="background-color: <?php echo esc_attr($colors['diy_color_base'] ?? '#000'); ?>;"></div>
				                        <div class="color-block" style="background-color: <?php echo esc_attr($colors['diy_color_nicotine'] ?? '#000'); ?>;"></div>
				                        <div class="color-block" style="background-color: <?php echo esc_attr($colors['diy_color_aroma'] ?? '#000'); ?>;"></div>
				                    </div>
                    
				                    <!-- Ajout du span pour le thème actif -->
				                    <?php if ($name === $active_theme): ?>
				                        <span class="badge bg-secondary ms-2">
				                            <?php _e('Current theme', 'diy-calculator'); ?>
				                        </span>
				                    <?php endif; ?>
				                </div>
				                <div>
				                    <?php if ($name !== $active_theme): ?>
				                        <!-- Bouton Apply pour les autres thèmes -->
				                        <form method="post" action="" class="themes-form">
				                            <input type="hidden" name="action" value="diy_save_settings">
				                            <?php wp_nonce_field('diy_calculator_settings', '_diy_calculator_nonce'); ?>
				                            <input type="hidden" name="apply_theme_name" value="<?php echo esc_attr($name); ?>">
				                            <button type="submit" class="btn btn-success btn-sm">
				                                <?php _e('Apply', 'diy-calculator'); ?>
				                            </button>
				                        </form>
				                    <?php endif; ?>
				                    
				                    <?php if ($name !== 'Default'): ?>
				                        <!-- Bouton Delete pour tous les thèmes sauf "Default" -->
				                        <button class="btn btn-danger btn-sm delete-theme" data-theme="<?php echo esc_attr($name); ?>">
				                            <?php _e('Delete', 'diy-calculator'); ?>
				                        </button>
				                    <?php endif; ?>
				                </div>
				            </li>
				        <?php endforeach; ?>
				    <?php else: ?>
				        <li class="list-group-item">
				            <?php _e('No themes available.', 'diy-calculator'); ?>
				        </li>
				    <?php endif; ?>
				</ul>
        </div>
    </div>
    <?php
}



// AJAX pour obtenir la liste des thèmes
add_action('wp_ajax_diy_get_theme_list', 'diy_get_theme_list');
function diy_get_theme_list() {
    // Vérifiez les capacités de l'utilisateur
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized action.', 'diy-calculator')]);
    }

    // Vérifiez le nonce pour sécuriser la requête
    if (!isset($_POST['_diy_calculator_nonce']) || !wp_verify_nonce($_POST['_diy_calculator_nonce'], 'diy_calculator_settings')) {
        wp_send_json_error(['message' => __('Nonce verification failed.', 'diy-calculator')]);
    }

    // Récupérer les thèmes depuis la base de données
    $themes = get_option('diy_color_themes', []);
    $active_theme = get_option('diy_active_theme', 'Default');

    // Assurez-vous que le thème par défaut existe
    if (!isset($themes['Default'])) {
        $themes['Default'] = diy_get_default_colors();
        update_option('diy_color_themes', $themes);
    }

    // Construire le HTML pour la liste des thèmes
    ob_start();
    ?>
    <ul class="list-group">
        <?php if (!empty($themes) && is_array($themes)): ?>
            <?php foreach ($themes as $name => $colors): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="theme_name"><?php echo esc_html($name); ?></div>
                        <div class="theme-colors d-flex ms-2">
                            <div class="color-block" style="background-color: <?php echo esc_attr($colors['diy_color_base'] ?? '#000'); ?>;"></div>
                            <div class="color-block" style="background-color: <?php echo esc_attr($colors['diy_color_nicotine'] ?? '#000'); ?>;"></div>
                            <div class="color-block" style="background-color: <?php echo esc_attr($colors['diy_color_aroma'] ?? '#000'); ?>;"></div>
                        </div>
                        <?php if ($name === $active_theme): ?>
                            <span class="badge bg-secondary ms-2">
                                <?php _e('Current theme', 'diy-calculator'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($name !== $active_theme): ?>
                            <form method="post" class="themes-form">
                                <input type="hidden" name="action" value="diy_manage_themes">
                                <?php wp_nonce_field('diy_calculator_settings', '_diy_calculator_nonce'); ?>
                                <input type="hidden" name="apply_theme_name" value="<?php echo esc_attr($name); ?>">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <?php _e('Apply', 'diy-calculator'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if ($name !== 'Default'): ?>
                            <button class="btn btn-danger btn-sm delete-theme" data-theme="<?php echo esc_attr($name); ?>">
                                <?php _e('Delete', 'diy-calculator'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="list-group-item">
                <?php _e('No themes available.', 'diy-calculator'); ?>
            </li>
        <?php endif; ?>
    </ul>
    <?php
    $html = ob_get_clean();

    // Envoyer la réponse AJAX avec le HTML généré
    wp_send_json_success(['html' => $html]);
}



///////////////////
// CLIENT SIDE
// Enqueue les assets uniquement si le shortcode est présent dans le contenu
function diy_calculator_enqueue_assets() {
    global $post;

    // Vérifier si le post existe et contient le shortcode
    if (isset($post) && has_shortcode($post->post_content, 'diy_calculator')) {
        // Chemins des fichiers CSS et JS
        $css_file_path = plugin_dir_path(__FILE__) . 'diy-calculator.css';
        $js_file_path = plugin_dir_path(__FILE__) . 'diy-calculator.js';

        // Enqueue CSS local
        wp_enqueue_style(
            'diy-calculator-style',
            plugin_dir_url(__FILE__) . 'diy-calculator.css',
            array(),
            filemtime($css_file_path) // Version basée sur l'horodatage
        );

        // **Ajout des styles FastBootstrap**
        wp_enqueue_style(
            'fastbootstrap-style',
            'https://cdn.jsdelivr.net/npm/fastbootstrap@2.2.0/dist/css/fastbootstrap.min.css',
            array(),
            null, // Pas de version spécifique
            'all'
        );

        // Enqueue JS local
        wp_enqueue_script(
            'diy-calculator-script',
            plugin_dir_url(__FILE__) . 'diy-calculator.js',
            ['jquery'],
            filemtime($js_file_path), // Version basée sur l'horodatage
            true
        );

        // Définir les liens encodés pour chaque langue
        $vpll = array(
            'fr_FR' => 'PGEgaHJlZj0iaHR0cHM6Ly9mci52YXBpbmdwb3N0LmNvbSIgdGFyZ2V0PSJfYmxhbmsiPlByb3Bvc8OpIHBhciBsZSBWYXBpbmcgUG9zdDwvYT4=',
            'ar' => 'PGEgaHJlZj0iaHR0cHM6Ly9hci52YXBpbmdwb3N0LmNvbSIgdGFyZ2V0PSJfYmxhbmsiPtmF2YLYr9mF2Kkg2YXZhiBWYXBpbmcgUG9zdDwvYT4=',
            'en_US' => 'PGEgaHJlZj0iaHR0cHM6Ly93d3cudmFwaW5ncG9zdC5jb20iPlBvd2VyZWQgYnkgdGhlIFZhcGluZyBQb3N0PC9hPg==',
            'en' => 'PGEgaHJlZj0iaHR0cHM6Ly93d3cudmFwaW5ncG9zdC5jb20iPlBvd2VyZWQgYnkgdGhlIFZhcGluZyBQb3N0PC9hPg==',
        );
 
        // Valeur par défaut (anglais générique)
        $vpld = 'PGEgaHJlZj0iaHR0cHM6Ly93d3cudmFwaW5ncG9zdC5jb20iPlBvd2VyZWQgYnkgdGhlIFZhcGluZyBQb3N0PC9hPg==';

        // Déterminer la langue active
        $locale = get_locale();
		$vpl = isset($vpll[$locale]) ? $vpll[$locale] : $vpld;

        // Localisation des chaînes traduisibles et du lien encodé
        wp_localize_script(
            'diy-calculator-script',
            'diyCalculatorStrings', // Nom de l'objet JS
            array(
                'warningMessage' => __('Not enough space for flavors with current nicotine level.', 'diy-calculator'),
                'bottlesLabel' => __('bottles', 'diy-calculator'),
                'bottlesOfLabel' => __('bottles of 10ml each', 'diy-calculator'),
                'vplus' => $vpl
            )
        );

        // **Ajout de la bibliothèque Bootstrap**
        wp_enqueue_script(
            'bootstrap-script',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
            array(),
            null, // Pas de version spécifique
            true // Charger dans le footer
        );
    }
}
add_action('wp_enqueue_scripts', 'diy_calculator_enqueue_assets');

// CSS dynamique
add_action('wp_enqueue_scripts', 'diy_enqueue_dynamic_css');
function diy_enqueue_dynamic_css() {
    global $post;

    // Vérifier si le shortcode est présent avant de charger le CSS dynamique
    if (isset($post) && has_shortcode($post->post_content, 'diy_calculator')) {
        wp_enqueue_style(
            'diy-dynamic-styles',
            plugin_dir_url(__FILE__) . 'dynamic-styles.php',
            array('diy-calculator-style'),
            time() // Pour forcer le rafraîchissement en développement
        );
    }
}



// Shortcode pour afficher le calculateur
function diy_calculator_shortcode() {
    ob_start();
    ?>
    <div class="mt-2" data-bs-theme="light">
	    <div class="title-settings"><?php _e('Enter your values', 'diy-calculator'); ?></div>
            
        <div class="res-col">
            <div class="col-md-12">
                
                
                <div class="mb-3 range-section">
                    <label for="total-volume" class="form-label"><?php _e('Total Volume', 'diy-calculator'); ?> : <span id="total-volume-display" class="badge range-neutral">10 ml</span></label><input id="total-volume" type="range" min="10" max="100" step="10" value="10" class="form-range">
                    
                </div>

                <div class="mb-3 range-section">
                    <label for="nicotine-level" class="form-label"><?php _e('Target Nicotine Strength', 'diy-calculator'); ?> : <span id="nicotine-level-display" class="badge text-bg-primary  range-nicotine">3 mg/ml</span></label><input id="nicotine-level" type="range" min="0" max="20" step="1" value="3" class="form-range">
                    
                </div>

               

                <div class="mb-3 range-section">
                    <label for="aroma-percent" class="form-label"><?php _e('Flavors Concentrate', 'diy-calculator'); ?> : <span id="aroma-percent-display" class="badge text-bg-primary  range-aroma">1.2%</span></label><input id="aroma-percent" type="range" min="0" max="20" step="0.1" value="1.2" class="form-range">
                    
                </div>
            </div>
        </div>

        <!-- Partie résultats -->
        <div class="row gx-8" style="margin: 20px auto 0 auto;">
            <div class="col d-flex align-items-stretch rec-res" style="padding-left: 0;">
	            <div class="res-col">
	                <div class="title-results"><?php _e('Recipe Results', 'diy-calculator'); ?></div>
	                <div class="list-results d-flex gap-1">
					    <div id="base-amount" class="flex-fill border p-2 text-center"><span class="res-label"><?php _e('Base', 'diy-calculator'); ?></span><span class="res-value">10</span><span class="ml">ml</span></div>
					    <div id="booster-amount" class="flex-fill border p-2 text-center"><span class="res-label"><?php _e('Boosters', 'diy-calculator'); ?></span><span class="res-value">2</span><span class="ml">ml</span><span class="res-value-bottle"></span></div>
					    <div id="aroma-amount" class="flex-fill border p-2 text-center"><span class="res-label"><?php _e('Flavors', 'diy-calculator'); ?></span><span class="res-value">1</span><span class="ml">ml</span></div>
					</div>
	            </div>
            </div>
            
            
            <div class="col mix-res">
		    
			    <div class="res-col">
				    <div class="title-results"><?php _e('Your mix', 'diy-calculator'); ?></div>
				    
					    <div class="visualization-wrapper" style="position: relative;">
					        <div class="visual-legend-simple">
					            <div class="legend-item">
					                <span class="legend-color legend-base"></span>
					                <span class="legend-text"><?php _e('Base', 'diy-calculator'); ?></span>
					            </div>
					            <div class="legend-item">
					                <span class="legend-color legend-nicotine"></span>
					                <span class="legend-text"><?php _e('Nicotine', 'diy-calculator'); ?></span>
					            </div>
					            <div class="legend-item">
					                <span class="legend-color legend-aroma"></span>
					                <span class="legend-text"><?php _e('Flavors', 'diy-calculator'); ?></span>
					            </div>
					        </div>
					        <div class="flask-visual">
					            <div id="base-visual"></div>
					            <div id="nicotine-visual"></div>
					            <div id="aroma-visual"></div>
					            <div class="volume-total-label">10ml</div>
					            <div class="glass-reflection"></div>
					        </div>
					        <div id="back-ml">10</div>
					        <div id="back-nic" class="nicotine-badge">
							  <span class="nicotine-value">3</span>
							  <span class="nicotine-unit">mg/ml</span>
							</div>
					    </div>
						
						
					</div>
            	</div>
        </div>
        <div id="cr"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('diy_calculator', 'diy_calculator_shortcode');
