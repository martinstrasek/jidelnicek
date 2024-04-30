<?php
/*
Plugin Name: Jídelníček
Description: Lunch menu management plugin
Version: 1.0
Author: Martin Strašek
*/

// Register the dashboard widget
function jidelnicek_dashboard_widget() {
    wp_add_dashboard_widget(
        'jidelnicek_dashboard_widget',
        'Jídelníček',
        'jidelnicek_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'jidelnicek_dashboard_widget');

// Dashboard widget content
function jidelnicek_dashboard_widget_content() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle file upload
    if (isset($_FILES['jidelnicek_file'])) {
      $file = $_FILES['jidelnicek_file'];
      $file_type = wp_check_filetype($_FILES['jidelnicek_file']['name']);
      $allowed_types = array('pdf'); // Allowed file types

      if ($file['error'] === 0 && in_array($file_type['ext'], $allowed_types)) {
          $upload_dir = wp_upload_dir();
          $menu_dir = $upload_dir['basedir'] . '/jidelnicek';
          if (!file_exists($menu_dir)) {
              wp_mkdir_p($menu_dir);
          }
          move_uploaded_file($file['tmp_name'], $menu_dir . '/jidelnicek.pdf');
          echo '<div class="updated"><p>Jídelníček úspěšně přidán.</p></div>';
      } else {
          echo '<div class="error"><p>Chyba nahrávání souboru. Prosím nahrajte soubor ve formátu PDF.</p></div>';
      }
    }

    // Display file upload form
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<label for="jidelnicek_file">Nahrát nový jídelníček (PDF):</label><br>';
    echo '<input type="file" name="jidelnicek_file" id="jidelnicek_file"><br>';
    echo '<input type="submit" value="Nahrát" class="button button-primary">';
    echo '</form>';
}

// Rewrite rule to serve PDF file
function jidelnicek_rewrite_rule($rules) {
    $new_rules = array(
        'stravovani/jidelnicek' => 'index.php?jidelnicek=1',
    );
    return $new_rules + $rules;
}
add_filter('rewrite_rules_array', 'jidelnicek_rewrite_rule');
// Flush the rewrite rules
flush_rewrite_rules();

// Add query variable for PDF
function jidelnicek_query_vars($vars) {
    $vars[] = 'jidelnicek';
    return $vars;
}
add_filter('query_vars', 'jidelnicek_query_vars');

// Serve PDF file
function jidelnicek_serve_pdf() {
    if (get_query_var('jidelnicek') == 1) {
        $upload_dir = wp_upload_dir();
        $pdf_path = $upload_dir['basedir'] . '/jidelnicek/jidelnicek.pdf';
        if (file_exists($pdf_path)) {
            header('Content-type: application/pdf');
            readfile($pdf_path);
            exit;
        } else {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
        }
    }
}
add_action('template_redirect', 'jidelnicek_serve_pdf');