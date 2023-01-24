<?php
/**
 * @package wpispring
 * @author Bogdanov Andrey (swarzone2100@yandex.ru)
 */

namespace wpispring\Tables;

class ResultTable
{
    protected $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function CreateTable()
    {
        $this->wpdb->get_results(
           "CREATE TABLE `" . $this->wpdb->prefix . "wpispring_results`
           (
           id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	         user_id BIGINT(20) UNSIGNED NOT NULL,
           post_id BIGINT(20),
	         name VARCHAR(255),
           passing_precent BIGINT(20),
           score BIGINT(20),
           results MEDIUMTEXT,
           date DATETIME,
	         UNIQUE KEY id (id)
           )"
        );
    }

    public function DeleteTable()
    {
        $this->wpdb->get_results(
          "DROP TABLE `" . $this->wpdb->prefix . "wpispring_results`"
        );
    }

    public function GetAll()
    {
      return $this->wpdb->get_results(
         "SELECT *
         FROM `" . $this->wpdb->prefix . "wpispring_results`",
         ARRAY_A
        );
    }

    public function GetByPost( $post_id )
    {
      return $this->wpdb->get_results(
         "SELECT *
         FROM `" . $this->wpdb->prefix . "wpispring_results`
         WHERE `post_id` = " . $post_id,
         ARRAY_A
        );
    }

    public function GetGroupPosts()
    {
      return $this->wpdb->get_results(
         "SELECT `post_id`
         FROM `" . $this->wpdb->prefix . "wpispring_results`
         GROUP BY `post_id`",
         ARRAY_A
        );
    }

    public function Add($user_id, $post_id, $title, $points, $precent, $results, $date)
    {
      $this->wpdb->get_results(
        "INSERT INTO `" . $this->wpdb->prefix . "wpispring_results` (`user_id`, `post_id`, `name` , `score`, `passing_precent`, `results`, `date`)
        VALUES (" . $user_id . ", " . $post_id . ", '" . $title . "', " . $points . ", " . $precent . ", '" . $results . "', '" . $date . "')"
      );
    }

    public function Delete($id)
    {
        $this->wpdb->get_results(
        "DELETE FROM `" . $this->wpdb->prefix . "wpispring_results` WHERE id = " . $id
        );
    }
}
