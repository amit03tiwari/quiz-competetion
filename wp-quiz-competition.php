<?php
/*
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * 
 * @wordpress-plugin
 * Plugin Name:       Quiz Competition
 * Plugin URI:        https://innosewa.com/
 * Description:       A WordPress plugin for quiz competitions with leaderboards, timed submissions, and user performance tracking. Create powerful and engaging quizzes, tests, and exams in minutes. Build an unlimited number of quizzes and questions.
 * Version:           1.0
 * Author:            InnoSewa Team
 * Author URI:        https://innosewa.com/our-technical-squad/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       quiz-competition

 
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly.

// Define constants for the plugin.
define('QC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include plugin components.
require_once QC_PLUGIN_PATH . 'includes/admin-dashboard.php';
require_once QC_PLUGIN_PATH . 'includes/public-quiz.php';
require_once QC_PLUGIN_PATH . 'includes/functions.php';

// On activation, create database tables.
register_activation_hook(__FILE__, 'qc_install_tables');
function qc_install_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_quizzes   = $wpdb->prefix . 'qc_quizzes';
    $table_questions = $wpdb->prefix . 'qc_questions';
    $table_options   = $wpdb->prefix . 'qc_options';
    $table_results   = $wpdb->prefix . 'qc_results';

    $sql = "
    CREATE TABLE $table_quizzes (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        max_time INT DEFAULT 0,
        randomize_questions TINYINT(1) DEFAULT 0,
        questions_to_show INT DEFAULT 0,
        auto_next TINYINT(1) DEFAULT 1,
        submission_start DATETIME NULL,
        submission_end DATETIME NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_questions (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        quiz_id MEDIUMINT(9) NOT NULL,
        question TEXT NOT NULL,
        question_type VARCHAR(20) DEFAULT 'radio',
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_options (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        question_id MEDIUMINT(9) NOT NULL,
        option_text TEXT NOT NULL,
        is_correct TINYINT(1) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_results (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        quiz_id MEDIUMINT(9) NOT NULL,
        user_id BIGINT(20) NOT NULL,
        correct_answers INT DEFAULT 0,
        wrong_answers INT DEFAULT 0,
        total_time INT DEFAULT 0,
        attempt_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
?>
