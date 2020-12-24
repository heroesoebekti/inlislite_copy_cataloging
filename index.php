<?php
/**
 * @Created by          : Heru Subekti (heroe.soebekti@gmail.com)
 * @Date                : 23/12/20
 * @File name           : index.php
 */

// key to authenticate
defined('INDEX_AUTH') OR die('Direct access not allowed!');

$php_self = $_SERVER['PHP_SELF'].'?'.http_build_query($_GET);

// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB . 'admin/default/session.inc.php';
require SB . 'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';
require MDLBS.'system/biblio_indexer.inc.php';

include 'lib/simple_html_dom.php';
include 'lib/function.inc.php';
require 'server_list.inc.php';

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');

if (!$can_read) {
  die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

$plugin_name = 'inlislite_copy_cataloging';

if (isset($_POST['saveZ']) AND isset($_SESSION['marc_url'])) {
    require MDLBS.'bibliography/biblio_utils.inc.php';
    $place_cache = '';
    $publ_cache = '';
    $author_cache = '';
    $subject_cache = '';
    foreach ($_POST['zrecord'] as $id) {
      $_d = getMARC($id,$_SESSION['marc_url']);
      $sql_op = new simbio_dbop($dbs);
      $title = $_d['title'].' '.($_d['subtitle']??'');
      $data['title'] = preg_replace("/(?<=)+(\/)$/m", "", trim($dbs->escape_string(strip_tags($title))));
      $data['sor'] = preg_replace("/(?<=)+(\/)$/m", "", trim($dbs->escape_string(strip_tags($_d['sor']))));
      if ($_d['publish_place']) {
        $data['publish_place_id'] = utility::getID($dbs, 'mst_place', 'place_id', 'place_name', preg_replace("~[^a-zA-Z0-9\s]~", "", $_d['publish_place']), $place_cache);
      } 
      if ($_d['publisher']) {
        $data['publisher_id'] = utility::getID($dbs, 'mst_publisher', 'publisher_id', 'publisher_name', preg_replace("~[^a-zA-Z0-9\s]~", "", $_d['publisher']), $publ_cache);
      }    
      $data['language_id'] = 'id';
      $data['gmd_id'] = 1; 
      $data['publish_year'] = preg_replace("~[^a-zA-Z0-9\s]~", "", $_d['publish_year']);
      $data['edition'] = $_d['edition'];
      $data['isbn_issn'] = $_d['isbn'];
      $data['uid'] = $_SESSION['uid'];      
      $data['collation'] = $_d['collation']['size'].($_d['collation']['other']??'').($_d['collation']['dimension']??'');
      $data['call_number'] = $_d['call_number'];
      $data['classification'] = $_d['classification'];
      $data['notes'] = $_d['notes'];
      $data['opac_hide'] = 0;
      $data['promoted'] = 0;
      $data['labels'] = '';
      $data['spec_detail_info'] = '';
      $data['series_title'] = $_d['series_title'];
      $data['input_date'] = date('Y-m-d H:i:s');
      $data['last_update'] = date('Y-m-d H:i:s');

      if($_d['image']){
        $url_image = urldecode($_d['image']);
        $data_image = pathinfo($url_image);
        $image_name = date("YmdHis").'_'.$data_image['basename'];
        $image_path = IMGBS . 'docs' . DS . $image_name;
        $arrContextOptions = array(
            "ssl" => array(
              "verify_peer" => false,
              "verify_peer_name" => false,
            ),
          );
        $img = file_put_contents($image_path, file_get_contents($url_image, false, stream_context_create($arrContextOptions)));
        if($img){
          $data['image'] = $image_name;    
        }
      }

      $insert = $sql_op->insert('biblio', $data); 
      echo '<p>'.$sql_op->error.'</p><p>&nbsp;</p>';
      $biblio_id = $sql_op->insert_id;  

      foreach ($_d['authors'] as $key => $value) {
        $author_id = getAuthorID($value['author_name'], $value['type'], $author_cache);
        @$dbs->query("INSERT IGNORE INTO biblio_author (biblio_id, author_id, level) VALUES ($biblio_id, $author_id, '".$value['level']."')");
      }
      foreach ($_d['topics'] as $key => $value) {
        $subject_id = getSubjectID($value['topic_name'], $value['type'], $subject_cache);
        @$dbs->query("INSERT IGNORE INTO biblio_topic (biblio_id, topic_id, level) VALUES ($biblio_id, $subject_id,'".$value['type']."')");
      }

      if ($biblio_id) {
          // create biblio_indexer class instance
          $indexer = new biblio_indexer($dbs);
          // update index
          $indexer->makeIndex($biblio_id);
          // write to logs
          utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'bibliography',sprintf(__('%s insert bibliographic data from Inlislite MARC-XML service (server: %s) with title (%s) and biblio_id (%s)'),$_SESSION['realname'],$zserver,$data['title'],$biblio_id), 'Inlislite MARC-XML', 'Add');         
          $r++;
      }
    }  
    echo '<script>parent.toastr.success(" records inserted into the database", "MARC XML");</script>';
}
?>

