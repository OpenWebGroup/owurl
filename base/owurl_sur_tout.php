<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

function owurl_declarer_champs_extras($champs = array())
{
  $champs[] = new ChampExtra(array(
    'table' => 'rubrique',
    'champ' => 'ow_url',
    'label' => 'URL Openweb de la rubrique',
    'precisions' => '',
    'obligatoire' => true,
    'rechercher' => true,
    'type' => 'text',
    'sql' => 'VARCHAR (30) NOT NULL',
    ));

  $champs[] = new ChampExtra(array(
    'table' => 'article',
    'champ' => 'ow_url',
    'label' => 'URL Openweb de l\'article',
    'precisions' => '',
    'obligatoire' => true,
    'rechercher' => true,
    'type' => 'text',
    'sql' => 'VARCHAR (30) NOT NULL',
    ));

  $champs[] = new ChampExtra(array(
    'table' => 'auteur',
    'champ' => 'ow_url',
    'label' => 'URL Openweb de l\'auteur',
    'precisions' => '',
    'obligatoire' => true,
    'rechercher' => true,
    'type' => 'text',
    'sql' => 'VARCHAR (30) NOT NULL',
    ));

  $champs[] = new ChampExtra(array(
    'table' => 'mot',
    'champ' => 'ow_url',
    'label' => 'URL Openweb du mot clef',
    'precisions' => '',
    'obligatoire' => true,
    'rechercher' => true,
    'type' => 'text',
    'sql' => 'VARCHAR (30) NOT NULL',
    ));

  return $champs;
}
