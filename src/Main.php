<?php
/**
 * @package wpispring
 * @author Bogdanov Andrey (swarzone2100@yandex.ru)
 */
namespace wpispring;

 use wpispring\Tables\ResultTable;

use WP_REST_Request;

class Main
{
    protected $tableMananger;

    protected $wpdb;

    protected $user_id;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->Init();
    }

    protected function init() : self
    {
        new ResultTable;
        $this->tableMananger = new TableMananger;
        $this->apiInit();
        $this->InitWP();
        $this->scriptAdd();
        $this->menuAdd();
        return $this;
    }

    protected function scriptAdd() : self
    {
        wp_enqueue_style( 'shortcodes', plugins_url('wp-ispring/assets/css/shortcodes.css') );

        add_action('wp_enqueue_scripts', function()
        {
            wp_enqueue_script(
                'wpispring-client',
                plugins_url('wp-ispring/assets/js/wpispring-client.js'),
                [],
                '0.1.5'
            );
        });
        return $this;
    }

    protected function menuAdd() : self
    {
      add_action('admin_menu', function()
      {
          add_menu_page(
              'Настройки iSpring',
              'iSpring',
              'administrator',
              'settings_ispring',
              array($this, 'ispring_settings_callback'),
              'dashicons-admin-generic',
              20
          );
      });

        return $this;
    }

    protected function GetPost($post_id)
    {
      return $this->wpdb->get_results(
          "SELECT `ID`, `post_title`
           FROM `" . $this->wpdb->prefix . "posts`
           WHERE ID = " . $post_id,
           ARRAY_A
      )[0];
    }

    protected function GetPostIdByPostName($post_name)
    {
      return $this->wpdb->get_results(
          "SELECT `ID`
           FROM `" . $this->wpdb->prefix . "posts`
           WHERE `post_name` = '" . $post_name . "'",
           ARRAY_A
      )[0]['ID'];
    }

    function ispring_settings_callback()
    {
       $html = '';
       $html .= '<div class="container">';
       $html .= '<h1 class="h3 text-center my-5">Скачать статистику тестов iSpring</h1>';
       $html .= '<div style=" max-width: 500px; margin: 0px auto;">';
       $html .= '<form action="" method="post">';
       $html .=  wp_nonce_field('wpispringSettingsNonce-wpnp', 'wpispringSettingsNonce');
       $html .= '<label style="margin-top:20px; min-width: 50%;" for="wpispringselecpost" class="form-label">Выберите мероприятие по которому необходимо выгрузить тест:</label>';
       $html .= '<br>';
       $html .= '<select style="min-width: 50%;" name="select" name="wpispringselectpost" class="form-control form-control-sm">';

       $group_posts_id = $this->tableMananger->resultTable->GetGroupPosts();

       foreach ($group_posts_id as $group_post_id)
       {
          $post = $this->GetPost($group_post_id['post_id']);
          $html .= '<option value="' . $post['ID'] . '">'. $post['post_title'] .'</option>';
       }

       $html .= '</select>';
       $html .= '<br>';
       $html .= '<button type="submit" style="margin-top:20px;" class="button button-primary">Выгрузить CSV</button>';
       $html .= '</form>';
       $html .= '</div>';
       $html .= '</div>';

       echo $html;
    }

    protected function InitWP()
    {
      add_action('init', function()
      {
          $this->user_id = get_current_user_id();

          $file = '<?xml version="1.0" encoding="UTF-8"?>
<quizReport xmlns="http://www.ispringsolutions.com/ispring/quizbuilder/quizresults" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.ispringsolutions.com/ispring/quizbuilder/quizresults quizReport.xsd" version="2">
   <quizSettings quizType="graded" maxScore="20" maxNormalizedScore="100" timeLimit="0">
      <passingPercent>80</passingPercent>
   </quizSettings>
   <summary score="10" percent="50" time="10" finishTimestamp="26 января 2022 г. 13:30" passed="false" />
   <questions>
      <multipleChoiceQuestion id="z5q2y6e36egh-s0x4agh84d3c" status="incorrect" evaluationEnabled="true" maxPoints="10" maxAttempts="1" awardedPoints="0" usedAttempts="1">
         <direction>
            <text><![CDATA[Выберите правильный вариант ответа1:]]></text>
         </direction>
         <feedback>
            <text><![CDATA[Вы ответили неверно.]]></text>
         </feedback>
         <answers correctAnswerIndex="0" userAnswerIndex="2">
            <answer>
               <text><![CDATA[Вариант 1]]></text>
            </answer>
            <answer>
               <text><![CDATA[Вариант 2]]></text>
            </answer>
            <answer>
               <text><![CDATA[Вариант 3]]></text>
            </answer>
         </answers>
      </multipleChoiceQuestion>
      <multipleChoiceQuestion id="aopbsdt5zzpc-22zh5heiyeqs" status="correct" evaluationEnabled="true" maxPoints="10" maxAttempts="1" awardedPoints="10" usedAttempts="1">
         <direction>
            <text><![CDATA[Выберите правильный вариант ответа2:]]></text>
         </direction>
         <feedback>
            <text><![CDATA[Вы ответили верно.]]></text>
         </feedback>
         <answers correctAnswerIndex="0" userAnswerIndex="0">
            <answer>
               <text><![CDATA[Вариант 1]]></text>
            </answer>
            <answer>
               <text><![CDATA[Вариант 2]]></text>
            </answer>
            <answer>
               <text><![CDATA[Вариант 3]]></text>
            </answer>
         </answers>
      </multipleChoiceQuestion>
   </questions>
   <groups>
      <group name="Группа вопросов 1;modul-test" passingScore="16" awardedScore="10" passingPercent="80" awardedPercent="50" totalQuestions="2" answeredQuestions="1" />
   </groups>
</quizReport>';

          $results = simplexml_load_string($file);
        //  echo '<pre>'. var_dump($results->questions) . '</pre>';
      });

      return $this;
    }

    protected function download()
    {
      add_action('plugins_loaded', function()
      {
          // if (wp_verify_nonce($_POST['poststat-download'], 'poststat-download-wpnp') === false)
          //     $this->notice('error', $this->fail_nonce_notice);
          // else
          // {
          //     $post_id = (int)$_POST['poststat-download-post'];
          //
          //     $end_time = $this->wpdb->get_results(
          //         "SELECT t.meta_value
          //             FROM `".$this->wpdb->prefix."postmeta` AS t
          //             WHERE t.post_id = ".$post_id."
          //             AND t.meta_key = 'evcal_erow'",
          //         ARRAY_A
          //     );
          //
          //     if (!empty($end_time))
          //       $end_time = (int)$end_time[0]['meta_value'] + 3600;
          //     else
          //       $end_time = 0;
          //
          //     if (empty($presence)) {
          //         $this->notice(
          //             'warning',
          //             'Статистика по указанному мероприятию отсутствует.'
          //         );
          //         return;
          //     }
          //
          //     if (!file_exists($this->path.'temp/')) {
          //
          //         if (!mkdir($this->path.'temp/')) {
          //
          //             $this->notice(
          //                 'error',
          //                 'Нет доступа к временной директории. Пожалуйста, обратитесь к администратору.'
          //             );
          //
          //             return;
          //
          //         }
          //
          //     }
          //
          //     $ch_arr = array_merge(range('a', 'z'), range(0, 9));
          //
          //     do {
          //
          //         $filename = '';
          //
          //         for ($i = 0; $i < 32; $i++) {
          //
          //             $filename .= $ch_arr[rand(0, count($ch_arr) - 1)];
          //
          //         }
          //
          //         $filename .= '.csv';
          //
          //     } while (file_exists($this->path.'temp/'.$filename));
          //
          //     $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
          //     $writer->save($this->path.'temp/'.$filename);
          //
          //     unset($spreadsheet);
          //     unset($writer);
          //
          //     $file = file_get_contents($this->path.'temp/'.$filename);
          //
          //     unlink($this->path.'temp/'.$filename);
          //
          //     header('Content-type: application; charset=utf-8');
          //     header('Content-disposition: attachment; filename=statistics.csv');
          //
          //     echo $file;
          //
          //     die;
          //}
      });

      return $this;
    }

    protected function apiInit() : self
    {
        add_action('rest_api_init', function()
        {
            register_rest_route(
                'wp-ispring/v1',
                '/gettest',
                [
                    'methods' => 'POST',
                    'callback' => function(WP_REST_Request $request)
                    {
                        $points = $request->get_param('sp');
                        $percent = $request->get_param('psp');
                        $points = $request->get_param('tp');
                        $title = $request->get_param('qt');
                        $results = $request->get_param('dr');

                        $points =  empty($points) ? 0 : $points;
                        $precent = empty($precent) ? 0 : $precent;

                        $atts = explode(";", $title);
                        $title = $atts[0];
                        $post_name = empty($atts[1]) ? '' : $atts[1];

                        if ( empty($post_name) )
                            return [
                                'code' => -99,
                                'message' => 'Postname is empty!'
                            ];

                        $post_id = $this->GetPostIdByPostName($post_name);

                        if ( empty($post_id) )
                            return [
                                'code' => -99,
                                'message' => 'PostID is empty!'
                            ];

                        $date = date("Y-m-d H:i:s", time());

                        $this->tableMananger->resultTable->Add($this->user_id, $post_id, $title, $points, $precent, $results, $date);

                        return [
                            'code' => 0,
                            'message' => 'Success.'
                        ];
                    },
                    'permission_callback' => function(WP_REST_Request $request) {
                        return !empty( $request->get_param('qt') );
                    }
                ]
            );
        });
        return $this;
    }
}
