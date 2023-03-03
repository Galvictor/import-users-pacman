<?php
/*
Plugin Name: Import Users from CSV And Pacaman
Description: Plugin para importar usuários a partir de um arquivo CSV
Version: 1.0
Author: João
*/

function import_users_csv_menu()
{
    add_menu_page(
        'Import Users from CSV',
        'Import Users from CSV',
        'manage_options',
        'import-users-csv',
        'import_users_csv_page'
    );
}

add_action('admin_menu', 'import_users_csv_menu');

function add_import_xls_submenu()
{
    add_submenu_page(
        'import-users-csv',
        'PacMan',
        'PacMan',
        'manage_options',
        'pacman',
        'fn_pacman'
    );
}

add_action('admin_menu', 'add_import_xls_submenu');

function import_users_csv_page()
{
    ?>
    <div class="wrap">
        <h1>Import Users from CSV</h1>
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><p><label for="csv_delimiter">Delimitador do CSV</label></th>
                    <td>
                        <select name="csv_delimiter" id="csv_delimiter">
                            <option value=";" selected>;</option>
                            <option value=",">,</option>
                            <option value="|">|</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="csv_file">Select CSV file:</label></th>
                    <td><input type="file" id="csv_file" name="csv_file"></td>
                </tr>
            </table>
            <?php submit_button('Import Users', 'primary', 'importar_user_csv'); ?>
        </form>
    </div>
    <?php
    import_users_csv();
}

function fn_pacman()
{
    ob_start();
    include 'pacmanv2.html';
    echo ob_get_clean();
}

function import_users_csv()
{
    $field_map = array(
        'user_email' => 'user_email',
        'user_login' => 'user_login',
        'user_pass' => 'user_pass',
        'user_nicename' => 'user_nicename',
        'role' => 'role'
    );

    if (isset($_POST['importar_user_csv'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $file_type = wp_check_filetype($_FILES['csv_file']['name']);
            if ($file_type['ext'] === 'csv' && $_FILES['csv_file']['type'] === 'text/csv') {
                $file = $_FILES['csv_file']['tmp_name'];
                if (($handle = fopen($file, "r")) !== FALSE) {
                    $headers = fgetcsv($handle, 1000, $_POST['csv_delimiter']);
                    while (($data = fgetcsv($handle, 1000, $_POST['csv_delimiter'])) !== FALSE) {
                        $fields = array_combine($headers, $data);

                        $userdata = [];
                        foreach ($field_map as $csv_key => $wp_key) {
                            if (isset($fields[$csv_key])) {
                                $userdata[$wp_key] = sanitize_text_field($fields[$csv_key]);
                            }
                        }

                        if (email_exists($userdata['user_email'])) {
                            echo '<div class="error"><p>The user with email ' . $userdata['user_email'] . ' already exists.</p></div>';
                            continue;
                        }

                        $userdata['user_pass'] = wp_generate_password();
                        var_dump($userdata);
                        $user_id = wp_insert_user($userdata);

                        if (is_wp_error($user_id)) {
                            echo '<div class="error"><p>' . $user_id->get_error_message() . '</p></div>';
                            continue;
                        }

                        if ($user_id) {
                            wp_new_user_notification($user_id, null, 'user');
                        }
                    }
                    fclose($handle);
                    echo '<div class="updated"><p>Users imported successfully!</p></div>';
                }
            } else {
                echo '<div class="error"><p>Não é um arquivo csv</p></div>';
            }


        } else {
            echo '<div class="error"><p>Please select a CSV file to import.</p></div>';
        }
    }


}

add_action('admin_post_import_users_csv', 'import_users_csv');

function import_users_csv_handler()
{
    if (isset($_POST['action']) && $_POST['action'] == 'import_users_csv') {
        check_admin_referer('import_users_csv');
        import_users_csv();
    }
    import_users_csv_page();
}

function import_users_csv_shortcode()
{
    ob_start();
    import_users_csv_handler();
    return ob_get_clean();
}

add_shortcode('import_users_csv', 'import_users_csv_shortcode');

