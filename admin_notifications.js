/**
 * Roundcube Plugin Admin Notifications
 * Presents messages to users with notifications from admins
 *
 *
 * @license GNU GPLv3+
 * @author Manuel Delgado
 */
$(document).ready(function(){ 
  if (window.rcmail) {
    if (rcmail.env.action == 'plugin.admin_options.load'){
      rcmail.register_command('plugin.admin_options.edit_notif', function(i) {
          rcmail.admin_options_load('notifications', 'plugin.admin_options.load', '&_nid='+i);
      }, true);
      rcmail.register_command('plugin.admin_options.delete_notif', function(i) {
          if (confirm(rcmail.gettext('notification_delete','admin_notifications'))) {
            rcmail.admin_options_load('notifications', 'plugin.admin_options.load', '&_delete=true&_nid='+i);
          }
      }, true);
    }
    if (rcmail.env.task == 'mail'){
      rcube_webmail.prototype.admin_notifications_mark_current = function() {
        var current_id = $('.notification_slide.current input').val();
        var request = {};
        request['_adminnotificationid'] = current_id;

        rcmail.http_post('plugin.admin_notification_ack', request, true);
      }  
      rcube_webmail.prototype.admin_notifications_mark_all = function() {
        var i, value, request = {}, slides_input = $('.notification_slide input');
        request['_adminnotificationid'] = {};
        for(i = 0; i < slides_input.length; i++) {
            value = slides_input[i].value;
            request['_adminnotificationid'][i] = value;
        }
        request['_adminnotificationclose'] = true;
        rcmail.http_post('plugin.admin_notification_ack', request, true);
      }    
      
      rcube_webmail.prototype.admin_notifications_close = function() { $('.admin_notification_carousel').dialog('close'); }
      
      var mWidth = Math.min($(window).width() * 0.85, 500);
      var ntitle = rcmail.gettext('notifications','admin_notifications');
      var slides = $('.notification_slide');
      $('.admin_notification_carousel').show()
        .dialog({modal:true, resizable:false, closeOnEscape:false, width:mWidth, title:ntitle});
        
      rcmail.register_command('plugin.admin_notifications.prev', function() {
          var current = $('.notification_slide.current');
          if (! slides.first().is(current)) {
            var prev = current.prev();
            prev.addClass('current');
            current.removeClass('current');
            if (slides.first().is(prev)){
              rcmail.enable_command('plugin.admin_notifications.prev', false);
            }
          }
          rcmail.enable_command('plugin.admin_notifications.next', true);
          return false;
      }, false);
      rcmail.register_command('plugin.admin_notifications.next', function() {
          rcmail.admin_notifications_mark_current();
          var current = $('.notification_slide.current');
          if (! slides.last().is(current)) {
            var next = current.next();
            next.addClass('current');
            current.removeClass('current');
            if (slides.last().is(next)){
              rcmail.enable_command('plugin.admin_notifications.next', false);
            }
          }
          rcmail.enable_command('plugin.admin_notifications.prev', true);
          return false;
      }, true);
      rcmail.register_command('plugin.admin_notifications.markall', function() {
          rcmail.enable_command('plugin.admin_notifications.next', false);
          rcmail.enable_command('plugin.admin_notifications.prev', false);
          rcmail.admin_notifications_mark_all();
          
          return false;
      }, true);
      $('.notification_slide').first().addClass('current');
      
    }
  }
});

