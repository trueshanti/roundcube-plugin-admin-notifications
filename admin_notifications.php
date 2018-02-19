<?php

/**
 * Roundcube Plugin Admin Notifications
 * Presents messages to users with notifications from admins
 *
 *
 * @license GNU GPLv3+
 * @author Manuel Delgado
 */
class admin_notifications extends rcube_plugin
{
    public $task    = 'mail|settings';
    private $rc;
    private $notif_table = 'adminnotifications';
    private $sess_name = 'plugin.admin_notifications';
    private $plug_name = 'plugin.admin_notifications';
    private $pref_name = 'admin_notifications';


    function init()
    {
        $this->rc = rcmail::get_instance();
        $this->load_config();
        /*if ($this->rc->user->get_username()) {
          $this->enable_notifications(null);
        }*/
        
        // Show new notifications only after login (less disruptive)
        $this->add_hook('login_after', array($this, 'enable_notifications'));
        
        // Settings Tab
        if ($this->rc->task == 'settings') {
          $user = $this->rc->user->get_username();
          $admins =  $this->rc->config->get('admin_options_users', array());
          if (in_array($user, $admins, true)) {
            $this->add_texts('localization', true);
            $this->include_script('admin_notifications.js');
            $this->include_stylesheet($this->local_skin_path() . '/admin_notifications.css');
            $this->add_hook('admin_options_list', array($this, 'notifications_admin_options_list'));
            $this->add_hook('admin_options_data', array($this, 'notifications_data'));
            $this->add_hook('admin_options_save', array($this, 'save_notification'));
          }
        }
        // Mail Tab
        if ($this->rc->task == 'mail' and $this->rc->action != 'compose') {
          if ($this->rc->user->get_username() and $_SESSION[$this->sess_name]) {
              $this->add_texts('localization', true);
              $this->include_script('admin_notifications.js');
              $this->register_action('plugin.admin_notification_ack', array($this, 'save_ack_data'));
              $this->add_hook('render_page', array($this, 'render_page'));
          }
        }
    }
    
	  // ADMIN_OPTIONS HOOKS //
	  function notifications_admin_options_list($args)
	  {
	    $args['list']['notifications'] = array(
			  'id' => 'notifications',
			  'section' => rcube::Q($this->gettext('notifications'))
		  );
		  return($args);
	  }
	  
