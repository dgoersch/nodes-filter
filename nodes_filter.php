 <?php
/////////////////////////////////////////////////////////////////////////
//  nodes_filter.php                                                   //
//  filter nodes.json to match given part of the names                 //
//  to present all nodes of a cummunity on the ff-map                  //
//  by Domnique Görsch <ff@dgoersch.info>                              //
//                                                                     //
//  Dieses Material steht unter der Creative-Commons-Lizenz            //
//  "Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen     //
//  Bedingungen 4.0 International".                                    //
//  Um eine Kopie dieser Lizenz zu sehen, besuchen Sie                 //
//  http://creativecommons.org/licenses/by-nc-sa/4.0/.                 //
/////////////////////////////////////////////////////////////////////////

error_reporting(E_ALL ^ E_NOTICE);                                                           // suppress notices

$src_url    = "http://map.freifunk-ruhrgebiet.de/nodes.json";                                // source for nodes.json
$json_file  = "/var/customers/webs/ffmg/map/nodes.json";                                     // target file

$src_json   = file_get_contents($src_url);                                                   // get nodes from ruhrgebiet
$src_arr    = json_decode($src_json,TRUE);                                                   // and convert to array
$filter_str = "FF-MG-";                                                                      // filterstring


$ids_arr  = array();
$macs_arr = array();
foreach($src_arr['nodes'] as $node_arr) {                                                    // run through nodes
  $node = new stdClass();
  $node = json_decode(json_encode($node_arr), FALSE);

  if($node->name &&                                                                          // if node has a name
    strtoupper(substr($node->name,0,strlen($filter_str))) == strtoupper($filter_str)) {      // and filterstring in it 
    $ids_arr[] = $node->id;                                                                  // push id to array

    $node_macs = explode(",",$node->macs);                                                   // extract macs from node
    foreach($node_macs as $mac) {
      $macs_arr[] = trim($mac);                                                              // and push them to array
    }
  }
}

$nodes_arr = array();
$idxs_arr  = array();
$idx = 0; $hit = 0;

foreach($src_arr['nodes'] as $node_arr) {                                                    // run through nodes
  $node = new stdClass();
  $node = json_decode(json_encode($node_arr), FALSE);

  if(($node->name && $node->flags->legacy) ||                                                // if node has a name and is legacy
     strpos_array($node->id,$ids_arr) > -1 ||                                                // or its id is in ids array
     (strpos_array($node->id,$macs_arr) > -1 && $node->flags->client)) {                     // or its id is in macs array nad node is a client 
    $nodes_arr[]    = $node;                                                                 // push node to array
    $idxs_arr[$idx] = $hit;                                                                  // save old position in list
    $hit++;
  }
  $idx++;
}

$links_arr = array();
foreach($src_arr['links'] as $link_arr) {                                                    // run through links
  $link = new stdClass();
  $link = json_decode(json_encode($link_arr), FALSE);

  $link->source = $idxs_arr[$link->source];                                                  // replace source with new position
  $link->target = $idxs_arr[$link->target];                                                  // replace target with new position
  if($link->source != "" && $link->target != "") $links_arr[] = $link;                       // if source or target not NULL push link to array
}

$output_arr = array("nodes" => $nodes_arr,"meta" => $src_arr['meta'],"links" => $links_arr); // build output array from new nodes array, source meta array and new links array

//header('Content-type: application/json');
//echo json_encode($output_arr,JSON_UNESCAPED_SLASHES);                                        // print output as json object

file_put_contents($json_file,json_encode($output_arr,JSON_UNESCAPED_SLASHES));               // output as json object to nodes.json


// strpos() replacement with array as needles
// from http://php.net/manual/de/function.strpos.php
function strpos_array($haystack, $needles) {
    if ( is_array($needles) ) {
        foreach ($needles as $str) {
            if ( is_array($str) ) {
                $pos = strpos_array($haystack, $str);
            } else {
                $pos = strpos($haystack, $str);
            }
            if ($pos !== FALSE) {
                return $pos;
            }
        }
    } else {
        return strpos($haystack, $needles);
    }
}

?>
