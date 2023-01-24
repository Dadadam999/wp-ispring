<?php
/**
 * @package wpispring
 * @author Bogdanov Andrey (swarzone2100@yandex.ru)
 */
namespace wpispring;
use wpispring\Tables\ResultTable;
use WP_REST_Request;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $this->download();
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
       //$html = $xml->name . ' is ' . $xml->age . ' years old.';
       $html .= '<div class="container">';
       $html .= '<h1 class="h3 text-center my-5">Скачать статистику тестов iSpring</h1>';
       $html .= '<div style=" max-width: 500px; margin: 0px auto;">';
       $html .= '<form action="" method="post">';
       $html .=  wp_nonce_field('wpispringSettingsNonce-wpnp', 'wpispringSettingsNonce');
       $html .= '<label style="margin-top:20px; min-width: 50%;" for="wpispringselecpost" class="form-label">Выберите мероприятие по которому необходимо выгрузить тест:</label>';
       $html .= '<br>';
       $html .= '<select style="min-width: 50%;" name="wpispringselectpost" class="form-control form-control-sm">';
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

    public function xmlToStr( $xml )
    {
        $output = '';
        $results = simplexml_load_string( $xml );

        foreach ( $results->questions->children() as $question )
        {
            $output .=  $question->direction->text . chr( 13 ) . chr( 10 );
            $counter = 0;
            $selected = $question->answers['userAnswerIndex'];

            foreach ($question->answers->children() as $answer)
            {
                $output .= $answer->text;

                if( $selected == $counter )
                    $output .= ' - выбрал пользователь ';

                $output .= chr( 13 ) . chr( 10 );
                $counter++;
            }

            $output .= chr( 13 ) . chr( 10 );
        }

        return $output;
    }

    protected function download()
    {
      add_action('plugins_loaded', function()
      {
          if( !isset( $_POST['wpispringselectpost'] ) )
            if( empty( $_POST['wpispringselectpost'] ) )
                return '';

          if ( wp_verify_nonce( $_POST['wpispringSettingsNonce'], 'wpispringSettingsNonce-wpnp' ) )
          {
              $post_id = $_POST['wpispringselectpost'];
              $path = plugin_dir_path(__FILE__);

              $spreadsheet = new Spreadsheet;
              $worksheet = $spreadsheet->getSheet(0);
              $worksheet->setTitle('Модули');
              $header_row = 1;

              $header = [
                 'Моудль id',
                 'Название модуля',
                 'Название теста',
                 'Очки',
                 'Дата ответа',
                 'Результаты',
                 'Юзер id',
                 'Дата регистрации',
                 'Телефон',
                 'Email',
                 'Фамилия',
                 'Имя',
                 'Отчество',
                 'Пол',
                 'Страна',
                 'Регион',
                 'Город',
                 'Основная специальность',
                 'Место работы',
                 'Должность',
              ];

              foreach ($header as $key => $value)
                  $worksheet->getCellByColumnAndRow($key + 1, $header_row)->setValueExplicit($value, DataType::TYPE_STRING);

              $row_ref = 2;

              $results = $this->tableMananger->resultTable->GetByPost( $post_id );

              foreach ( $results as $result )
              {
                  $row_user = array();
                  $user_meta = get_userdata( ( int ) $result['user_id'] );
                  $user = get_user_by('id', ( int )$result['user_id'] );

                  array_push(
                      $row_user,
                      $result['post_id'],
                      get_post( $result['post_id'] )->post_title,
                      $result['name'],
                      $result['score'],
                      $result['date'],
                      $this->xmlToStr( $result['results'] ),
                      $result['user_id'],
                      $user->user_registered,
                      $user_meta->mobile_number,
                      $user->user_email,
                      $user_meta->last_name,
                      $user_meta->first_name,
                      $user_meta->patronymic,
                      ( empty( $user_meta->sex[0] ) ? 'Женский' : $user_meta->sex[0] ),
                      $user_meta->country,
                      ( is_array( $user_meta->region ) ? $user_meta->region[0] : $user_meta->region ),
                      ( $user_meta->city === 'Другой' ? $user_meta->city_manual : $user_meta->city ),
                      $user_meta->speciality,
                      ( $user_meta->workplace === 'Другое' || empty( $user_meta->workplace ) ? $user_meta->workplace_manual : $user_meta->workplace ),
                      $user_meta->post_in_workplace
                  );

                  foreach ($row_user as $key => $value)
                      $worksheet->getCellByColumnAndRow($key + 1, $row_ref)->setValueExplicit($value, DataType::TYPE_STRING);

                  $row_ref++;
              }

              if (!file_exists($path . 'temp/'))
              {
                  if (!mkdir($path . 'temp/'))
                  {
                      echo 'Нет доступа к временной директории. Пожалуйста, обратитесь к администратору.';
                      return;
                  }
              }

              $ch_arr = array_merge(range('a', 'z'), range(0, 9));

              do
              {
                  $filename = '';

                  for ($i = 0; $i < 32; $i++)
                      $filename .= $ch_arr[rand(0, count($ch_arr) - 1)];

                  $filename .= '.xlsx';
              }
              while (file_exists($path.'temp/'.$filename));

              $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
              $writer->save($path.'temp/'.$filename);
              unset($spreadsheet);
              unset($writer);
              $file = file_get_contents($path.'temp/'.$filename);
              unlink($path.'temp/'.$filename);
              header('Content-type: application; charset=utf-8');
              header('Content-disposition: attachment; filename=statistics.xlsx');
              echo $file;
              die;
          }
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
