<?php
if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/cextras_gerer');
include_spip('owurl_sur_tout');
       
function owurl_upgrade($nom_meta_base_version,$version_cible)
{
  $champs = owurl_declarer_champs_extras();
  installer_champs_extras($champs, $nom_meta_base_version, $version_cible);
}

function owurl_vider_tables($nom_meta_base_version)
{
  $champs = owurl_declarer_champs_extras();
  desinstaller_champs_extras($champs, $nom_meta_base_version);
}
