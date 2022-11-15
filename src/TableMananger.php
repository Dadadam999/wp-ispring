<?php
/**
 * @package wpispring
 * @author Bogdanov Andrey (swarzone2100@yandex.ru)
 */

 namespace wpispring;

 use WP_REST_Request;
 use wpdb;

 use wpispring\Tables\ResultTable;

 class TableMananger
 {
   protected $wpdb;
   public $resultTable;

   public function __construct()
   {
       global $wpdb;
       $this->wpdb = $wpdb;
       $this->Init();
   }

   protected function Init() : self
   {
     $this->resultTable = new ResultTable();
     return $this;
   }

   public function Install()
   {
     $this->resultTable->CreateTable();
   }

   public function Uninstall()
   {
     $this->resultTable->DeleteTable();
   }
 }
?>
