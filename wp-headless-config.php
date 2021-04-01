<?php

   /*
   Plugin Name: WP Headless config
   Version: 1.0.0
   Author: InsomniacDesign
   Author URI: https://www.insomniacdesign.com/
   */

  require_once(ABSPATH . 'wp-config.php');

$urlError = "";
$url = "";
$servername=DB_HOST;
$dbname = DB_NAME;
$value = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if (!preg_match('/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i',test_input($_POST["headless_config_plugin_settings"]["frontend_domain"]))) {
        $urlError = "Domain is not valid";
        $value = null;
        $error_value = "fail";
    } else {

        if (isset($_POST['submit'])) {
            $some_text_field = $_POST["headless_config_plugin_settings"]["frontend_domain"];
            $value = $some_text_field;


            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $conn->prepare("UPDATE wp_input_url_data SET url_value='$some_text_field' WHERE url_name = 'url address'");

                if ($stmt->execute()) {
                    $error_value = "pass";
                    $conn = null;
                }

            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }

            $conn = null;
        }
    }
}

  /*---------------------------------------------------*/

function test_contact_form()
{
    global $wpdb;
    $db_table_name = $wpdb->prefix . 'input_url_data';  // table name
    $charset_collate = $wpdb->get_charset_collate();

    if($wpdb->get_var( "show tables like '$db_table_name'" ) != $db_table_name) {

        $sql = "CREATE TABLE $db_table_name (
                id int(11) NOT NULL auto_increment,
                url_name varchar(60) NOT NULL,
                url_value varchar(200) NOT NULL,
                UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $url_name = 'url address';
        $url_value = '';

        $wpdb->insert(
            $db_table_name,
            array(
                'url_name' => $url_name,
                'url_value' => $url_value,
            )
        );
    }
    else{
       $wpdb->query("TRUNCATE TABLE $db_table_name");

        $url_name = 'url address';
        $url_value = '';

        $wpdb->insert(
            $db_table_name,
            array(
                'url_name' => $url_name,
                'url_value' => $url_value,
            )
        );
    }
}

register_activation_hook( __FILE__, 'test_contact_form' );


  /*---------------------------------------------------*/

  function headless_config_add_settings_page() {
    add_options_page(
      'Headless config Settings',
      'Headless config',
      'manage_options',
      'headless_config_plugin',
      'headless_config_render_settings_page'
    );
  }
  add_action('admin_menu', 'headless_config_add_settings_page');



  function headless_config_render_settings_page() {
      global $error_value;
      ?>
    <h1>Headless config</h1>
      <?php
      if ($error_value == "pass") {
          echo '<div class="updated">
          <p>' . __('Success! Data have been saved.') . '</p>
      </div>';
      } else if ($error_value == "fail") {
          echo
              '<div class="error">
          <p>' . __('Your domain name is not valid! Please try again!') . '</p>
      </div>';
      }
      ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]."?page=headless_config_plugin");?>">
        <?php
        settings_fields( 'headless_config_plugin_settings' );
        do_settings_sections( 'headless_config_plugin' );
        ?>
        <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
      <?php
    }

    function headless_config_register_settings() {

      register_setting(
        'headless_config_plugin_settings',
        'headless_config_plugin_settings',
      );

      add_settings_section(
        'section_one',
        '',
        '',
        'headless_config_plugin'
      );
    
      add_settings_field(
        'frontend_domain',
        '<h3>Frontend Domain</h3>',
        'headless_config_render_frontend_domain_field',
        'headless_config_plugin',
        'section_one'
      );

    }
    add_action('admin_init', 'headless_config_register_settings');

function headless_config_render_frontend_domain_field() {
  $servername=DB_HOST;
  $dbname = DB_NAME;

  global $urlError;
  global $value;

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", DB_USER, DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->prepare("SELECT url_value FROM wp_input_url_data WHERE url_name = 'url address'");
        $stmt->execute();
        $result = $stmt->fetchAll();
        $value = $result[0]["url_value"];


    } catch(PDOException $e) {
        echo "Something went wrong.We are trying to resolve it.";
//        echo "Error: " . $e->getMessage();
    }

    $options = get_option('headless_config_plugin_settings');
    printf(
        "<input type='text' name='%s' value='$value'/>",
        esc_attr('headless_config_plugin_settings[frontend_domain]'),
        esc_attr($options['frontend_domain'])
    );
    ?>
    <span>* <?php echo $urlError;?></span>
    <?php


    $conn = null;
}



?>