	  function notifications_data($args)
	  {
		  if ($args['section'] == 'notifications') {
        $notificacion = array();
        $id = trim(rcube_utils::get_input_value('_nid', rcube_utils::INPUT_GET));
        $delete = trim(rcube_utils::get_input_value('_delete', rcube_utils::INPUT_GET));
        $delete = $delete == 'true'? true : false;
        if (is_numeric($id) and !$delete) {
          $notificacion = $this->get_notification($id);
        } else if (is_numeric($id) and $delete) {
          if (!$this->delete_notification($id)) {
            $this->rc->output->show_message('errorsaving', 'error');
          }
        }


        $args['blocks']['id_notif'] = array('content' => html::tag('input', array('name' => '_id',
                                  'id'    => 'rcmfd_notifid',
			                            'type'  => 'hidden',
			                            'value' => $notificacion['id'])));

			  $args['blocks']['create_notif'] = array('options' => array(), 'name' => rcube::Q($this->gettext('notifications_create')));
			  $args['blocks']['list_notif'] = array('options' => array(), 'name' => rcube::Q($this->gettext('notifications')), 'cols' => 1);

			  $i = 0;
			  $notifications = $this->load_notifications(0, false);
			  
			  $args['blocks']['create_notif']['options'][] = array(
			          'title'   => rcube::Q($this->gettext('notification_title')), 
			          'content' => html::tag('input', array('name' => '_title', 
			                            'type'      => 'text', 
			                            'maxlength' => "100",
			                            'title'     => rcube::Q($this->gettext('notification_title')),
			                            'class'     => 'linput', 
			                            'value'     => $notificacion['title']))
        );
        $args['blocks']['create_notif']['options'][] = array(
			          'title'   => rcube::Q($this->gettext('notification_message')), 
			          'content' => html::tag('textarea', array('name' => '_message',  
			                            'title'      => rcube::Q($this->gettext('notification_message')),
			                            'id'         => 'rcmfd_notifmessage',
			                            'size'       => 40, 'rows' => 6,
			                            'spellcheck' => true,),
			                            $notificacion['message'])
        );
        
        //TODO HTML editor
        /*$args['blocks']['create_notif']['options'][] = array(
			          'title' => rcube::Q($this->gettext('notification_ishtml')), 
			          'content' => html::tag('input', array('name'=> '_ishtml',
			                            'title' => rcube::Q($this->gettext('notification_ishtml')),
			                            'id' => 'rcmfd_notifhtml',
			                            'type' => 'checkbox', 
			                            'onclick' => rcmail_output::JS_OBJECT_NAME . '.admin_toggle_html(this, \'rcmfd_notifhtml\', \'rcmfd_notifmessage\');'))
        );*/
        
        $chbox_atrib = array('name' => '_isactive',
                            'title' => rcube::Q($this->gettext('notification_isactive')),
                            'type'  => 'checkbox',
                            'value' => '1',);
        if ($notificacion['active'] == 1) {
          $chbox_atrib['checked'] = 'checked';
        }
        $args['blocks']['create_notif']['options'][] = array(
			          'title' => rcube::Q($this->gettext('notification_isactive')), 
			          'content' => html::tag('input', $chbox_atrib)
        );
        
        $args['blocks']['create_notif']['options'][] = array(
			          'content' => $this->api->output->button(array('type' => 'input', 
			                            'command' => 'plugin.admin_options.save', 
			                            'class'   => 'button mainaction',
			                            'label'   => 'admin_notifications.notification_add'))
        );

			  foreach ($notifications as $n) {
			    $n['active_class'] = $n['active'] == 1 ? 'active' : 'disabled';
			    $n['active'] = $n['active'] == 1 ? 
			                      rcube::Q($this->gettext('notification_active')) : 
			                      rcube::Q($this->gettext('notification_disabled'));
			    
				  $args['blocks']['list_notif']['options'][] = array(
					  'content' => html::tag('div', 'notificationitem', 
					                        html::tag('p', 'notificationtitle', $n['title']) .
					                        html::tag('p', 'notificationmessage', $n['message']) . 
					                        html::tag('span', 'notification'.$n['active_class'], $n['active'])).
                         html::tag('td', 'notificationedit', 
                                  $this->api->output->button(array('type' => 'input',
			                              'command' => 'plugin.admin_options.edit_notif',
			                              'class'   => 'button mainaction',
			                              'label'   => 'edit',
			                              'prop'    => $n['id'])).
			                            $this->api->output->button(array('type' => 'input',
			                              'command' => 'plugin.admin_options.delete_notif',
			                              'class'   => 'button deleteaction',
			                              'label'   => 'delete',
			                              'prop'    => $n['id'])))
				  );
			  }
		  }
		  return($args);
	  }
	  
	  function save_notification($attrib)
    {
      if ($attrib['section'] == "notifications" and !$attrib['abort']){
        $id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_POST);
        if (!is_numeric($id) or !$this->notification_exists($id)) {
          $attrib['abort'] = true;
          return $attrib;
        }
        
        $title = trim(rcube_utils::get_input_value('_title', rcube_utils::INPUT_POST));
        $message = trim(rcube_utils::get_input_value('_message', rcube_utils::INPUT_POST, true));
        if ($title == '' or $message == '') {
          $attrib['abort'] = true;
          return $attrib;
        }
        $active = rcube_utils::get_input_value('_isactive', rcube_utils::INPUT_POST);
        if ($active == '1') {
          $active = true;
        } else {
          $active = false;
        }
        
        if ($id) {
          if (!$this->update_notification($id, $title, $message, $active)) {
            $attrib['abort'] = true;
          }
        } else {
          if (!$this->insert_notification($title, $message, $active)) {
            $attrib['abort'] = true;
          }
        }
      }
      