<div class="menuBox">
<div class="menuBoxInner biblioIcon">
  <div class="per_title">
      <h2><?php echo __('Search/Retrieve from Inlislite via MARC-XML'); ?></h2>
    </div>
    <div class="sub_section">
    <form name="search" id="search" action="<?= $php_self; ?>" method="get" class="form-inline"><?php echo __('Search'); ?>
    <input type="text" name="keywords" id="keywords" class="form-control col-md-3" value="<?= ($_GET['keywords']??'')?>" />
        <input type="hidden" name="search" value="search" />
        <input type="hidden" name="in_search" value="1" />
    <select name="field" class="form-control ">
      <option value="Semua Ruas"><?php echo __('All fields'); ?></option>
      <option value="ISBN"><?php echo __('ISBN/ISSN'); ?></option>
      <option value="Judul"><?php echo __('Title/Series Title'); ?></option>
      <option value="Pengarang"><?php echo __('Authors'); ?></option>
    </select>
    <?php echo __('Inlislite Server'); ?>
    <select name="marc_XML_source" class="form-control">
      <?php foreach ($sysconf['marc_XML_source'] as $serverid => $sru_source) { 
        if($_GET['marc_XML_source'] == $sru_source['uri']){
          echo '<option value="'.$sru_source['uri'].'" selected="true">'.$sru_source['desc'].'</option>';  
        }else{
          echo '<option value="'.$sru_source['uri'].'">'.$sru_source['desc'].'</option>';            
        }
      } ?>
    </select>
    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default" />
    </form>
    </div>
    <div class="infoBox"><?php echo __('* Please make sure you have a working Internet connection.'); ?></div>
</div>
</div>
<?php

if(isset($_GET['in_search'])){

    $result = getData($_GET['keywords'],$_GET['marc_XML_source'],$_GET['page'],$_GET['field']);
    $_SESSION['marc_url'] = $_GET['marc_XML_source'];

    if($result){

        echo '<div class="alert alert-info">'.sprintf(__('Have found <strong>%s</strong> records from searched keywords <strong>%s</strong> from <i>%s</i>'),$result['total_records'],$result['keywords'],$result['url']).'</div>';
        echo '<div class="p-3" style="padding: 0px 0px 0px 1rem !important;"><span id="pagingBox"></span></div>';

        $table = new simbio_table();
        $table->table_attr = 'align="center" class="s-table table" cellpadding="5" cellspacing="0"';
        echo  '<div class="p-3">
                <input value="'.__('Check All').'" class="check-all button btn btn-default" type="button"> 
                <input value="'.__('Uncheck All').'" class="uncheck-all button btn btn-default" type="button">
                <input type="submit" name="saveZ" class="s-btn btn btn-success save" value="' . __('Save Marc Records to Database') . '" /></div>';
        // table header
        $table->setHeader(array(__('Select'),__('Title'),__('Publishing Place'),__('Publisher'),__('Publishing Year')));
        $table->table_header_attr = 'class="dataListHeader alterCell font-weight-bold"';

        $i = 0;
        foreach ($result['data'] as $key=> $value) {

          $url_image = $value['img'];
          if($value['img']==NULL){
              $url_image = '../images/default/image.png';
          }

          $cb = '<input type="checkbox" name="zrecord['.$value['id'].']" value="'.$value['id'].'#'.urlencode($url_image).'">';
          $title_content = '<div class="media">
                        <img class="mr-3 rounded" src="'.$url_image.'" alt="cover image" style="height:70px;">
                        <div class="media-body">
                          <div class="title">'.stripslashes($value['title']).'</div><div class="authors">'.$value['author'].'</div>
                        </div>
                      </div>';
          
          $table->appendTableRow(array($cb,$title_content,$value['publish_place'],$value['publisher'],$value['publish_year']));
          // set cell attribute
          $row_class = ($i%2 == 0)?'alterCell':'alterCell2';
          $table->setCellAttr($i, 0, ' valign="top" style="width: 5px;"');
          $table->setCellAttr($i, 1, ' valign="top" style="width: auto;"');
          $table->setCellAttr($i, 2, ' valign="top" style="width: auto;"');
          $table->setCellAttr($i, 2, ' valign="top" style="width: auto;"');
          $table->setCellAttr($i, 2, ' valign="top" style="width: auto;"');
          $i++;

        }
        echo $table->printTable(); 
        $page = new simbio_paging();
        echo '<script type="text/javascript">'."\n";
        echo 'parent.$(\'#pagingBox\').html(\''.str_replace(array("\n", "\r", "\t"), '', $page->paging($result['total_records'],10)).'\');'."\n";
        echo '</script>';
      ?>
      <script>
          $('.save').on('click', function (e) {
          var zrecord = {};
          var uri = '<?php echo $php_self; ?>';
          $("input[type=checkbox]:checked").each(function() {
             zrecord[$(this).val()] = $(this).val();
          });

          $.ajax({
                  url: uri,
                  type: 'post',
                  data: {saveZ: true,zrecord}
              })
                .done(function (msg) {
                  //console.log(zrecord);
                  parent.toastr.success(Object.keys(zrecord).length+" records inserted into the database", "MARC XML");
                  $('[type=checkbox]').prop('checked', false).parents('tr').removeClass('alterCell highlighted');
              })
          })
          $(".uncheck-all").on('click',function (e){
              e.preventDefault()
              $('[type=checkbox]').prop('checked', false).parents('tr').removeClass('alterCell highlighted');
          });
          $(".check-all").on('click',function (e){
              e.preventDefault()
              $('[type=checkbox]').prop('checked', true).parents('tr').addClass('alterCell highlighted');
          });

          $('td').click( function() {
            if($(this).parents('tr').hasClass('alterCell')){
              $(this).parents('tr').removeClass('alterCell highlighted').find(':checkbox').prop('checked',false);
            }else{
              $(this).parents('tr').addClass('alterCell highlighted').find(':checkbox').prop('checked',true);
            }
          });
      </script>
      <?php
      exit();
    }
    else{
      echo '<div class="alert alert-danger">'.__('No Results Found!').'</div>';
      exit();
    }
}