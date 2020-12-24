<?php
session_start();

function getMARC($dataID,$url){
	$id = explode('#', $dataID);
    $url = file_get_contents($url.'/opac/detail-opac/download?id='.$id[0].'&type=MARCXML');
    $xmldata = simplexml_load_string($url);
    foreach($xmldata->children() AS $field ){
    $data = array();  
    $data['image'] = $id[1];
      foreach ($field->datafield as $key => $value) {
          $tags = $value->attributes()->tag[0]; 
           switch ($tags) {

              case '020': # ISBN
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['isbn'] = (string)$subf[0]; 
                      break;                                   
                  }     
                }              
                break;

              case '041': # Language Code
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['language'] = (string)$subf[0]; 
                      break;                                   
                  }     
                }              
                break;

              case '082': # DDC Call Number
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['classification'] = (string)$subf[0]; 
                      break;                                   
                  }     
                }              
                break;

              case '084': # OTHER CLASSIFICATION NUMBER
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['call_number'] = (string)$subf[0]; 
                      break;                                   
                  }     
                }              
                break;

              case '245': # Title Statement
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['title'] = (string)$subf[0]; //title
                      break;
                    case 'b':
                     $data['subtitle'] = (string)$subf[0]; //subtitle
                      break;                       
                    case 'c':
                     $data['sor'] = (string)$subf[0]; //sor
                      break; 
                    case 'h':
                     $data['gmd'] = (string)$subf[0]; //gmd
                      break;                    
                  }     
                }              
                break;

              case '250': # Edition Statement
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['edition'] = (string)$subf[0]; 
                      break;                                   
                  }     
                }              
                break;

              case '260': # Publication, Distribution, etc. [Imprint]
              case '264': # Source of acquisition / Subscription Address
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a': # publishing place
                      $data['publish_place'] = (string)$subf[0]; 
                      break;
                    case 'b': # publisher
                     $data['publisher'] = (string)$subf[0]; 
                      break;                       
                    case 'c': # publishing year
                     $data['publish_year'] = (string)$subf[0]; 
                      break;                    
                  }     
                }              
                break;

              case '300': # Physical Description
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['collation']['size'] = (string)$subf[0]; # Ukuran bahan perpustakaan (mis. hlm., jil.) (NR)
                      break;
                    case 'b':
                     $data['collation']['other'] = (string)$subf[0]; # Keterangan fisik lainnya (mis. ilus. untuk ilustrasi) (NR)
                      break;                       
                    case 'c':
                     $data['collation']['dimension'] = (string)$subf[0]; # Dimensi (mis. 22 cm.) (NR)
                      break;                    
                    case 'e':
                     $data['collation']['include'] = (string)$subf[0]; # Bahan sertaan (NR)
                      break;  
                  }     
                }              
                break;

              case '520': # Note
              case '505': # Restrictions on Access Note
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['notes'] = (string)$subf[0]; //notes
                      break;                  
                  }     
                }              
                break;

              # Main Entry
              case '100': # Main Entry - Personal Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['authors'][] = array('type'=>'p','level' => '1', 'author_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '110': # Main Entry - Corporate Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['authors'][] = array('type'=>'o','level' => '1', 'author_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '111': # Main Entry - Meeting Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['authors'][] = array('type'=>'p','level' => '1', 'author_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;
              #=======

              case '440': # SERIES STATEMENT/ADDED ENTRY-TITLE
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['series_title'][] = (string)$subf[0]; 
                      break;                 
                  }     
                }              
                break;
              #=======

              # Subject Access Fields  
              case '600': # Subject Added Entry - Personal Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['topics'][] = array('type'=>'n','topic_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '610': # Subject Added Entry - Corporate Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['topics'][] = array('type'=>'o','topic_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '611': # Subject Added Entry – Meeting Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['topics'][] = array('type'=>'c','topic_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '650': # Subject Added Entry - Topical Term
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['topics'][] = array('type'=>'t','topic_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '651': # Subject Added Entry – Geographic Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['topics'][] = array('type'=>'g','topic_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;
              # ======================

              # Added Entries  
              case '700': # Added Entry - Personal Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['authors'][] = array('type'=>'p', 'level' => '2', 'author_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '710': # Added Entry - Corporate Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['authors'][] = array('type'=>'o', 'level' => '2', 'author_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;

              case '711': # Added Entry - Meeting Name
                foreach ($value->subfield as $sf => $subf) {
                  $code = (string)$subf->attributes()->code[0];
                  switch ($code) {
                    case 'a':
                      $data['authors'][] = array('type'=>'c','level' => '2', 'author_name'=>(string)$subf[0]); 
                      break;                 
                  }     
                }              
                break;        
              # ========================        
            }  
      }
  }    
  return $data;
}