      return $attrib;
    }
    
    
    // NOTIFICATIONS ACTIONS //
    function enable_notifications($args)
    {
        $last_id = $this->get_last_id();
        if (!$last_id or $last_id < 1){
          $this->rc->session->remove($this->sess_name);
        } else {
          $prefs = $this->rc->user->get_prefs();
          if (empty($prefs[$this->pref_name])
            or $prefs[$this->pref_name]['last'] < $last_id) {
              $_SESSION[$this->sess_name] = true;
          }        
        }
        return $args;
    }

    function render_page($p)
    {
        if ($_SESSION[$this->sess_name]) {
            $prefs = $this->rc->user->get_prefs();
            $last = !empty($prefs[$this->pref_name])? 
                      $prefs[$this->pref_name]['last'] : 0;
            $notifications = $this->load_notifications($last);
            if (count($notifications) > 0) {
              $carousel = '<ul>';
              $buttons  = '';
              foreach ($notifications as $n){
                $slide = html::tag('h3', null, $n['title'] ) .
                         html::div('admin_notification', $n['message'] ) .
                         html::tag('input', array(
                            'type'     => 'hidden',
                            'name'     => '_adminnotificationid[]',
                            'value'    => $n['id']));
                            
                $carousel .= html::tag('li', 'notification_slide' , $slide); 
              }
              $carousel .= '</ul>';
              if (count($notifications) > 1) {
                $buttons = html::div('formbuttons',
                            $this->api->output->button(array('type' => 'input', 
			                            'command' => 'plugin.admin_notifications.prev', 
			                            'class'   => 'button mainaction',
			                            'label'   => 'previous')) .
                            $this->api->output->button(array('type' => 'input', 
			                            'command' => 'plugin.admin_notifications.next', 
			                            'class'   => 'button mainaction',
			                            'label'   => 'next')) .
                            $this->api->output->button(array('type' => 'input', 
			                            'command' => 'plugin.admin_notifications.markall', 
			                            'class'   => 'button mainaction markall',
			                            'label'   => 'markallread')));
              } else {
                $buttons = html::div('formbuttons',
                            $this->api->output->button(array('type' => 'input', 
			                            'command' => 'plugin.admin_notifications.markall', 
			                            'class'   => 'button mainaction markall',
			                            'label'   => 'close')));
              }
			                            
              $nform = html::tag('form', array(
                          'id'     => 'admin-notifications',
                          //'action' => $this->rc->url($this->plug_name),
                          'method' => 'post'
                       ), $carousel.$buttons);

              $this->rc->output->add_footer(html::div(array(
                          'class' => 'admin_notification_carousel',
                          'id'    => 'admin_notification_carousel'
                       ), $nform));
              
              $this->include_stylesheet($this->local_skin_path() . '/admin_notifications.css');
            }
        }
    }

    function save_ack_data($p)
    {
        $ids = rcube_utils::get_input_value('_adminnotificationid', rcube_utils::INPUT_POST);
        $close = trim(rcube_utils::get_input_value('_adminnotificationclose', rcube_utils::INPUT_POST));
        
        $abort = false;
        if (!is_array($ids)) {
          $ids = array($ids);
        }
        foreach($ids as $id){
          if (!$abort and (empty($id) or !is_numeric($id))) {
            $abort = true;
          }
        }
        if ($abort) {
          $this->rc->output->show_message('errorsaving', 'error');
        } else {
            // save data
            $prefs = $this->rc->user->get_prefs();
            if (empty($prefs[$this->pref_name])){
              $prefs[$this->pref_name] = array();
            }
            foreach($ids as $id) {
              $id = intval($id);
              if ($id > $prefs[$this->pref_name]['last']){
                $prefs[$this->pref_name]['last'] = $id;
              }
            }
            if (!$this->rc->user->save_prefs($prefs)){
              $this->rc->output->show_message('errorsaving', 'error');
            } else if (!empty($close)){
              // remove flag and hide dialog
              $this->rc->session->remove($this->sess_name);
              $this->rc->output->command('admin_notifications_close');
            }
        }

        $this->rc->output->send();
    }
    
    
    // DATABASE //
    private function load_notifications($last = 0, $active = true)
    {
        $sql = "SELECT id, mod_user_id, modified, title, message, html, type, active FROM " .
            $this->table_name() . " WHERE id > ? " . ($active? " AND active = 1 ":"") .
            " ORDER BY id, modified DESC ";
        $sth = $this->rc->db->query($sql,  $last);

        $rows = array();
        while ($res = $this->rc->db->fetch_assoc($sth)) {
            $rows[$res['id']] = $res;
        }

        return $rows;
    }
    
    private function get_last_id()
    {
        $sql = "SELECT id FROM " . $this->table_name() . 
            " WHERE active = 1 ORDER BY id DESC ";
        $sth = $this->rc->db->limitquery($sql, 0, 1, $last);

        $rows = array();
        while ($res = $this->rc->db->fetch_assoc($sth)) {
            $rows[] = $res;
        }
        
        if (count($rows) > 0) {
          return $rows[0]['id'];
        }

        return false;
    }
    
    private function get_notification($id)
    {
        $sql = "SELECT id, mod_user_id, modified, title, message, html, type, active FROM " . 
            $this->table_name() . " WHERE id = ? ";
        $sth = $this->rc->db->query($sql, $id);

        $rows = array();
        while ($res = $this->rc->db->fetch_assoc($sth)) {
            $rows[] = $res;
        }
        
        if (count($rows) > 0) {
          return $rows[0];
        }

        return false;
    }
    
    private function notification_exists($id)
    {
        $sql = "SELECT id FROM " . $this->table_name() . 
            " WHERE id = ? ";
        $sth = $this->rc->db->query($sql, $id);

        $rows = array();
        while ($res = $this->rc->db->fetch_assoc($sth)) {
            $rows[] = $res;
        }
        
        if (count($rows) > 0) {
          return true;
        }

        return false;
    }
    
    private function insert_notification($title, $message, $active=false, $html=false, $type=0)
    {
      $user_id = $this->rc->user->ID;
      $sql = "INSERT INTO ". $this->table_name() .
             " (user_id, mod_user_id, title, message ".($active?", active":"").($html?", html":"").", type)" . 
             " VALUES (".$user_id.",".$user_id.",?,?".($active?", TRUE":"").($html?", TRUE":"").",?)";
             
      $sth = $this->rc->db->query($sql,  array($title, $message, $type));
      if ($sth) {
        return true;
      }
      return false;
    }
    
    private function delete_notification($id)
    {
      $sql = "DELETE FROM ". $this->table_name() .
             " WHERE id = ?";
             
      $sth = $this->rc->db->query($sql,  $id);
      if ($sth) {
        return true;
      }
      return false;
    }
        
    private function update_notification($id, $title, $message, $active=false, $html=false, $type=0)
    {
      $user_id = $this->rc->user->ID;
      $sql = "UPDATE ". $this->table_name() .
             " SET mod_user_id = ".$user_id.", title = ?, message = ?, type = ? " . 
             ($active?", active=TRUE":", active=FALSE").($html?", html=TRUE":", html=FALSE").
             " WHERE id = ? ";
             
      $sth = $this->rc->db->query($sql,  array($title, $message, $type, $id));
      if ($sth) {
        return true;
      }
      return false;
    }
    
    /**
     * Get table name.
     */
    private function table_name()
    {
        return $this->rc->db->table_name($this->notif_table, true);
    }
}

