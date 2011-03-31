<?php
/**
 * Jaxl (Jabber XMPP Library)
 *
 * Copyright (c) 2009-2010, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Abhinav Singh nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package jaxl
 * @subpackage xep
 * @author Abhinav Singh <me@abhinavsingh.com>, Willy Barro <eu@willybarro.com>
 * @copyright Abhinav Singh
 * @link http://code.google.com/p/jaxl
 */

    /**
     * XEP-0045: Mutli-User Chat Implementation
    */
    class JAXL0045 {
        
        public static $ns = array(
            'muc' => 'http://jabber.org/protocol/muc',
            'disco' => 'http://jabber.org/protocol/disco'
        );
    
        public static function init($jaxl) {
            $jaxl->features[] = self::$ns;
            
            JAXLXml::addTag('presence', 'itemJid', '//presence/x/item/@jid');
            JAXLXml::addTag('presence', 'itemAffiliation', '//presence/x/item/@affiliation');
            JAXLXml::addTag('presence', 'itemRole', '//presence/x/item/@role');
        }
        
        /*
         * Occupant Use Cases
        */
        public static function joinRoom($jaxl, $jid, $roomJid, $history=0, $type='seconds') {
            $child = array();
            $child['payload'] = '';
            $child['payload'] .= '<x xmlns="'.self::$ns['muc'].'">';
            $child['payload'] .= '<history '.$type.'="'.$history.'"/>';
            $child['payload'] .= '</x>';
            return XMPPSend::presence($jaxl, $roomJid, $jid, $child, false);
        }
        
        public static function exitRoom($jaxl, $jid, $roomJid) {
            return XMPPSend::presence($jaxl, $roomJid, $jid, false, "unavailable");
        }

        /**
         * Get room information.
         * @param Jaxl $jaxl
         * @param String $fromJid
         * @param String $roomJid
         * @param mixed $callback
         * @return Bool|String
         */
        public static function getRoomInfo($jaxl, $fromJid, $roomJid, $callback = false)
        {
            $payload = '<query xmlns="' . self::$ns['disco'] . '#info"/>';

            return XMPPSend::iq($jaxl, "get", $payload, $roomJid, $fromJid, $callback);
        }

        /*
         * Moderator Use Cases
        */
        public static function kickOccupant($jaxl, $fromJid, $nick, $roomJid, $reason=false, $callback=false) {
            $payload = '<query xmlns="'.self::$ns['muc'].'#admin">';
            $payload .= '<item role="none" nick="'.$nick.'">';
            if($reason) $payload .= '<reason>'.$reason.'</reason>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $fromJid, $callback);
        }

        /*
         * Admin Use Cases
        */
        /**
         * Bans an user
         * @author Willy Barro
         * @param <type> $jaxl
         * @param <type> $fromJid
         * @param <type> $nick
         * @param <type> $roomJid
         * @param <type> $reason
         * @param <type> $callback
         * @return <type>
         */
        public static function banUser($jaxl, $fromJid, $nick, $roomJid, $reason=false, $callback=false) {
            $payload = '<query xmlns="'.self::$ns['muc'].'#admin">';
            $payload .= '<item role="outcast" nick="'.$nick.'">';
            if($reason) $payload .= '<reason>'.$reason.'</reason>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $fromJid, $callback);
        }

        public static function grantModeratorPrivileges() {
            
        }

        public static function revokeModeratorPrivileges() {

        }

        public static function modifyModeratorList() {
            
        }
        
        /*
         * Owner Use Cases
        */

        /**
         * Create a configurable muc room
         * @author Willy Barro
         * @link http://xmpp.org/extensions/xep-0045.html#createroom
         * @param JAXL $jaxl
         * @param string $jid
         * @param string $roomJid
         * @param int $history
         * @param string $type
         * @param array $roomConfig 
         */
        public static function createRoom($jaxl, $jid, $roomJid, $nickName, $history = 0, $type = 'seconds', array $roomConfig = array()) {
            $jid = JAXLUtil::getBareJid($jid);

            // Sends presence to the room with user informed nickname
            $presenceRoomCreationJid = $roomJid . '/' . $nickName;
            self::joinRoom($jaxl, $jid, $presenceRoomCreationJid, $history, $type);

            // Configures room
            self::configureRoom($jaxl, $jid, $roomJid, $roomConfig);

            // Exits from the room
            self::exitRoom($jaxl, $jid, $roomJid);
        }

        public static function getUniqueRoomName($jaxl) {
            return 'jaxl-muc-' . uniqid();
        }

        /**
         * Configures a chat room after it's been created.
         * @link http://xmpp.org/extensions/xep-0045.html#example-147
         * @param array $roomConfig 
         */
        public static function configureRoom($jaxl, $jid, $roomJid, array $rcfg = array(), array $callback = array()) {
            // Configurations
            $config = array(
                'FORM_TYPE' => self::$ns['muc'] . '#roomconfig',
                'muc#roomconfig_roomname'       => $rcfg['name'],
                'muc#roomconfig_roomdesc'       => $rcfg['description'],
                'muc#roomconfig_enablelogging'  => isset($rcfg['enablelogging']) ? $rcfg['enablelogging'] : '0',
                'muc#roomconfig_changesubject'  => isset($rcfg['changesubject']) ? (string)(int) $rcfg['changesubject'] : '0',
                'muc#roomconfig_allowinvites'   => isset($rcfg['allowinvites']) ? $rcfg['allowinvites'] : '0',
                'muc#roomconfig_publicroom'     => isset($rcfg['public']) ? (string)(int) $rcfg['public'] : '1',
                'muc#roomconfig_persistentroom' => $rcfg['persistent'],
                'muc#roomconfig_moderatedroom'  => isset($rcfg['moderated']) ? (string)(int) $rcfg['moderated'] : '0',
                'muc#roomconfig_membersonly'    => isset($rcfg['membersonly']) ? (string)(int) $rcfg['membersonly'] : '0',
            );

            //
            if(isset($rcfg['admins']) && !empty($rcfg['admins'])) {
                $config['muc#roomconfig_roomadmins'] = $rcfg['admins'];
            } else {
                $config['muc#roomconfig_roomadmins'] = array();
            }

            if(isset($rcfg['owners'])) {
                $config['muc#roomconfig_roomowners'] = $rcfg['owners'];
            } else {
                $config['muc#roomconfig_roomowners'] = array($jid);
            }

            if(isset($rcfg['maxusers'])) {
                $config['muc#roomconfig_maxusers'] = $rcfg['maxusers'];
            } else {
                $config['muc#roomconfig_maxusers'] = '30';
            }

            // Roles
            $roles = array('moderator', 'participant', 'visitor');

            // Roles for which Presence is Broadcast
            $rcfg['presencebroadcast'] = (array) $rcfg['presencebroadcast'];

            if(!empty($rcfg['presencebroadcast'])) {
                $config['muc#roomconfig_presencebroadcast'] = $rcfg['presencebroadcast'];
            } else {
                $config['muc#roomconfig_presencebroadcast'] = $roles;
            }

            // Roles and Affiliations that May Retrieve Member List
            if($rcfg['memberlist'] != '') {
                $rcfg['memberlist'] = strtolower($rcfg['memberlist']);

                if(in_array($rcfg['memberlist'], $roles)) {
                    $config['muc#roomconfig_getmemberlist'] = $rcfg['memberlist'];
                }
            }

            // Password protection
            if(trim($rcfg['password']) != '') {
                $config['muc#roomconfig_passwordprotectedroom'] = '1';
                $config['muc#roomconfig_roomsecret'] = $rcfg['password'];
            } else {
                $config['muc#roomconfig_passwordprotectedroom'] = '0';
                $config['muc#roomconfig_roomsecret'] = '';
            }

            // Who May Discover Real JIDs?
            $whoisRoles = array('moderators', 'anyone');
            if($rcfg['whois'] != '') {
                $rcfg['whois'] = strtolower($rcfg['whois']);

                if(in_array($rcfg['whois'], $whoisRoles)) {
                    $config['muc#roomconfig_whois'] = $rcfg['whois'];
                }
            } else {
                $config['muc#roomconfig_whois'] = $whoisRoles[1];
            }

            // X-mucs
            $config['x-muc#roomconfig_reservednick'] = '0';
            $config['x-muc#roomconfig_canchangenick'] = '1';
            $config['x-muc#roomconfig_registration'] = '1';

            // Transform the associative array to the XEP0004 pattern (array('var' => '', value => ''))
            $fields = array();
            foreach($config as $k => $v) {
                $fields[] = array(
                    'var' => $k,
                    'value' => $v,
                );
            }

            // Room configs (needs to be 2 calls)
            self::getRoomConfig($jaxl, $jid, $roomJid);
            self::getRoomConfig($jaxl, $jid, $roomJid);

            // Set configuration
            self::setRoomConfig($jaxl, $jid, $roomJid, $fields);
        }

        public static function getRoomConfig($jaxl, $jid, $roomJid, $callback=false) {
            $payload = '<query xmlns="'.self::$ns['muc'].'#owner"/>';
            return XMPPSend::iq($jaxl, "get", $payload, $roomJid, $jid, $callback);
        }
        
        public static function setRoomConfig($jaxl, $jid, $roomJid, $fields, $callback=false) {
            $payload = JAXL0004::setFormField($fields, false, false, 'submit');
            $payload = '<query xmlns="'.self::$ns['muc'].'#owner">'.$payload.'</query>';
            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $jid, $callback);
        }

        public static function grantOwnerPrivileges($jaxl, $fromJid, $toJid, $roomJid, $reason=false, $callback=false) {
            $payload = '<query xmlns="'.self::$ns['muc'].'#admin">';
            $payload .= '<item affiliation="owner" jid="'.$toJid.'">';
            if($reason) $payload .= '<reason>'.$reason.'</reason>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $fromJid, $callback);
        }
        
        public static function revokeOwnerPrivileges($jaxl, $fromJid, $toJid, $roomJid, $reason=false, $callback=false) {
            $payload = '<query xmlns="'.self::$ns['muc'].'#admin">';
            $payload .= '<item affiliation="member" jid="'.$toJid.'">';
            if($reason) $payload .= '<reason>'.$reason.'</reason>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $fromJid, $callback);
        }
        
        public static function modifyOwnerList() {
            
        }
        
        public static function grantAdminPrivileges($jaxl, $fromJid, $toJid, $roomJid, $reason=false, $callback=false) {
            $payload = '<query xmlns="'.self::$ns['muc'].'#admin">';
            $payload .= '<item affiliation="admin" jid="'.$toJid.'">';
            if($reason) $payload .= '<reason>'.$reason.'</reason>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $fromJid, $callback);
        }

        public static function removeAdminPrivileges($jaxl, $fromJid, $toJid, $roomJid, $reason=false, $callback=false) {
            $payload = '<query xmlns="'.self::$ns['muc'].'#admin">';
            $payload .= '<item affiliation="member" jid="'.$toJid.'">';
            if($reason) $payload .= '<reason>'.$reason.'</reason>';
            $payload .= '</item>';
            $payload .= '</query>';
            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $fromJid, $callback);
        }

        public static function modifyAdminList() {

        }

        /**
         * Destroys a room
         * 
         * @param Jaxl $jaxl
         * @param String $fromJid
         * @param String $roomJid
         * @param String $alternateRoomJid Alternate room from which the users must be redirected
         * @param String $reason The reason why you're destroying the room
         * @param mixed $callback
         */
        public static function destroyRoom($jaxl, $fromJid, $roomJid, $alternateRoomJid = '', $reason = '', $callback = false)
        {
            // We must join the room before trying to destroy it
            $presenceRoomDestroyJid = $roomJid . '/jaxl-shredder-' . uniqid();
            self::joinRoom($jaxl, $fromJid, $presenceRoomDestroyJid);

            // Destroy the room
            $payload  = '<query xmlns="' . self::$ns['muc'] . '#owner">';
            $payload .= '<destroy'. ($alternateRoomJid ? ' jid="' . $alternateRoomJid .'"': '') .'>';
            if($reason) {
                $payload .= '<reason>' . $reason . '</reason>';
            }
            $payload .= '</destroy>';
            $payload .= '</query>';

            return XMPPSend::iq($jaxl, "set", $payload, $roomJid, $fromJid, $callback);
        }

        
        
    }
    
?>