function getData($keyword, $url,$page = '1'){
 
  //remove temp cookie
  if($_SESSION['keyword']!=$keyword && file_exists(UPLOAD.'cache/cache')){
  	unlink(UPLOAD.'cache/cache');
  }		

  $html_source = $url.'/opac/pencarian-sederhana?action=pencarianSederhana&katakunci='.urlencode($keyword).'&ruas=Judul&bahan=Semua+Jenis+Bahan&limit=10&page='.($page??1);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_URL, $html_source);
  curl_setopt($curl, CURLOPT_REFERER, $html_source);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_ENCODING, '');
  curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
  curl_setopt ($curl,CURLOPT_CONNECTTIMEOUT,120);
  curl_setopt ($curl,CURLOPT_TIMEOUT,120);
  curl_setopt ($curl,CURLOPT_MAXREDIRS,10);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_COOKIEJAR, UPLOAD . "cache/cache");  
  curl_setopt($curl, CURLOPT_COOKIEFILE, UPLOAD . "cache/cache");
  $str = curl_exec($curl);
  curl_close($curl);

  // Create a DOM object
  $html = new simple_html_dom();
  $html->load($str);

  //get page 
  preg_match_all('/(?<= dari )(.*)(?= hasil )/m', $html, $matches, PREG_SET_ORDER, 0);
  $total = strip_tags($matches[0][0]);

  //get biblioID
  $biblioID = [];
  $a = 0;
  foreach($html->find('input[name=catalogID]') as $e) {
    $biblioID[$a] = $e->value;   
    $a++;
  }

  //get image cover
  $img_url = [];
  foreach ($html->find('a') as $img) {
    foreach ($img->find('img') as $pic) {
      $img = $pic->getAttribute('src');
      if(preg_match('/tdkada|logo/i', $img)){
          $img = NULL;    
      }
      $img_url[] = str_replace('..',$url,$img);
    }
  }

  $data = [];
  $n = 0;
  foreach ($html->find('table[class=table2]') as $key) {

    foreach ($key->find('[class=topnav-content]') as $judul) {
       $judul = $judul->innertext;
    }

    $t = [];
    $t['id'] = $biblioID[$n];
    $title = explode('/', strip_tags($judul));
    $t['title'] = $title[0];
    $t['img'] = $img_url[$n];

    foreach ($key->find('tr') as $tr) {
      $data_tabel = $tr->outertext;
      if(preg_match('/Pengarang/i', $data_tabel)){
        $val = explode('</td>', $data_tabel);
        $t['author'] =  trim(preg_replace('/\t/i','',strip_tags($val[1])));
      }
      if(preg_match('/Penerbitan/i', $data_tabel)){      	
        $val = explode('</td>', $data_tabel);
        $impresum =trim(preg_replace('/\t|$a|$b|$c/i','',strip_tags($val[1])));
        $aa = preg_split('/\:|\,/i', $impresum);
        $t['publish_place'] = $aa[0]??NULL;
        $t['publisher'] = $aa[1]??NULL;
        $t['publish_year'] = $aa[2]??NULL;  
      } 
    }
    $data[] = $t;
    $n++;
  }
  $data = array_combine($biblioID, $data);
  $_SESSION['keyword'] = $keyword;
  $result = array(
    'url' => $url,
    'source'=>$html_source, 
    'current_page' => $page, 
    'total_records'=>$total, 
    'keywords'=> $keyword,
    'data'=> $data);
  
  return $result;
}