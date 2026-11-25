<?php
/**
 * Plugin Name: Twitch Musik – Client
 * Description: Musikwünsche per Twitch-Chat und Kanalpunkte auf der eigenen WordPress-Seite.
 * Version: 0.7.12
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
if(!defined('ABSPATH'))exit;
class TM_Client {
 const NS='twitch-musik-client/v1';
 static function boot(){add_action('admin_init',[__CLASS__,'connect']);add_action('admin_menu',[__CLASS__,'menu']);add_action('admin_footer',[__CLASS__,'external_viewer_menu']);add_action('admin_post_tmc_quick_action',[__CLASS__,'quick_action']);add_action('admin_post_tmc_list_control',[__CLASS__,'list_control']);add_action('admin_post_tmc_sync_reward',[__CLASS__,'sync_reward']);add_action('wp_ajax_tmc_refresh_queue',[__CLASS__,'refresh_queue']);add_action('rest_api_init',[__CLASS__,'routes']);add_shortcode('twitch_musikwuensche',[__CLASS__,'shortcode']);add_action('wp_enqueue_scripts',[__CLASS__,'assets']);register_activation_hook(__FILE__,[__CLASS__,'activate']);add_action('tmc_cleanup_played',[__CLASS__,'cleanup_played']);add_action('init',[__CLASS__,'ensure_schema']);register_deactivation_hook(__FILE__,[__CLASS__,'deactivate']);}
 static function table(){global $wpdb;return $wpdb->prefix.'tmc_songs';}
 static function activate(){global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=self::table();$c=$wpdb->get_charset_collate();dbDelta("CREATE TABLE $t (id bigint unsigned NOT NULL AUTO_INCREMENT, event_id varchar(120) NOT NULL, username varchar(100) NOT NULL, artist varchar(255) NOT NULL, title varchar(255) NOT NULL, cover_url text NULL, source varchar(20) NOT NULL, status varchar(20) NOT NULL DEFAULT 'waiting', created datetime NOT NULL, played_at datetime NULL, PRIMARY KEY (id), UNIQUE KEY event_id (event_id)) $c;");if(!get_option('tmc_secret'))add_option('tmc_secret',bin2hex(random_bytes(32)),'','no');update_option('tmc_schema_version','0.5.7');self::schedule_cleanup();self::cleanup_playlist();}
 static function schedule_cleanup(){if(!wp_next_scheduled('tmc_cleanup_played'))wp_schedule_event(time()+60,'hourly','tmc_cleanup_played');}
 static function deactivate(){wp_clear_scheduled_hook('tmc_cleanup_played');}
 static function ensure_schema(){if(get_option('tmc_schema_version')!=='0.5.7'){
  global $wpdb;$table=self::table();$exists=$wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM `'.esc_sql($table).'` LIKE %s','played_at'));
  if(!$exists){$wpdb->query('ALTER TABLE `'.esc_sql($table).'` ADD COLUMN played_at datetime NULL');}
  // Legacy played entries have no completion timestamp; begin their 12-hour period at migration.
  $wpdb->query($wpdb->prepare("UPDATE `$table` SET played_at=%s WHERE status='played' AND played_at IS NULL",current_time('mysql')));
  update_option('tmc_schema_version','0.5.7');
 }self::schedule_cleanup();self::cleanup_playlist();}
 // Die gesamte Playlist wird alle 12 Stunden ab dem ersten Start dieser Version geleert.
 // Unix-Zeitstempel vermeiden Zeitzonen-/Sommerzeitfehler.
  static function cleanup_playlist(){
   if(get_option('tmc_cleanup_mode','12h')==='keep')return;
   $period=12*HOUR_IN_SECONDS;
  $start=(int)get_option('tmc_playlist_cycle_start',0);
  if($start<=0){add_option('tmc_playlist_cycle_start',time(),'','no');return;}
  $now=time();
  if($now<$start+$period)return;
  // Verhindert gleichzeitige doppelte Bereinigungen durch Cron und Requests.
  if(!add_option('tmc_playlist_cleanup_lock',$now,'','no')){
   $locked=(int)get_option('tmc_playlist_cleanup_lock',0);
   if($locked && $locked>$now-120)return;
   delete_option('tmc_playlist_cleanup_lock');
   if(!add_option('tmc_playlist_cleanup_lock',$now,'','no'))return;
  }
  global $wpdb;
  $result=$wpdb->query('DELETE FROM '.self::table());
  if($result!==false){
   // Zeitplan bleibt fest, auch wenn WordPress-Cron verspätet ausgeführt wird.
   update_option('tmc_playlist_cycle_start',$start+(int)floor(($now-$start)/$period)*$period,false);
  }
  delete_option('tmc_playlist_cleanup_lock');
 }
 static function cleanup_played(){self::cleanup_playlist();}
 static function opts(){return get_option('tmc_settings',['mode'=>'both','command'=>'!wunsch','reward'=>'Musikwunsch','reward_cost'=>1000]);}
 static function list_control(){
  if(!current_user_can('manage_options'))wp_die('Keine Berechtigung.',403);
  check_admin_referer('tmc_list_control');
  $action=sanitize_key(wp_unslash($_POST['tmc_list_action']??''));
  if($action==='enable'||$action==='disable'){
   update_option('tmc_list_enabled',$action==='enable'?'yes':'no');
  }elseif($action==='clear'){
   global $wpdb;
   $wpdb->query('DELETE FROM '.self::table());
   update_option('tmc_playlist_cycle_start',time(),false);
  }else wp_die('Ungültige Aktion.',400);
  wp_safe_redirect(add_query_arg(['page'=>'tmc','tmc_list_result'=>$action],admin_url('admin.php')));
  exit;
 }
 static function menu(){add_menu_page('Twitch Musikwünsche','Twitch Musikwünsche','manage_options','tmc',[__CLASS__,'admin'],'dashicons-playlist-audio');add_submenu_page('tmc','Warteschlange und Einstellungen','Warteschlange und Einstellungen','manage_options','tmc',[__CLASS__,'admin']);add_submenu_page('tmc','Zuschaueransicht','Zuschaueransicht','manage_options','tmc-viewer',[__CLASS__,'admin']);}
 static function external_viewer_menu(){
  if(!current_user_can('manage_options'))return;
  $viewer_url=self::viewer_url();
  echo '<script>(function(){var link=document.querySelector("#adminmenu a[href*=tmc-viewer]");if(!link)return;link.href='.wp_json_encode(esc_url($viewer_url)).';link.target="_blank";link.rel="noopener noreferrer";link.setAttribute("aria-label","Zuschaueransicht (öffnet neuen Tab)");})();</script>';
 }
 static function viewer_url(){
  // Find the actual published page with the viewer shortcode, not just the site homepage.
  $pages=get_posts(['post_type'=>'page','post_status'=>'publish','posts_per_page'=>200,'orderby'=>'ID','order'=>'ASC']);
  foreach($pages as $page){if(has_shortcode($page->post_content,'twitch_musikwuensche'))return get_permalink($page);}
  $posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'orderby'=>'ID','order'=>'DESC']);
  foreach($posts as $post){if(has_shortcode($post->post_content,'twitch_musikwuensche'))return get_permalink($post);}
  return home_url('/');
 }
 static function admin(){if(!current_user_can('manage_options'))return;self::cleanup_played();global $wpdb;$o=self::opts();if(isset($_POST['tmc_save'])){check_admin_referer('tmc_save');$previous_cleanup_mode=get_option('tmc_cleanup_mode','12h');$cleanup_mode=sanitize_key(wp_unslash($_POST['tmc_cleanup_mode']??$previous_cleanup_mode));if(!in_array($cleanup_mode,['12h','keep'],true))$cleanup_mode='12h';update_option('tmc_cleanup_mode',$cleanup_mode);if($previous_cleanup_mode==='keep'&&$cleanup_mode==='12h')update_option('tmc_playlist_cycle_start',time(),false);$o=['mode'=>in_array($_POST['mode']??'',['both','chat','points','off'],true)?$_POST['mode']:'off','command'=>sanitize_text_field(wp_unslash($_POST['command']??'!wunsch')),'reward'=>mb_substr(sanitize_text_field(wp_unslash($_POST['reward']??'Musikwunsch')),0,45),'reward_cost'=>max(1,min(1000000000,absint($_POST['reward_cost']??1000))),'disabled_chat_notice'=>!empty($_POST['disabled_chat_notice'])?'yes':'no','disabled_chat_text'=>mb_substr(sanitize_text_field(wp_unslash($_POST['disabled_chat_text']??'')),0,450)];update_option('tmc_settings',$o);echo '<div class="notice notice-success" role="status"><p>Einstellungen gespeichert.</p></div>';} if(isset($_GET['tmc_connected']))echo '<div class="notice notice-success" role="status"><p>Twitch-Autorisierung abgeschlossen. Bitte Verbindungsstatus prüfen.</p></div>';echo '<div class="wrap"><h1>Twitch Musikwünsche</h1><p>Verbunden: <strong>'.esc_html(get_option('tmc_broadcaster','Noch nicht verbunden')).'</strong></p>';if(isset($_GET['tmc_error']))echo '<div class="notice notice-error" role="alert"><p>'.esc_html(sanitize_text_field(wp_unslash($_GET['tmc_error']))).'</p></div>';$enabled=get_option('tmc_list_enabled','yes')!=='no';$view=(isset($_GET['page'])&&sanitize_key(wp_unslash($_GET['page']))==='tmc-viewer')?'viewer':'queue';
$viewer_url=self::viewer_url();
$admin_url=admin_url('admin.php?page=tmc');
$viewer_tab_url=admin_url('admin.php?page=tmc-viewer');
echo '<nav class="nav-tab-wrapper tmc-admin-nav" aria-label="Musikwunsch-Verwaltung">';
echo '<a class="nav-tab '.($view==='viewer'?'':'nav-tab-active').'" '.($view==='viewer'?'':'aria-current="page" ').'href="'.esc_url($admin_url).'">Warteschlange und Einstellungen</a>';
echo '<a class="nav-tab" href="'.esc_url($viewer_url).'" target="_blank" rel="noopener noreferrer">Zuschaueransicht <span class="screen-reader-text">(öffnet neuen Tab)</span></a>';
echo '</nav>';
echo '<style>.tmc-admin-nav{display:flex;flex-wrap:wrap;gap:4px;margin:16px 0 20px}.tmc-admin-nav .nav-tab{white-space:normal;overflow-wrap:anywhere;min-height:44px;display:inline-flex;align-items:center}.tmc-admin-nav .nav-tab:focus-visible{outline:3px solid #9146ff;outline-offset:2px}.tmc-viewer-iframe{width:100%;height:720px;max-width:100%;border:1px solid #c3c4c7;border-radius:6px;background:#fff}@media(max-width:782px){.tmc-admin-nav .nav-tab{flex:1 1 100%;margin:0}.tmc-viewer-iframe{height:70vh;min-height:450px}}</style>';
if($view==='viewer'){
 echo '<h2>Zuschaueransicht</h2><p>Hier siehst du die öffentliche Musikwunschliste mit offenen und gespielten Titeln, so wie deine Zuschauer sie sehen.</p>';
 echo '<p><a class="button button-primary" href="'.esc_url($viewer_url).'" target="_blank" rel="noopener noreferrer">Zuschaueransicht in neuem Tab öffnen <span class="screen-reader-text">(öffnet einen neuen Tab)</span></a></p>';

 echo '</div>';return;
}

if(isset($_GET['tmc_list_result'])){$result=sanitize_key(wp_unslash($_GET['tmc_list_result']));$messages=['enable'=>'Liste aktiviert. Neue Musikwünsche werden angenommen.','disable'=>'Liste deaktiviert. Neue Musikwünsche werden abgewiesen; vorhandene bleiben sichtbar.','clear'=>'Alle Musikwünsche wurden gelöscht.'];if(isset($messages[$result]))echo '<div class="notice notice-success" role="status"><p>'.esc_html($messages[$result]).'</p></div>';}
echo '<style>.tmc-settings-panel .button.tmc-twitch-connect,.tmc-settings-panel .button.tmc-twitch-connect:focus{background:#9146ff!important;border-color:#9146ff!important;color:#fff!important;font-weight:700!important;box-shadow:none}.tmc-settings-panel .button.tmc-twitch-connect:hover{background:#772ce8!important;border-color:#772ce8!important;color:#fff!important}.tmc-settings-panel .button.tmc-twitch-connect:focus-visible{outline:3px solid #bf94ff;outline-offset:2px}.tmc-settings-panel,.tmc-settings-panel *{box-sizing:border-box}.tmc-settings-panel{width:100%;max-width:100%;min-width:0;overflow-wrap:anywhere}.tmc-settings-panel .form-table{width:100%;table-layout:auto}.tmc-settings-panel .form-table td,.tmc-settings-panel .form-table th{overflow-wrap:anywhere;white-space:normal}.tmc-settings-panel input:not([type=radio]):not([type=checkbox]):not([type=hidden]),.tmc-settings-panel select,.tmc-settings-panel textarea{max-width:100%;min-width:0}.tmc-settings-panel textarea{width:100%;height:auto}.tmc-settings-panel .tmc-retention-choice{display:flex;align-items:flex-start;gap:10px;margin:0 0 12px;max-width:100%;min-width:0;white-space:normal;overflow-wrap:anywhere;line-height:1.5;cursor:pointer}.tmc-settings-panel .tmc-retention-choice input[type=radio]{flex:0 0 auto;margin:4px 0 0}.tmc-settings-panel .tmc-retention-choice span{display:block;flex:1 1 auto;min-width:0;white-space:normal;overflow-wrap:anywhere}.tmc-settings-panel .button{max-width:100%;height:auto;min-height:32px;white-space:normal;overflow-wrap:anywhere;text-align:center}@media(max-width:782px){.tmc-settings-panel{padding:14px 12px;margin:16px 0}.tmc-settings-panel .form-table,.tmc-settings-panel .form-table tbody,.tmc-settings-panel .form-table tr,.tmc-settings-panel .form-table td,.tmc-settings-panel .form-table th{display:block;width:100%;max-width:100%;box-sizing:border-box}.tmc-settings-panel .form-table th{padding:14px 0 3px}.tmc-settings-panel .form-table td{padding:3px 0 12px}.tmc-settings-panel .form-table input:not([type=radio]):not([type=checkbox]):not([type=hidden]),.tmc-settings-panel .form-table select{width:100%}.tmc-settings-panel fieldset{min-width:0}.tmc-settings-panel .tmc-retention-choice{font-size:16px}.tmc-settings-panel .description{max-width:100%;line-height:1.5}}.tmc-settings-panel{margin:24px 0 32px;padding:22px;border:1px solid #c3c4c7;border-radius:10px;background:#fff}.tmc-settings-panel h3{margin:22px 0 10px}.tmc-dark .tmc-settings-panel{background:#242b36!important;border-color:#526072}.tmc-top-controls{display:flex;justify-content:flex-end;margin:0 0 12px}.tmc-list-status{display:inline-block;padding:12px 18px;border-radius:8px;border:2px solid;font-size:16px}.tmc-list-status.tmc-enabled{background:#d6f5df;color:#12582a;border-color:#16803c}.tmc-list-status.tmc-disabled{background:#ffe0e0;color:#87151b;border-color:#bd232b}.tmc-control-enable,.tmc-control-enable:disabled{background:#16803c!important;border-color:#12662f!important;color:#fff!important;font-weight:700!important}.tmc-control-disable,.tmc-control-disable:disabled{background:#bd232b!important;border-color:#a11c24!important;color:#fff!important;font-weight:700!important}.tmc-control-enable:disabled,.tmc-control-disable:disabled{opacity:.65!important;cursor:not-allowed}.tmc-control-enable:not(:disabled):hover{background:#10632e!important;color:#fff!important}.tmc-control-disable:not(:disabled):hover{background:#981a21!important;color:#fff!important}.tmc-dark{background:#171b22!important;color:#f1f4f9!important;min-height:100vh;padding:14px 20px!important;box-sizing:border-box}.tmc-dark h1,.tmc-dark h2,.tmc-dark p,.tmc-dark label,.tmc-dark th,.tmc-dark td,.tmc-dark .description{color:#f1f4f9!important}.tmc-dark .widefat,.tmc-dark .widefat th,.tmc-dark .widefat td,.tmc-dark .form-table{background:#242b36!important;color:#f1f4f9!important;border-color:#526072!important}.tmc-dark .widefat.striped>tbody>:nth-child(odd){background:#202630!important}.tmc-dark input,.tmc-dark select,.tmc-dark code{background:#303948!important;color:#fff!important;border-color:#758298!important}.tmc-dark .button:not(.tmc-control-enable):not(.tmc-control-disable):not(.tmc-action-delete):not(.tmc-action-played){background:#354154;color:#fff;border-color:#8392a8}.tmc-dark .tmc-list-status.tmc-enabled{background:#173f29!important;color:#b9f9cc!important;border-color:#55d781}.tmc-dark .tmc-list-status.tmc-disabled{background:#4d1d23!important;color:#ffd4d4!important;border-color:#fa7777}.tmc-dark #tmc-refresh-state{color:#c4d1e0!important}.tmc-dark .notice p{color:#1d2327!important}.tmc-settings-panel .button.tmc-twitch-sync,.tmc-settings-panel .button.tmc-twitch-sync:focus,.tmc-dark .tmc-settings-panel .button.tmc-twitch-sync,.tmc-dark .tmc-settings-panel .button.tmc-twitch-sync:focus{background:#9146ff!important;border-color:#9146ff!important;color:#fff!important;font-weight:700!important;box-shadow:none!important}.tmc-settings-panel .button.tmc-twitch-sync:hover,.tmc-dark .tmc-settings-panel .button.tmc-twitch-sync:hover{background:#772ce8!important;border-color:#772ce8!important;color:#fff!important}.tmc-settings-panel .button.tmc-twitch-sync:focus-visible{outline:3px solid #bf94ff!important;outline-offset:2px}.tmc-dark .tmc-settings-panel h2,.tmc-dark .tmc-settings-panel h3,.tmc-dark .tmc-settings-panel h4{color:#f1f4f9!important}.tmc-dark .tmc-settings-panel .description,.tmc-dark .tmc-settings-panel legend{color:#d6deec!important}</style>';echo '<hr><section class="tmc-settings-panel" aria-labelledby="tmc-settings-title"><h2 id="tmc-settings-title">Einstellungen und Verwaltung</h2><p>Hier findest du sämtliche Einstellungen und Aktionen für deine Musikwunschliste.</p><h3>Listensteuerung</h3>';echo '<div class="tmc-top-controls"><button type="button" id="tmc-theme-toggle" class="button" aria-pressed="false">Darkmode einschalten</button></div><p class="tmc-list-status '.($enabled?'tmc-enabled':'tmc-disabled').'" role="status"><strong>'.($enabled?'AKTIV – Musikwünsche erlaubt':'DEAKTIVIERT – Musikwünsche gesperrt').'</strong></p><p>Die vorhandene Liste bleibt immer sichtbar.</p><div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">';
$next_action=$enabled?'disable':'enable';
$toggle_label=$enabled?'Musikwünsche aktiviert – jetzt deaktivieren':'Musikwünsche deaktiviert – jetzt aktivieren';
echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
wp_nonce_field('tmc_list_control');
echo '<input type="hidden" name="action" value="tmc_list_control"><input type="hidden" name="tmc_list_action" value="'.esc_attr($next_action).'"><button type="submit" class="button '.($enabled?'tmc-control-enable':'tmc-control-disable').'" aria-label="'.esc_attr($toggle_label).'">'.esc_html($toggle_label).'</button></form>';
echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" onsubmit="return confirm(\'Wirklich ALLE Musikwünsche endgültig löschen? Dies kann nicht rückgängig gemacht werden.\');">';
wp_nonce_field('tmc_list_control');
echo '<input type="hidden" name="action" value="tmc_list_control"><input type="hidden" name="tmc_list_action" value="clear"><button type="submit" class="button tmc-action-clear" aria-label="Alle Musikwünsche dauerhaft löschen">Liste löschen</button></form>';
echo '</div>';
echo '<hr><h3>Eingegangene Wünsche</h3>';echo '<h2>Warteschlange</h2><div class="tmc-refresh-controls" role="group" aria-label="Warteschlange aktualisieren"><p id="tmc-refresh-state" role="status" aria-live="off">Automatische Aktualisierung alle 20 Sekunden.</p><button id="tmc-refresh-toggle" type="button" class="button button-secondary" aria-pressed="false">Automatische Aktualisierung pausieren</button> <button id="tmc-refresh-manual" type="button" class="button button-secondary">Jetzt aktualisieren</button></div>';echo '<style>.tmc-refresh-controls{margin:0 0 14px}.tmc-refresh-controls .button{margin:0 8px 8px 0;min-height:36px;max-width:100%;white-space:normal}.tmc-refresh-controls .button:focus-visible{outline:3px solid #2271b1;outline-offset:2px}.tmc-refresh-controls p{margin:0 0 8px}@media(max-width:782px){.tmc-refresh-controls .button{display:block;width:100%;text-align:center}} </style>';echo '<style>.tmc-action-played{background:#16803c!important;border-color:#12662f!important;color:#fff!important;font-weight:700!important}.tmc-action-played:hover,.tmc-action-played:focus{background:#10632e!important;color:#fff!important}.tmc-action-clear{background:#d97706!important;border-color:#a65305!important;color:#fff!important;font-weight:700!important}.tmc-action-clear:hover,.tmc-action-clear:focus{background:#a65305!important;border-color:#874204!important;color:#fff!important}.tmc-dark .tmc-action-clear{background:#d97706!important;border-color:#f59e0b!important;color:#fff!important}.tmc-dark .tmc-action-clear:hover,.tmc-dark .tmc-action-clear:focus{background:#b45309!important;color:#fff!important}.tmc-action-delete{background:#bd232b!important;border-color:#a11c24!important;color:#fff!important;font-weight:700!important}.tmc-action-delete:hover,.tmc-action-delete:focus{background:#981a21!important;color:#fff!important}.tmc-request-line{margin:0 0 7px;line-height:1.5;overflow-wrap:anywhere}.tmc-request-line strong{display:inline-block;min-width:110px}.tmc-request-table td,.tmc-request-table .tmc-song-cell{vertical-align:top;padding:14px 12px;text-align:left;font-weight:normal}.tmc-settings-panel input:focus-visible,.tmc-settings-panel select:focus-visible,.tmc-settings-panel textarea:focus-visible,.tmc-settings-panel button:focus-visible,.tmc-request-table button:focus-visible{outline:3px solid #9146ff!important;outline-offset:2px}.tmc-request-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.tmc-request-actions form{margin:0!important}@media(max-width:782px){.tmc-request-table thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}.tmc-request-table tr{display:block;border-bottom:1px solid #ccd0d4}.tmc-request-table td,.tmc-request-table .tmc-song-cell{display:block;width:auto!important;padding:8px 12px}.tmc-request-table td:before{content:attr(data-label);display:block;font-weight:600;margin-bottom:4px}.tmc-request-table .tmc-song-cell:before{display:none}}</style><table class="widefat striped tmc-request-table"><thead><tr><th scope="col">Musikwunsch</th><th scope="col">Quelle</th><th scope="col">Status</th><th scope="col">Aktionen</th></tr></thead><tbody id="tmc-queue-body">';
 echo self::queue_rows();
 echo '</tbody></table>';wp_enqueue_script('tmc-admin',plugins_url('admin.js',__FILE__),[], '0.7.12',true);wp_add_inline_script('tmc-admin','window.TMCAdminQueue='.wp_json_encode(['url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('tmc_refresh_queue')]).';','before');echo '<hr><h3>Eingabe und Kanalpunkte</h3>';if(isset($_GET['tmc_reward_status'])){ $status=sanitize_key($_GET['tmc_reward_status']);$msg=get_transient('tmc_reward_result_'.get_current_user_id());delete_transient('tmc_reward_result_'.get_current_user_id());if($msg)echo '<div role="status" class="notice '.($status==='ok'?'notice-success':'notice-error').'"><p>'.esc_html($msg).'</p></div>'; }echo '<form method="post">';wp_nonce_field('tmc_save');echo '<input type="hidden" name="tmc_save" value="1"><table class="form-table"><tr><th scope="row"><label for="tmc-mode">Eingabe</label></th><td><select id="tmc-mode" name="mode">';foreach(['both'=>'Chat und Kanalpunkte','chat'=>'Nur Chat','points'=>'Nur Kanalpunkte','off'=>'Deaktiviert'] as $k=>$v)echo '<option value="'.esc_attr($k).'" '.selected($o['mode'],$k,false).'>'.esc_html($v).'</option>';echo '</select></td></tr><tr><th scope="row"><label for="tmc-command">Chatbefehl</label></th><td><input id="tmc-command" name="command" value="'.esc_attr($o['command']).'"></td></tr><tr><th scope="row"><label for="tmc-reward">Kanalpunkte-Belohnung</label></th><td><input id="tmc-reward" name="reward" aria-describedby="tmc-reward-help" maxlength="45" value="'.esc_attr($o['reward']).'" required><p id="tmc-reward-help" class="description">Name der Belohnung bei Twitch (maximal 45 Zeichen).</p></td></tr><tr><th scope="row"><label for="tmc-reward-cost">Kosten in Kanalpunkten</label></th><td><input id="tmc-reward-cost" type="number" name="reward_cost" min="1" max="1000000000" step="1" value="'.esc_attr($o['reward_cost']??1000).'" required></td></tr>
<tr><th scope="row">Synchronisierung</th><td>
<button type="submit" class="button button-secondary tmc-twitch-sync" form="tmc-sync-reward-form">Twitch-Belohnung synchronisieren</button>
<p class="description">Erstellt die Belohnung bei Twitch, falls sie noch nicht existiert, oder aktualisiert eine bereits vorhandene Belohnung. Bitte vorher die Einstellungen speichern.</p>
</td></tr></table><h3>Chatnachrichten</h3><table class="form-table"><tr><th scope="row">Nachricht bei deaktivierter Liste</th><td><label><input type="checkbox" name="disabled_chat_notice" value="1" '.checked($o['disabled_chat_notice']??'no','yes',false).'> Nachricht im Twitch-Chat senden</label><p class="description">Nur bei einem Musikwunschversuch; höchstens einmal alle 60 Sekunden je verbundenem Kanal.</p><label for="tmc-disabled-chat-text">Nachrichtentext</label><br><textarea id="tmc-disabled-chat-text" name="disabled_chat_text" rows="3" cols="65" maxlength="450">'.esc_textarea($o['disabled_chat_text']??'Musikwünsche sind momentan deaktiviert. Bitte versuche es später erneut.').'</textarea></td></tr></table><h3>Aufbewahrung der Musikwünsche</h3><table class="form-table"><tr><th scope="row">Automatische Löschung</th><td><fieldset><legend class="screen-reader-text">Automatische Löschung</legend><label class="tmc-retention-choice"><input type="radio" name="tmc_cleanup_mode" value="12h" '.checked(get_option('tmc_cleanup_mode','12h'),'12h',false).'><span>Liste automatisch alle 12 Stunden löschen</span></label><label class="tmc-retention-choice"><input type="radio" name="tmc_cleanup_mode" value="keep" '.checked(get_option('tmc_cleanup_mode','12h'),'keep',false).'><span>Liste dauerhaft behalten (nur manuell löschen)</span></label><p class="description">Die Einstellung gilt für diese Webseite. Beim Wechsel zurück zur 12-Stunden-Löschung beginnt ein neuer Zeitraum. Vorhandene Wünsche werden beim Umschalten nicht gelöscht.</p></fieldset></td></tr></table>';submit_button('Einstellungen speichern');echo '</form><form id="tmc-sync-reward-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('tmc_sync_reward');echo '<input type="hidden" name="action" value="tmc_sync_reward"></form>';echo '<h2>Twitch verbinden</h2><p>Für die Anmeldung wird der zentrale Dienst musikwunsch.it-md-server.de verwendet.</p><form method="post">';wp_nonce_field('tmc_connect');echo '<input type="hidden" name="tmc_connect" value="1">';submit_button('Mit Twitch verbinden','secondary tmc-twitch-connect');echo '</form><p>Shortcode: <code>[twitch_musikwuensche]</code></p></section>';echo '</div>';}


 static function queue_rows(){global $wpdb;$songs=$wpdb->get_results('SELECT * FROM '.self::table().' ORDER BY id DESC LIMIT 100');ob_start();if(!$songs)echo '<tr><td colspan="4">Keine Musikwünsche vorhanden.</td></tr>';
 foreach($songs as $s){
  $song_label=trim($s->artist.' – '.$s->title);
  echo '<tr><th scope="row" class="tmc-song-cell"><div class="tmc-request-line"><strong>Interpret:</strong> '.esc_html($s->artist).'</div><div class="tmc-request-line"><strong>Titel:</strong> '.esc_html($s->title).'</div><div class="tmc-request-line"><strong>Gewünscht von:</strong> '.esc_html($s->username).'</div></th><td data-label="Quelle">'.esc_html($s->source).'</td><td data-label="Status">'.esc_html($s->status).'</td><td data-label="Aktionen"><div class="tmc-request-actions">';
  if($s->status!=='played'){
   echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin:0 6px 4px 0"><input type="hidden" name="action" value="tmc_quick_action">';
   wp_nonce_field('tmc_quick_action');
   echo '<input type="hidden" name="song_id" value="'.intval($s->id).'"><button class="button tmc-action-played" aria-label="' .esc_attr('Als gespielt markieren: '.$song_label).'" type="submit" name="tmc_quick_action" value="played">&#10003; Gespielt</button></form>';
  }
  echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin:0" onsubmit="return confirm(\'Diesen Musikwunsch wirklich endgültig löschen?\');">';
  wp_nonce_field('tmc_quick_action');
  echo '<input type="hidden" name="action" value="tmc_quick_action"><input type="hidden" name="song_id" value="'.intval($s->id).'"><button class="button tmc-action-delete" aria-label="' .esc_attr('Ablehnen oder löschen: '.$song_label).'" type="submit" name="tmc_quick_action" value="delete">&#10005; Ablehnen / Löschen</button></form></div></td></tr>';
 }
return ob_get_clean();}
 static function refresh_queue(){if(!current_user_can('manage_options'))wp_send_json_error(['message'=>'Keine Berechtigung'],403);check_ajax_referer('tmc_refresh_queue','nonce');self::cleanup_played();wp_send_json_success(['html'=>self::queue_rows()]);}
 static function quick_action(){
  if(!current_user_can('manage_options'))wp_die('Keine Berechtigung.',403);
  check_admin_referer('tmc_quick_action');
  global $wpdb;
  $id=absint($_POST['song_id']??0);
  $action=sanitize_key(wp_unslash($_POST['tmc_quick_action']??''));
  if($id>0 && $action==='played'){
   $wpdb->update(self::table(),['status'=>'played','played_at'=>current_time('mysql')],['id'=>$id,'status'=>'waiting'],['%s','%s'],['%d','%s']);
  }elseif($id>0 && $action==='delete'){
   $wpdb->delete(self::table(),['id'=>$id],['%d']);
  }
  wp_safe_redirect(add_query_arg(['page'=>'tmc','tmc_action_done'=>$action],admin_url('admin.php')));
  exit;
 }
 static function sync_reward(){
  if(!current_user_can('manage_options'))wp_die('Keine Berechtigung',403);check_admin_referer('tmc_sync_reward');$o=self::opts();$secret=get_option('tmc_secret');
  $payload=['site_url'=>home_url('/'),'title'=>$o['reward']??'Musikwunsch','cost'=>(int)($o['reward_cost']??1000),'nonce'=>bin2hex(random_bytes(16))];$body=wp_json_encode($payload);$ts=(string)time();
  $r=wp_remote_post('https://musikwunsch.it-md-server.de/index.php?rest_route=/twitch-musik/v1/reward/sync',['timeout'=>25,'headers'=>['Content-Type'=>'application/json','X-TM-Timestamp'=>$ts,'X-TM-Signature'=>hash_hmac('sha256',$ts.'.'.$body,$secret)],'body'=>$body]);
  $data=is_wp_error($r)?[]:json_decode(wp_remote_retrieve_body($r),true);$ok=!is_wp_error($r)&&wp_remote_retrieve_response_code($r)===200&&!empty($data['ok']);
  $msg=$ok?'Twitch-Belohnung '.(($data['action']??'')==='created'?'erstellt':'aktualisiert').': '.($data['title']??'').' ('.($data['cost']??'').' Punkte).':(is_wp_error($r)?$r->get_error_message():($data['message']??'Twitch konnte die Belohnung nicht speichern.'));
  set_transient('tmc_reward_result_'.get_current_user_id(),sanitize_text_field($msg),120);
  wp_safe_redirect(add_query_arg(['page'=>'tmc','tmc_reward_status'=>$ok?'ok':'error'],admin_url('admin.php')));exit;
 }
 static function connect(){if(!is_admin()||!isset($_POST['tmc_connect'])||!current_user_can('manage_options'))return;check_admin_referer('tmc_connect');$secret=get_option('tmc_secret');$r=wp_remote_post('https://musikwunsch.it-md-server.de/index.php?rest_route=/twitch-musik/v1/pair',['timeout'=>20,'headers'=>['Content-Type'=>'application/json'],'body'=>wp_json_encode(['site_url'=>home_url('/'),'push_secret'=>$secret])]);$d=is_wp_error($r)?[]:json_decode(wp_remote_retrieve_body($r),true);if(empty($d['authorization_url'])){wp_safe_redirect(add_query_arg('tmc_error',rawurlencode('Zentrale nicht erreichbar oder nicht eingerichtet'),admin_url('admin.php?page=tmc')));exit;}wp_redirect(esc_url_raw($d['authorization_url']));exit;}
 static function routes(){register_rest_route(self::NS,'/proof',['methods'=>'GET','callback'=>[__CLASS__,'proof'],'permission_callback'=>'__return_true']);register_rest_route(self::NS,'/incoming',['methods'=>'POST','callback'=>[__CLASS__,'incoming'],'permission_callback'=>'__return_true']);register_rest_route(self::NS,'/songs',['methods'=>'GET','callback'=>[__CLASS__,'songs'],'permission_callback'=>'__return_true']);register_rest_route(self::NS,'/status',['methods'=>'GET','callback'=>[__CLASS__,'public_status'],'permission_callback'=>'__return_true']);}
 static function proof($r){$challenge=(string)$r->get_param('challenge');if(!preg_match('/^[a-f0-9]{40}$/',$challenge))return new WP_Error('challenge','Ungültig',['status'=>400]);return ['proof'=>hash_hmac('sha256',$challenge,get_option('tmc_secret'))];}
 static function incoming($r){self::cleanup_playlist();$ts=$r->get_header('x-tm-timestamp');$sig=$r->get_header('x-tm-signature');$body=$r->get_body();if(!$ts||abs(time()-intval($ts))>300||!$sig||!hash_equals(hash_hmac('sha256',$ts.'.'.$body,get_option('tmc_secret')),$sig))return new WP_Error('forbidden','Signatur ungültig',['status'=>403]);$j=json_decode($body,true);if(($j['type']??'')==='connected'){update_option('tmc_broadcaster',sanitize_text_field($j['broadcaster_name']??''));return ['ok'=>true];}$o=self::opts();$type=$j['type']??'';if(get_option('tmc_list_enabled','yes')==='no'){
  // Only respond to an actual song request, never to unrelated chat messages.
  $is_request=false;
  if($type==='chat'){$command=trim($o['command']??'');$message=trim((string)($j['text']??''));$is_request=$command!==''&&($message===$command||stripos($message,$command.' ')===0);}
  elseif($type==='points'){$is_request=($j['reward']??'')===($o['reward']??'');}
  $notice=trim((string)($o['disabled_chat_text']??'Musikwünsche sind momentan deaktiviert. Bitte versuche es später erneut.'));
  if($is_request&&($o['disabled_chat_notice']??'no')==='yes'&&$notice!=='')return ['ignored'=>true,'disabled_notice'=>$notice];
  return ['ignored'=>true];
 }
 if($o['mode']==='off'||($type==='chat'&&!in_array($o['mode'],['chat','both'],true))||($type==='points'&&!in_array($o['mode'],['points','both'],true)))return ['ignored'=>true];$text=trim($j['text']??'');if($type==='chat'){$command=trim($o['command']);if(!$command||stripos($text,$command.' ')!==0)return ['ignored'=>true];$text=trim(substr($text,strlen($command)));}elseif($type==='points'){if(($j['reward']??'')!==$o['reward'])return ['ignored'=>true];}else return ['ignored'=>true];if(!str_contains($text,' - '))return ['ignored'=>true];[$artist,$title]=array_map('trim',explode(' - ',$text,2));if(!$artist||!$title)return ['ignored'=>true];global $wpdb;$event=substr(sanitize_text_field($j['event_id']??''),0,120);if(!$event)return new WP_Error('event','Keine Event-ID',['status'=>400]);$cover=self::cover($artist,$title);$username=substr(sanitize_text_field($j['user']??''),0,100);$artist=substr(sanitize_text_field($artist),0,255);$title=substr(sanitize_text_field($title),0,255);
 $inserted=$wpdb->insert(self::table(),['event_id'=>$event,'username'=>$username,'artist'=>$artist,'title'=>$title,'cover_url'=>$cover,'source'=>$type,'status'=>'waiting','created'=>current_time('mysql')]);
 if($inserted===false){
  // A duplicate event must never trigger a second public confirmation.
  if($wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table().' WHERE event_id=%s',$event)))return ['ok'=>true,'duplicate'=>true];
  return new WP_Error('insert_failed','Musikwunsch konnte nicht gespeichert werden',['status'=>500]);
 }
 return ['ok'=>true,'accepted'=>true,'user'=>$username,'artist'=>$artist,'title'=>$title];}
 static function cover($artist,$title){
  // Cache both positive and negative results to avoid repeated requests.
  $key='tmc_cover_'.md5(mb_strtolower($artist.'|'.$title));
  $cached=get_transient($key);
  if($cached!==false)return $cached==='none'?'':$cached;
  $url=add_query_arg(['term'=>$artist.' '.$title,'entity'=>'song','limit'=>10],'https://itunes.apple.com/search');
  $r=wp_remote_get($url,['timeout'=>5]);
  if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200){set_transient($key,'none',HOUR_IN_SECONDS);return '';}
  $j=json_decode(wp_remote_retrieve_body($r),true);$cover='';
  $normalize=static function($v){$v=mb_strtolower(trim((string)$v));return preg_replace('/[^\p{L}\p{N}]+/u','',$v);};
  foreach($j['results']??[] as $v){
   if($normalize($v['artistName']??'')===$normalize($artist)&&$normalize($v['trackName']??'')===$normalize($title)){
    $candidate=$v['artworkUrl100']??'';
    if(str_starts_with($candidate,'https://'))$cover=esc_url_raw(str_replace('100x100bb','300x300bb',$candidate));
    break;
   }
  }
  set_transient($key,$cover?:'none', $cover?30*DAY_IN_SECONDS:DAY_IN_SECONDS);
  return $cover;
 }
 static function public_status(){ $response=rest_ensure_response(['enabled'=>get_option('tmc_list_enabled','yes')!=='no']);$response->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');return $response;}
 static function songs(){self::cleanup_played();global $wpdb;$rows=$wpdb->get_results("SELECT username,artist,title,cover_url,source,status,created,played_at FROM ".self::table()." WHERE status IN ('waiting','playing','played') ORDER BY id ASC LIMIT 200",ARRAY_A);return rest_ensure_response($rows);}
 static function assets(){wp_register_script('tmc-front',plugins_url('front.js',__FILE__),[], '0.7.9',true);wp_register_style('tmc-front',plugins_url('front.css',__FILE__),[],'0.7.5');}
 static function shortcode(){wp_enqueue_script('tmc-front');wp_enqueue_style('tmc-front');return '<div class="tmc-public" data-tmc-status-endpoint="'.esc_url(rest_url(self::NS.'/status')).'"><div class="tmc-public-state" role="status" aria-live="polite">Status der Musikwünsche wird geladen …</div><div class="tmc-list" data-tmc-endpoint="'.esc_url(rest_url(self::NS.'/songs')).'">Musikwünsche werden geladen …</div></div>'; }
}
TM_Client::boot();
