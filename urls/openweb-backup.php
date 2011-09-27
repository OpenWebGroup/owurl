<?php

define('URLS_OPENWEB_EXEMPLE', '/articles/xhtml_une_heure');

function urls_openweb_dist($i, &$entite, $args='', $ancre='') {
  if (is_numeric($i))
    return _generer_url_openweb($entite, $i, $args, $ancre);

  // Chercher les valeurs d'environnement qui indiquent l'URL Openweb
  if (isset($_SERVER['REDIRECT_url_openweb']))
    $url_openweb = $_SERVER['REDIRECT_url_openweb'];
  elseif (isset($_ENV['url_openweb']))
    $url_openweb = $_ENV['url_openweb'];
  else {
    return;
  }
  $url_openweb = trim($url_openweb, '/');

  // On veut retrouver une ressource à partir de son URL -> regarder 
  // dans le cache spip_urls.
  $row = sql_fetsel('id_objet, type', 'spip_urls', 
    'url='.sql_quote($url_openweb), '', 'date DESC', 1);
  if($row) {
    $type = $row['type'];
    $col_id = id_table_objet($type);
    $contexte[$col_id] = $row['id_objet'];
    return array($contexte, $type, null, null);
  }
}

// Création d'une URL Openweb à partir du type et de l'identifiant de 
// l'objet.
function _generer_url_openweb($type, $id_objet, $args='', $ancre='') {
  // On commence par récupérer la table qui stocke les objets de type $type.
  $trouver_table = charger_fonction('trouver_table', 'base');
  $desc = $trouver_table(table_objet($type));
  $table = $desc['table'];
  $col_id =  @$desc['key']["PRIMARY KEY"];
  if (!$col_id) return false; // Quand $type ne reference pas une table

  $id_objet = intval($id_objet);

  // On va ensuite chercher l'URL correspondante dans spip_urls
  $row = sql_fetsel("U.url AS url",
    "$table AS O JOIN spip_urls AS U ON (U.type='$type' AND 
    U.id_objet=O.$col_id)", "O.$col_id=$id_objet", '', 'U.date DESC', 
    1);

  if($row)
    $url = $row['url'];
  // Sinon on génère l'URL à la main
  elseif($type == 'article') {
    // On récupère l'URL Openweb de sa rubrique
    $row = sql_fetsel('A.ow_url AS art_url, R.ow_url AS rub_url', 'spip_articles AS A LEFT JOIN spip_rubriques AS R ON (A.id_rubrique = R.id_rubrique)', "A.id_article=$id_objet", 
      '', '', 1);
    $url = $row['rub_url'].'/'.$row['art_url'];
    sauver_url_openweb($type, $id_objet, $url);
  }
  elseif($type == 'document') {
    include_spip('inc/documents');
    return generer_url_document_dist($id, $args, $ancre);
  }
  elseif($type == 'auteur') {
    $row = sql_fetsel('ow_url', $table, "$col_id=$id_objet", '', '', 1);
    $url = 'openwebgroup/bios/'.$row['ow_url'];
    sauver_url_openweb($type, $id_objet, $url);
  }
  elseif($type == 'forum') {
    // Pour un forum, on cherche l'URL de l'article associé, et on lui 
    // accroche /forum à la fin.
    $row = sql_fetsel('A.ow_url AS art_url, R.ow_url AS rub_url', 'spip_articles AS A LEFT JOIN spip_rubriques AS R ON (A.id_rubrique = R.id_rubrique)', "A.id_article=".intval($_SERVER['id_article']), 
      '', '', 1);
    $url = $row['rub_url'].'/'.$row['art_url'].'/forum';
    sauver_url_openweb($type, $id_objet, $url);
  }
  else {
    $row = sql_fetsel('ow_url', $table, "$col_id=$id_objet", '', '', 1);
    $url = $row['ow_url'];
    sauver_url_openweb($type, $id_objet, $url);
  }

  // On ajoute l'ancre et les arguments
  if($args)
    $url .= ((strpos($url, '?') === false) ? '?' : '&') . $args;
  if($ancre)
    $url .= "#$ancre";

  return $url;
}

// Fonction qui insère l'URL d'un objet dans la table spip_urls 
// (éventuellement en la mettant à jour).
function sauver_url_openweb($type, $id_objet, $url) {
  $set = array('url' => $url, 'type' => $type, 'id_objet' => $id_objet);
  if(@sql_insertq('spip_urls', $set) <= 0) {
    // XXX L'insertion échoue si l'URL existe déjà. Traiter ce cas 
    // (piquer une URL à une autre ressource).
  }

  sql_updateq('spip_urls', array('date' => date('Y-m-d H:i:s')), 'url='.sql_quote($set['url']));
  spip_log("Creation de l'url openweb '" . $set['url'] . "' pour $type=$id_objet");
}
