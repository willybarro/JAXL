<?php
		
	// Serve application UI if $_REQUEST['jaxl'] is not set
	if(!isset($_REQUEST['jaxl'])) {
		$boshchatUI =<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" dir="ltr">
        <head profile="http://gmpg.org/xfn/11">
                <link rel="SHORTCUT ICON" href="http://im.jaxl.im/favicon.ico" type="image/x-icon">
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
                <title>Sample Chat Application using Jaxl Library</title>
                <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
                <script type="text/javascript" src="jaxl.js"></script>
		<style type="text/css">
body { color:#444; background-color:#F7F7F7; font:62.5% "lucida grande","lucida sans unicode",helvetica,arial,sans-serif; }
label, input { margin-bottom:5px; }
#read { width:700px; height:250px; overflow-x:hidden; overflow-y:auto; background-color:#FFF; border:1px solid #E7E7E7; display:none; }
#read .mssgIn, #read .presIn { text-align:left; margin:5px; padding:0px 5px; border-bottom:1px solid #EEE; }
#read .presIn { background-color:#F7F7F7; font-size:11px; font-weight:normal; }
#read .mssgIn p.from, #read .presIn p.from { padding:0px; margin:0px; font-size:13px; }
#read .mssgIn p.from { font-weight:bold; }
#read .mssgIn p.body { padding:0px; margin:0px; font-size:12px; }
#write { width:698px; border:1px solid #E7E7E7; background-color:#FFF; height:20px; padding:1px; font-size:13px; color:#AAA; display:none; }
		</style>
		<script type="text/javascript">
var boshapp = {
        payloadHandler: function(payload) {
                if(payload.jaxl == 'connected') {
                        jaxl.connected = true;
                        jaxl.jid = payload.jid;

                        $('#uname').css('display', 'none');
                        $('#passwd').css('display', 'none');
                        $('#button input').val('Disconnect');
                        $('#read').css('display', 'block');
                        $('#write').css('display', 'block');

                        obj = new Object;
                        obj['jaxl'] = 'getRosterList';
                        jaxl.sendPayload(obj);
                }
                else if(payload.jaxl == 'rosterList') {
                        obj = new Object;
                        obj['jaxl'] = 'setStatus';
                        jaxl.sendPayload(obj);
                }
                else if(payload.jaxl == 'disconnected') {
                        jaxl.connected = false;
                        jaxl.disconnecting = false;

                        $('#read').css('display', 'none');
                        $('#write').css('display', 'none');
                        $('#uname').css('display', 'block');
                        $('#passwd').css('display', 'block');
                        $('#button input').val('Connect');

                        console.log('disconnected');
                }
                else if(payload.jaxl == 'message') {
                        $('#read').append(jaxl.urldecode(payload.message));
                        $('#read').animate({ scrollTop: $('#read').attr('scrollHeight') }, 300);
                        jaxl.ping();
                }
                else if(payload.jaxl == 'presence') {
                        $('#read').append(jaxl.urldecode(payload.presence));
                        $('#read').animate({ scrollTop: $('#read').attr('scrollHeight') }, 300);
                        jaxl.ping();
                }
                else if(payload.jaxl == 'pinged') {
                        jaxl.ping();
                }
        }
};

jQuery(function($) {
        $(document).ready(function() {
                jaxl.pollUrl = 'http://localhost.localdomain/jaxl.php';
                jaxl.payloadHandler = new Array('boshapp', 'payloadHandler');

                $('#button input').click(function() {
                        if($(this).val() == 'Connect') {
                                $(this).val('Connecting...');

                                // prepare connect object
                                obj = new Object;
                                obj['user'] = $('#uname input').val();
                                obj['pass'] = $('#passwd input').val();

                                jaxl.connect(obj);
                        }
                        else if($(this).val() == 'Disconnect') {
                                $(this).val('Disconnecting...');
                                jaxl.disconnect();
                        }
                });

                $('#write').focus(function() {
                        $(this).val('');
                        $(this).css('color', '#444');
                });

                $('#write').blur(function() {
                        if($(this).val() == '') $(this).val('Type your message');
                        $(this).css('color', '#AAA');
                });

                $('#write').keydown(function(e) {
                        if(e.keyCode == 13 && jaxl.connected) {
                                message = $.trim($(this).val());
                                if(message.length == 0) return false;
                                $(this).val('');

                                obj = new Object;
                                obj['jaxl'] = 'message';
                                obj['message'] = message;
                                jaxl.sendPayload(obj);
                        }
                });
        });
});
		</script>
	</head>
        <body>
                <center>
                        <h1>Sample Chat Application using Jaxl Library</h1>
                        <div id="uname">
                                <label>Username:</label>
                                <input type="text" value=""/>
                        </div>
                        <div id="passwd">
                                <label>Password:</label>
                                <input type="password" value=""/>
                        </div>
                        <div id="read"></div>
                        <input type="text" value="Type your message" id="write"></input>
                        <div id="button">
                                <label></label>
                                <input type="button" value="Connect"/>
                        </div>
                </center>
        </body>
</html>
HTML;
		echo $boshchatUI;
		exit;
	}
	else if(isset($_REQUEST['jaxl'])) {
		// Valid bosh request
	}
	
	// Initialize Jaxl Library
	$jaxl = new JAXL();
	
	// Include required XEP's
	jaxl_require(array(
		'JAXL0115', // Entity Capabilities
		'JAXL0085', // Chat State Notification
		'JAXL0092', // Software Version
		'JAXL0203', // Delayed Delivery
		'JAXL0206'  // XMPP over Bosh
	));
	
	// Sample Bosh chat application class
	class boshchat {
		
		public static function doAuth($mechanism) {
			global $jaxl;
			$jaxl->auth("DIGEST-MD5");
		}
		
		public static function postAuth() {
			global $jaxl;
			$response = array('jaxl'=>'connected', 'jid'=>$jaxl->jid);
			JAXL0124::out($response);
		}
		
		public static function handleRosterList($payload) {
			global $jaxl;
			
			$roster = array();
			if(is_array($payload['queryItemJid'])) {
				foreach($payload['queryItemJid'] as $key=>$jid) {
					$roster[$jid]['group'] = $payload['queryItemGrp'][$key];
					$roster[$jid]['subscription'] = $payload['queryItemSub'][$key];
					$roster[$jid]['name'] = $payload['queryItemName'][$key];
				}
			}
			
			$response = array('jaxl'=>'rosterList', 'roster'=>$roster);
			JAXL0124::out($response);
		}
		
		public static function postDisconnect() {
			$response = array('jaxl'=>'disconnected');
			JAXL0124::out($response);
		}
		
		public static function getMessage($payloads) {
			$html = '';
			foreach($payloads as $payload) {
				// reject offline message
				if($payload['offline'] != JAXL0203::$ns) {
					if(strlen($payload['body']) > 0) {
						$html .= '<div class="mssgIn">';
						$html .= '<p class="from">'.$payload['from'].'</p>';
						$html .= '<p class="body">'.$payload['body'].'</p>';
						$html .= '</div>';
					}
					else if(isset($payload['chatState']) && in_array($payload['chatState'], JAXL0085::$chatStates)) {
						$html .= '<div class="presIn">';
						$html .= '<p class="from">'.$payload['from'].' chat state '.$payload['chatState'].'</p>';
						$html .= '</div>';
					}
				}
			}
			
			if($html != '') {
				$response = array('jaxl'=>'message', 'message'=>urlencode($html));
				JAXL0124::out($response);
			}
		}
		
		public static function getPresence($payloads) {
			$html = '';
			foreach($payloads as $payload) {
				if($payload['offline'] != JAXL0203::$ns) {
					if($payload['type'] == '' || in_array($payload['type'], array('available', 'unavailable'))) {
						$html .= '<div class="presIn">';
						$html .= '<p class="from">'.$payload['from'];
						if($payload['type'] == 'unavailable') $html .= ' is now offline</p>';
						else $html .= ' is now online</p>';
						$html .= '</div>';
					}
				}
			}
			
			if($html != '') {
				$response = array('jaxl'=>'presence', 'presence'=>urlencode($html));
				JAXL0124::out($response);
			}
		}
		
		public static function postEmptyBody($body) {
			$response = array('jaxl'=>'pinged');
			JAXL0124::out($response);
		}
		
	}
	
	// Add callbacks on various event handlers
	JAXLPlugin::add('jaxl_post_auth', array('boshchat', 'postAuth'));
	JAXLPlugin::add('jaxl_post_disconnect', array('boshchat', 'postDisconnect'));
	JAXLPlugin::add('jaxl_get_auth_mech', array('boshchat', 'doAuth'));
	JAXLPlugin::add('jaxl_get_empty_body', array('boshchat', 'postEmptyBody'));
	JAXLPlugin::add('jaxl_get_message', array('boshchat', 'getMessage'));
	JAXLPlugin::add('jaxl_get_presence', array('boshchat', 'getPresence'));
	
	// Handle incoming bosh request
	switch($jaxl->action) {
		case 'connect':
			$jaxl->user = $_POST['user'];
			$jaxl->pass = $_POST['pass'];
			JAXL0206::startStream(JAXL_HOST_NAME, JAXL_HOST_PORT);
			break;
		case 'disconnect':
			JAXL0206::endStream();
			break;
		case 'getRosterList':
			$jaxl->getRosterList(array('boshchat', 'handleRosterList'));
			break;
		case 'setStatus':
			$jaxl->setStatus(FALSE, FALSE, FALSE, TRUE);
			break;
		case 'message':
			$jaxl->sendMessage('jaxl.im-b-global@muc.'.JAXL_HOST_NAME, $_POST['message'], $jaxl->jid, 'groupchat');
			break;
		case 'joinRoom':
			JAXL0045::joinRoom($jaxl->jid, 'jaxl.im-b-global@muc.'.JAXL_HOST_NAME.'/abhinavsingh', 20, 'maxstanzas');
			break;
		case 'ping':
			JAXL0206::ping();
			break;
		case 'jaxl':
			break;
		default:
			$response = array('jaxl'=>'400', 'desc'=>$jaxl->action." not implemented");
			JAXL0124::out($response);
			break;
	}
	
?>