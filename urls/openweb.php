<?php

define('URLS_OPENWEB_EXEMPLE', '/articles/xhtml_une_heure');

/**
 * Fonction appelée par SPIP pour deux tâches :
 * - soit générer une adresse pour une ressource donnée (dans ce cas, $i 
 *   est un entier),
 * - soit retrouver une ressource à partir de son adresse.
 *
 * On coupe le boulot en deux :
 * - la génération sera prise en charge par la fonction "_generer_url_openweb",
 * - la recherche à partir de l'adresse par la fonction "_analyser_url_openweb".
 */
function urls_openweb_dist($i, &$entite, $args='', $ancre='') {
  if (is_numeric($i))
    return _generer_url_openweb($entite, $i, $args, $ancre);

  return _analyser_url_openweb();
}

function _analyser_url_openweb() {
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

  // Sinon, quand on ne retrouve rien en cache, on analyse l'adresse à 
  // la main.
  $contexte = array();
  $entite = '404';
  if(preg_match('!^openwebgroup/bios/(.+)$!', $url_openweb, $match_result)) {
    if($row = sql_fetsel('id_auteur', 'spip_auteurs',
      'ow_url='.sql_quote($match_result[1]))) {
      $contexte['id_auteur'] = $row['id_auteur'];
      $entite = 'auteur';
    }
  }
  elseif(preg_match('!^(?P<rubrique>[^/]*)(/(?P<article>[^/]*)/?)?$!', $url_openweb, $match_result))
  {
    if(!empty($match_result['article'])) {
      if($row = sql_fetsel('id_article',
        'spip_articles AS A JOIN spip_rubriques AS R ON A.id_rubrique=R.id_rubrique',
        'A.ow_url='.sql_quote($match_result['article']).
        ' AND R.ow_url='.sql_quote($match_result['rubrique'])))
      {
        $contexte['id_article'] = $row['id_article'];
        $entite = 'article';
      }
    }
    else {
      if($row = sql_fetsel('id_rubrique', 'spip_rubriques AS R',
        'R.ow_url='.sql_quote($match_result['rubrique'])))
      {
        $contexte['id_rubrique'] = $row['id_rubrique'];
        $entite = 'rubrique';
      }
      elseif($row = sql_fetsel('id_mot', 'spip_mots AS M',
        'M.ow_url='.sql_quote($match_result['rubrique'])))
      {
        $contexte['id_mot'] = $row['id_rubrique'];
        $entite = 'mot';
      }
    }
  }

  return array($contexte, $entite, null, null);
}

// Création d'une URL Openweb à partir du type et de l'identifiant de 
// l'objet.
function _generer_url_openweb($type, $id_objet, $args='', $ancre='') {
  if($type == 'document') {
    include_spip('inc/documents');
    return generer_url_document_dist($id, $args, $ancre);
  }

  $url_openweb = declarer_url_openweb($type, $id_objet);

  if($url_openweb === false) // Objet inconnu, au revoir.
    return '';

  // Lorsque l'objet est connu, mais que l'on n'a pas su générer 
  // l'adresse, on génère à la place une URL de base SPIP
  if(!$url_openweb) {
    include_spip('base/connect_sql');
    $id_type = id_table_objet($type);
    $url_openweb = _DIR_RACINE . get_spip_script('./')."?"._SPIP_PAGE."=$type&$id_type=$id_objet";
  }

  // Enfin, on ajoute les arguments et l'ancre
  if($args)
    $url_openweb .= ((strpos($url_openweb, '?') === false) ? '?' : '&') . $args;

  if($ancre)
    $url_openweb .= "#$ancre";

  return $url_openweb;
}

function declarer_url_openweb($type, $id_objet) {
  // On commence par récupérer la table qui stocke les objets de type $type, 
  // afin de connaître le nom de la colonne qui contient les id de 
  // $type.
  $trouver_table = charger_fonction('trouver_table', 'base');
  $desc = $trouver_table(table_objet($type));
  $table = $desc['table'];
  $col_id =  @$desc['key']["PRIMARY KEY"];
  if (!$col_id) return false; // Quand $type ne reference pas une table

  $id_objet = intval($id_objet);

  // On va ensuite chercher l'URL correspondante dans spip_urls
  $row = sql_fetsel("U.url, U.date", "$table AS O LEFT JOIN spip_urls AS U ON (U.type='$type' AND U.id_objet=O.$col_id)", "O.$col_id=$id_objet", '', 'U.date DESC', 1);

  // Ce n'est pas un objet connu, au revoir.
  if(!$row)
    return false;

  // Si on a retrouvé une URL déjà générée, on est content et on ne la 
  // change pas. Cependant, si on invoque "voir en ligne" avec le droit 
  // de modifier l'URL, alors l'URL est recalculée.
  $url_openweb = $row['url'];
  $modifier_url = $GLOBALS['var_urls'];
  if($url_openweb and !$modifier_url) {
    return $url_openweb;
  }

  // Sinon on génère l'URL à la main, avec des variations selon le type 
  // de la ressource demandée.
  if($type == 'article') {
    // On récupère l'URL Openweb de sa rubrique
    $row = sql_fetsel('A.ow_url AS art_url, R.ow_url AS rub_url', 'spip_articles AS A LEFT JOIN spip_rubriques AS R ON (A.id_rubrique = R.id_rubrique)', "A.id_article=$id_objet", 
      '', '', 1);
    $new_url_openweb = $row['rub_url'].'/'.$row['art_url'];
  }

  elseif($type == 'auteur') {
    $row = sql_fetsel('ow_url', $table, "$col_id=$id_objet", '', '', 1);
    $new_url_openweb = 'openwebgroup/bios/'.$row['ow_url'];
  }

  elseif($type == 'forum') {
    //XXX pour l'instant, on ne sait pas gérer les forums
    //On renvoie une chaîne vide, ainsi SPIP générera les adresses par 
    //défaut.
    return "";
  }

  else {
    $row = sql_fetsel('ow_url', $table, "$col_id=$id_objet", '', '', 1);
    $new_url_openweb = $row['ow_url'];
  }

  // Maintenant, on vérifie s'il faut mettre à jour l'adresse stockée 
  // dans la table spip_urls, et si on a le droit de le faire.
  if($url_openweb == $new_url_openweb) {
    return $url_openweb;
  }
  // verifier l'autorisation, maintenant qu'on est sur qu'on va agir
  if ($modifier_url) {
    include_spip('inc/autoriser');
    $modifier_url = autoriser('modifierurl', $type, $id_objet);
  }

  // Verifier si l'utilisateur veut effectivement changer l'URL
  if ($modifier_url
    // AND CONFIRMER_MODIFIER_URL: pompé de propres, mais l'interface de 
    // confirmation n'existe pas encore.
    AND $url_openweb
    AND $new_url_openweb != preg_replace('/,.*/', '', $url_openweb))
    $confirmer = true;
  else
    $confirmer = false;

  if ($confirmer AND !_request('ok')) {
    die ("vous changez d'url ? $url_propre -&gt; $url");
  }

  $set = array('url' => $new_url_openweb, 'type' => $type, 'id_objet' => $id_objet);
  include_spip('action/editer_url');
  if (!url_insert($set,$confirmer,_url_propres_sep_id))
    return $url_openweb; //serveur out ? retourner au mieux

  return $set['url'];
}
